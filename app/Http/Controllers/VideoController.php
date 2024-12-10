<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\VideoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoController extends Controller
{
    protected $videoService;

    public function __construct(VideoService $videoService)
    {
        $this->videoService = $videoService;
    }

    public function index()
    {
        $videos = Video::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(9);

        return view('dashboard', compact('videos'));
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
        return view('videos.upload');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'path' => 'required|string'
            ]);

            // Create video record and start processing
            $video = $this->videoService->store(
                $request->input('path'),
                $request->input('title'),
                auth()->user()->id,
                $request->input('description')
            );

            // Return success response immediately
            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully and is being processed',
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
                return redirect()->route('dashboard')
                    ->with('error', 'You do not have permission to delete this video.');
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

            Log::info('Video and associated files deleted successfully', [
                'video_id' => $video->id,
                'file_paths' => $filePaths,
                'hls_path' => $hlsPath
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Video deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Video deletion failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'Failed to delete video: ' . $e->getMessage());
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
            $this->videoService->generateHLSPlaylist($video);
        }

        return response()->json([
            'hls_url' => Storage::disk('public')->url($playlistPath),
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
            if (!auth()->check()) {
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
                'originalName' => 'required|string',
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

    public function checkConversionProgress(Video $video)
    {
        // Check if user has access to this video
        if ($video->user_id !== auth()->user()->id) {
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
}
