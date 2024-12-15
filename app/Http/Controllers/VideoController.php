<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\VideoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoController extends Controller
{
    protected $videoService;

    public function __construct(VideoService $videoService)
    {
        $this->videoService = $videoService;
    }

    public function index(Request $request)
    {
        $query = Video::query();
        $folderId = $request->get('folder_id');
        $searchQuery = $request->get('search');

        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }

        if ($searchQuery) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('title', 'like', '%' . $searchQuery . '%')
                  ->orWhere('description', 'like', '%' . $searchQuery . '%')
                  ->orWhere('filename', 'like', '%' . $searchQuery . '%');
            });
        }

        $videos = $query->latest()->paginate(12);
        $currentFolder = $folderId ? Folder::find($folderId) : null;
        $folders = Folder::whereNull('parent_id')->with('children')->get();

        return view('folders.index', compact('videos', 'folders', 'currentFolder'));
    }

    /**
     * Display the specified video.
     */
    public function show(Video $video)
    {
        // Check if user has access to this video
        if ($video->user_id !== auth()->id()) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to view this video.');
        }

        return view('videos.show', compact('video'));
    }

    public function create()
    {
        // Get all folders for the folder selection dropdown
        $folders = Folder::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();
            
        return view('videos.upload', compact('folders'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'description' => 'nullable|string',
                'path' => 'required|string',
                'folder_id' => 'nullable|exists:folders,id'
            ]);

            // Verify folder belongs to user if specified
            if ($request->folder_id) {
                $folder = Folder::findOrFail($request->folder_id);
                if ($folder->user_id !== Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized folder access'
                    ], 403);
                }
            }

            // Create video record and start processing
            $video = $this->videoService->store(
                $request->input('path'),
                $request->input('title'),
                Auth::id(),
                $request->input('description'),
                $request->input('folder_id')
            );

            // Return success response immediately
            return response()->json([
                'success' => true,
                'video' => $video
            ]);

        } catch (\Exception $e) {
            Log::error('Video upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload video: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Video $video)
    {
        try {
            // Check if user owns the video
            if ($video->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete this video.'
                ], 403);
            }

            // Get all paths before deleting the video record
            $filePaths = [
                $video->file_path, // Original video
                $video->thumbnail_path, // Thumbnail
            ];

            // Get HLS directory path
            $uuid = basename(dirname($video->file_path));
            $hlsPath = "videos/hls/{$uuid}";

            // Delete the video record first
            $video->delete();

            // Delete all associated files
            foreach ($filePaths as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    
                    // Delete parent directory if empty
                    $dirPath = dirname($path);
                    if (Storage::disk('public')->exists($dirPath)) {
                        $files = Storage::disk('public')->files($dirPath);
                        $directories = Storage::disk('public')->directories($dirPath);
                        if (empty($files) && empty($directories)) {
                            Storage::disk('public')->deleteDirectory($dirPath);
                        }
                    }
                }
            }

            // Delete HLS directory and all its contents
            if (Storage::disk('public')->exists($hlsPath)) {
                Storage::disk('public')->deleteDirectory($hlsPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Video deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Video deletion failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete video: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stream(Video $video)
    {
        // Check if user has access to this video
        if ($video->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $videoPath = Storage::disk('public')->path($video->file_path);
        $playlistPath = str_replace('.mp4', '.m3u8', $video->file_path);
        
        // If HLS playlist doesn't exist, create it
        if (!Storage::disk('public')->exists($playlistPath)) {
            $this->videoService->processVideo($video);
        }

        return response()->json([
            'hls_url' => URL::to(Storage::disk('public')->url($playlistPath)),
            'video' => $video
        ]);
    }

    public function segment(Request $request, $video_id, $segment)
    {
        $filePath = storage_path("app/public/videos/hls/{$video_id}/{$segment}");
        
        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->file($filePath, [
            'Content-Type' => 'video/MP2T',
            'Cache-Control' => 'public, max-age=86400'
        ]);
    }

    public function uploadChunk(Request $request)
    {
        // Increase memory limit and execution time for large uploads
        ini_set('memory_limit', '1G');
        set_time_limit(600); // 10 minutes

        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'session_expired' => true
                ], 401);
            }

            $request->validate([
                'file' => 'required|file',
                'chunkNumber' => 'required|integer|min:0',
                'totalChunks' => 'required|integer|min:1',
                'uuid' => 'required|string',
                'originalName' => 'required|string'
            ]);

            $chunkNumber = $request->input('chunkNumber');
            $totalChunks = $request->input('totalChunks');
            $uuid = $request->input('uuid');
            // Store chunk in temporary location
            $chunkFile = $request->file('file');
            $chunkPath = "chunks/{$uuid}";
            
            if (!Storage::disk('public')->exists($chunkPath)) {
                Storage::disk('public')->makeDirectory($chunkPath);
            }
            
            // Store the chunk with a lock to prevent concurrent access
            $lockFile = storage_path("app/public/{$chunkPath}/upload.lock");
            $lock = fopen($lockFile, 'c');
            
            if (flock($lock, LOCK_EX)) {
                try {
                    $chunkFile->storeAs($chunkPath, "chunk_{$chunkNumber}", 'public');
                    
                    // Verify all chunks up to this point are present
                    $allChunksPresent = true;
                    for ($i = 0; $i <= $chunkNumber; $i++) {
                        if (!Storage::disk('public')->exists("{$chunkPath}/chunk_{$i}")) {
                            $allChunksPresent = false;
                            break;
                        }
                    }

                    // If this is the last chunk and all previous chunks exist, merge them
                    if ($chunkNumber == $totalChunks - 1 && $allChunksPresent) {
                        $finalPath = $this->mergeChunks($uuid, $totalChunks, $request->input('originalName'));
                        
                        // Clean up chunks
                        Storage::disk('public')->deleteDirectory($chunkPath);
                        
                        return response()->json([
                            'success' => true,
                            'message' => 'All chunks uploaded successfully',
                            'complete' => true,
                            'path' => $finalPath
                        ]);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Chunk uploaded successfully',
                        'complete' => false,
                        'chunk' => $chunkNumber,
                        'totalChunks' => $totalChunks
                    ]);
                } finally {
                    flock($lock, LOCK_UN);
                    fclose($lock);
                }
            } else {
                throw new \Exception('Could not acquire lock for chunk upload');
            }

        } catch (\Exception $e) {
            Log::error('Chunk upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'chunk' => $request->input('chunkNumber'),
                'total_chunks' => $request->input('totalChunks'),
                'uuid' => $request->input('uuid')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload chunk: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function mergeChunks($uuid, $totalChunks, $originalName)
    {
        ini_set('memory_limit', '1G');
        set_time_limit(600); // 10 minutes

        $chunkPath = "chunks/{$uuid}";
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $finalPath = "videos/original/{$uuid}/{$originalName}";
        
        // Ensure the target directory exists
        $targetDir = dirname(Storage::disk('public')->path($finalPath));
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Create a new file handle for the final file
        $finalFile = Storage::disk('public')->path($finalPath);
        $out = fopen($finalFile, "wb");
        
        try {
            // Append each chunk to the final file
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkFile = Storage::disk('public')->path("{$chunkPath}/chunk_{$i}");
                if (!file_exists($chunkFile)) {
                    throw new \Exception("Chunk {$i} is missing");
                }
                
                $in = fopen($chunkFile, "rb");
                while ($buff = fread($in, 8192)) {
                    fwrite($out, $buff);
                }
                fclose($in);
            }
            
            fclose($out);
            
            return $finalPath;
        } catch (\Exception $e) {
            fclose($out);
            // Clean up the incomplete file
            if (file_exists($finalFile)) {
                unlink($finalFile);
            }
            throw $e;
        }
    }

    public function checkConversionProgress($id)
    {
        $video = Video::findOrFail($id);
        // Check if user has access to this video
        if ($video->user_id !== Auth::id()) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        $status = $video->status;
        $progress = 0;

        if ($status === 'completed') {
            $progress = 100;
        } elseif ($status === 'failed') {
            $progress = -1;
        } elseif ($status === 'processing') {
            // Get progress from cache
            $progressKey = "video_conversion_progress_{$video->id}";
            $progress = Cache::get($progressKey, 0);
        }

        return response()->json([
            'status' => $status,
            'progress' => $progress,
            'hls_url' => $video->hls_url
        ]);
    }

    public function copy(Request $request)
    {
        try {
            $request->validate([
                'video_id' => 'required|exists:videos,id',
                'target_folder_id' => 'nullable|exists:folders,id'
            ]);

            $video = Video::findOrFail($request->video_id);
            
            // Check ownership
            if ($video->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check if target folder exists and belongs to user
            if ($request->target_folder_id) {
                $targetFolder = Folder::findOrFail($request->target_folder_id);
                if ($targetFolder->user_id !== Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access to target folder'
                    ], 403);
                }
            }

            // Generate new UUID for the copied video
            $newUuid = (string) Str::uuid();
            
            // Initialize paths
            $originalPath = null;
            $hlsPath = null;
            $thumbnailPath = null;

            // Copy original video file
            if ($video->file_path) {
                $originalDir = dirname($video->file_path);
                $newOriginalDir = str_replace(basename($originalDir), $newUuid, $originalDir);
                $originalPath = str_replace($originalDir, $newOriginalDir, $video->file_path);
                
                if (!Storage::disk('public')->exists($newOriginalDir)) {
                    Storage::disk('public')->makeDirectory($newOriginalDir);
                }
                
                if (Storage::disk('public')->exists($video->file_path)) {
                    Storage::disk('public')->copy($video->file_path, $originalPath);
                }
            }

            // Copy HLS files
            if ($video->hls_path) {
                $hlsDir = dirname($video->hls_path);
                $newHlsDir = str_replace(basename($hlsDir), $newUuid, $hlsDir);
                $hlsPath = str_replace($hlsDir, $newHlsDir, $video->hls_path);
                
                if (!Storage::disk('public')->exists($newHlsDir)) {
                    Storage::disk('public')->makeDirectory($newHlsDir);
                }
                
                // Copy all files in the HLS directory
                if (Storage::disk('public')->exists($hlsDir)) {
                    foreach (Storage::disk('public')->allFiles($hlsDir) as $file) {
                        $newFile = str_replace($hlsDir, $newHlsDir, $file);
                        Storage::disk('public')->copy($file, $newFile);
                    }
                }
            }

            // Copy thumbnail
            if ($video->thumbnail_path) {
                $thumbnailPath = str_replace(
                    basename($video->thumbnail_path, '.jpg'),
                    $newUuid,
                    $video->thumbnail_path
                );
                
                if (Storage::disk('public')->exists($video->thumbnail_path)) {
                    Storage::disk('public')->copy($video->thumbnail_path, $thumbnailPath);
                }
            }

            // Create a copy of the video in database
            $newVideo = $video->replicate();
            $newVideo->folder_id = $request->target_folder_id;
            $newVideo->title = $video->title . ' (Copy)';
            $newVideo->views = 0; // Reset view count
            $newVideo->conversion_progress = 100; // Set conversion as complete since we copied all files
            $newVideo->error_message = null; // Clear any error messages
            
            // Generate a unique slug
            $baseSlug = Str::slug($newVideo->title);
            $slug = $baseSlug;
            $counter = 1;
            
            while (Video::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . Str::random(6);
                $counter++;
                
                if ($counter > 10) {
                    throw new \Exception('Unable to generate unique slug after multiple attempts');
                }
            }
            
            $newVideo->slug = $slug;
            $newVideo->file_path = $originalPath ?? $video->file_path;
            $newVideo->hls_path = $hlsPath ?? $video->hls_path;
            $newVideo->thumbnail_path = $thumbnailPath ?? $video->thumbnail_path;
            $newVideo->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // If something goes wrong, clean up any copied files
            if (isset($originalPath) && Storage::disk('public')->exists(dirname($originalPath))) {
                Storage::disk('public')->deleteDirectory(dirname($originalPath));
            }
            if (isset($hlsPath) && Storage::disk('public')->exists(dirname($hlsPath))) {
                Storage::disk('public')->deleteDirectory(dirname($hlsPath));
            }
            if (isset($thumbnailPath) && Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }

            Log::error('Error copying video: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to copy video'
            ], 500);
        }
    }

    public function move(Request $request)
    {
        try {
            $request->validate([
                'video_id' => 'required|exists:videos,id',
                'target_folder_id' => 'nullable|exists:folders,id'
            ]);

            $video = Video::findOrFail($request->video_id);
            
            // Check ownership
            if ($video->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check if target folder exists and belongs to user
            if ($request->target_folder_id) {
                $targetFolder = Folder::findOrFail($request->target_folder_id);
                if ($targetFolder->user_id !== Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access to target folder'
                    ], 403);
                }
            }

            $video->folder_id = $request->target_folder_id;
            $video->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error moving video: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to move video'
            ], 500);
        }
    }

    public function rename(Request $request, $id)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255'
            ]);

            $video = Video::findOrFail($id);
            
            // Check ownership
            if ($video->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $video->title = $request->title;
            $video->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error renaming video: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to rename video'
            ], 500);
        }
    }

    public function edit(Video $video)
    {
        // Check ownership
        if ($video->user_id !== Auth::id()) {
            abort(403);
        }

        // Get all folders for the folder selection dropdown
        $folders = Folder::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        return view('videos.edit', compact('video', 'folders'));
    }

    public function update(Request $request, Video $video)
    {
        // Check ownership
        if ($video->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'folder_id' => 'nullable|exists:folders,id',
                'thumbnail' => 'nullable|image|max:2048' // Optional new thumbnail
            ]);

            // Check if new folder belongs to user
            if ($request->folder_id) {
                $folder = Folder::findOrFail($request->folder_id);
                if ($folder->user_id !== Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access to folder'
                    ], 403);
                }
            }

            // Update basic info
            $video->title = $request->title;
            $video->description = $request->description;
            $video->folder_id = $request->folder_id;

            // Handle new thumbnail if uploaded
            if ($request->hasFile('thumbnail')) {
                // Delete old thumbnail if exists
                if ($video->thumbnail_path && Storage::disk('public')->exists($video->thumbnail_path)) {
                    Storage::disk('public')->delete($video->thumbnail_path);
                }

                // Generate new thumbnail path with UUID
                $uuid = basename(dirname($video->hls_path));
                $extension = $request->file('thumbnail')->getClientOriginalExtension();
                $thumbnailPath = 'thumbnails/' . $uuid . '.' . $extension;

                // Store new thumbnail in public disk
                $request->file('thumbnail')->storeAs('public', $thumbnailPath);
                $video->thumbnail_path = $thumbnailPath;
            }

            $video->save();

            return response()->json([
                'success' => true,
                'message' => 'Video updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating video: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update video'
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $request->validate([
                'video_ids' => 'required|array',
                'video_ids.*' => 'exists:videos,id'
            ]);

            $videos = Video::whereIn('id', $request->video_ids)
                ->where('user_id', Auth::id())
                ->get();

            foreach ($videos as $video) {
                try {
                    // Get all paths before deleting the video record
                    $filePaths = [
                        $video->file_path, // Original video
                        $video->thumbnail_path, // Thumbnail
                    ];

                    // Get HLS directory path
                    $uuid = basename(dirname($video->file_path));
                    $hlsPath = "videos/hls/{$uuid}";

                    // Delete the video record first
                    $video->delete();

                    // Delete all associated files
                    foreach ($filePaths as $path) {
                        if ($path && Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                            
                            // Delete parent directory if empty
                            $dirPath = dirname($path);
                            if (Storage::disk('public')->exists($dirPath)) {
                                $files = Storage::disk('public')->files($dirPath);
                                $directories = Storage::disk('public')->directories($dirPath);
                                if (empty($files) && empty($directories)) {
                                    Storage::disk('public')->deleteDirectory($dirPath);
                                }
                            }
                        }
                    }

                    // Delete HLS directory and all its contents
                    if (Storage::disk('public')->exists($hlsPath)) {
                        Storage::disk('public')->deleteDirectory($hlsPath);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to delete video during bulk deletion', [
                        'video_id' => $video->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with next video even if one fails
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Videos deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting videos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete videos'
            ], 500);
        }
    }

    public function search(Request $request)
    {
        $searchQuery = $request->get('search');
        $folderId = $request->get('folder_id');
        
        // Get videos based on search
        $videos = Video::where('user_id', Auth::id())
            ->when($folderId, function($query) use ($folderId) {
                $query->where('folder_id', $folderId);
            }, function($query) {
                $query->whereNull('folder_id');
            })
            ->when($searchQuery, function($query) use ($searchQuery) {
                $query->where(function($q) use ($searchQuery) {
                    $q->where('title', 'like', '%' . $searchQuery . '%')
                      ->orWhere('description', 'like', '%' . $searchQuery . '%')
                      ->orWhere('filename', 'like', '%' . $searchQuery . '%');
                });
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($video) {
                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'thumbnail_url' => $video->thumbnail_url
                ];
            });
        
        // Get folders based on search
        $folders = Folder::where('user_id', Auth::id())
            ->when($searchQuery, function($query) use ($searchQuery) {
                $query->where('name', 'like', '%' . $searchQuery . '%');
            })
            ->when($folderId, function($query) use ($folderId) {
                $query->where('parent_id', $folderId);
            }, function($query) {
                $query->whereNull('parent_id');
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(function($folder) {
                return [
                    'id' => $folder->id,
                    'name' => $folder->name
                ];
            });

        return response()->json([
            'videos' => $videos,
            'folders' => $folders
        ]);
    }
}
