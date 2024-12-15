<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FolderController extends Controller
{
    public function index(Request $request)
    {
        $folderId = $request->query('folder_id');
        
        // Get folders in current directory
        $folders = Folder::where('user_id', Auth::id())
            ->where('parent_id', $folderId)
            ->orderBy('name')
            ->get();
            
        // Get videos in current directory
        $videos = Video::where('user_id', Auth::id())
            ->where('folder_id', $folderId)
            ->orderBy('created_at', 'desc')
            ->paginate(9);
            
        // Get current folder for breadcrumb
        $currentFolder = $folderId ? Folder::findOrFail($folderId) : null;

        return view('folders.index', compact('videos', 'folders', 'currentFolder'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'folder_id' => 'nullable|exists:folders,id'
        ]);

        $folder = new Folder();
        $folder->name = $request->name;
        $folder->parent_id = $request->folder_id;
        $folder->user_id = Auth::id();
        $folder->save();

        return response()->json($folder);
    }

    public function update(Request $request, Folder $folder)
    {
        if ($folder->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $folder->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(6)
        ]);

        return response()->json([
            'success' => true,
            'folder' => $folder
        ]);
    }

    public function destroy(Request $request, Folder $folder)
    {
        Log::info('Starting folder deletion', [
            'folder_id' => $folder->id,
            'folder_name' => $folder->name,
            'user_id' => Auth::id(),
            'folder_user_id' => $folder->user_id,
            'request_data' => $request->all()
        ]);

        if ($folder->user_id !== Auth::id()) {
            Log::warning('Unauthorized folder deletion attempt', [
                'folder_id' => $folder->id,
                'user_id' => Auth::id(),
                'folder_user_id' => $folder->user_id
            ]);
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $deleteOption = $request->input('delete_option');
            $destinationFolderId = $request->input('destination_folder_id');

            Log::info('Processing folder deletion', [
                'delete_option' => $deleteOption,
                'destination_folder_id' => $destinationFolderId
            ]);

            if ($deleteOption === 'with_contents') {
                Log::info('Deleting folder with contents', [
                    'folder_id' => $folder->id,
                    'video_count' => $folder->allVideos()->count(),
                    'subfolder_count' => $folder->children->count()
                ]);

                // Delete all videos in this folder and subfolders
                foreach ($folder->allVideos() as $video) {
                    $video_uuid = preg_match('/videos\/original\/([^\/]+)\//', $video->file_path, $matches) ? $matches[1] : null;
                    Log::info('Deleting video', [
                        'video_id' => $video->id,
                        'video_path' => $video->file_path,
                        'video_uuid' => $video_uuid
                    ]);
                    try {
                        // Delete video files if they exist
                        if ($video->file_path && Storage::disk('public')->exists($video->file_path)) {
                            Storage::disk('public')->delete($video->file_path);
                            Storage::disk('public')->deleteDirectory('videos/original/' . $video_uuid);
                        }
                        
                        // Delete HLS segments if they exist
                        if ($video_uuid) {
                            $hlsPath = 'videos/hls/' . $video_uuid;
                            if (Storage::disk('public')->exists($hlsPath)) {
                                Storage::disk('public')->deleteDirectory($hlsPath);
                            }
                        }
                        
                        // Delete thumbnail if it exists
                        if ($video->thumbnail_path && Storage::disk('public')->exists($video->thumbnail_path)) {
                            Storage::disk('public')->delete($video->thumbnail_path);
                        }
                        
                        // Delete video record
                        $video->delete();
                    } catch (\Exception $e) {
                        Log::warning('Error deleting video files', [
                            'video_id' => $video->id,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with deletion even if file deletion fails
                        $video->delete();
                    }
                }

                // Delete all subfolders recursively
                foreach ($folder->children as $childFolder) {
                    Log::info('Deleting subfolder', [
                        'subfolder_id' => $childFolder->id,
                        'subfolder_name' => $childFolder->name
                    ]);
                    $childFolder->delete();
                }

                // Delete the folder itself
                $folder->delete();
                Log::info('Folder deleted successfully with all contents');

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Folder and all contents deleted successfully'
                ]);
            } 
            else if ($deleteOption === 'move_contents') {
                Log::info('Moving folder contents before deletion', [
                    'folder_id' => $folder->id,
                    'destination_folder_id' => $destinationFolderId
                ]);

                // Validate destination folder
                if ($destinationFolderId === $folder->id) {
                    Log::warning('Attempted to move contents to same folder');
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot move contents to the same folder'
                    ]);
                }

                // Check if destination folder exists and belongs to user
                if ($destinationFolderId) {
                    $destinationFolder = Folder::find($destinationFolderId);
                    Log::info('Destination folder check', [
                        'destination_folder_exists' => (bool)$destinationFolder,
                        'destination_folder_user_id' => $destinationFolder ? $destinationFolder->user_id : null
                    ]);

                    if (!$destinationFolder || $destinationFolder->user_id !== Auth::id()) {
                        Log::warning('Invalid destination folder');
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid destination folder'
                        ]);
                    }

                    // Check if destination is a child of the folder being deleted
                    if ($this->isFolderDescendant($destinationFolder, $folder)) {
                        Log::warning('Attempted to move contents to a subfolder');
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot move contents to a subfolder'
                        ]);
                    }
                }

                // Move all videos to destination folder
                $videoCount = $folder->videos->count();
                foreach ($folder->videos as $video) {
                    Log::info('Moving video', [
                        'video_id' => $video->id,
                        'from_folder' => $folder->id,
                        'to_folder' => $destinationFolderId
                    ]);
                    $video->update(['folder_id' => $destinationFolderId]);
                }

                // Move all direct child folders to destination
                $childFolderCount = $folder->children->count();
                foreach ($folder->children as $childFolder) {
                    Log::info('Moving subfolder', [
                        'subfolder_id' => $childFolder->id,
                        'from_folder' => $folder->id,
                        'to_folder' => $destinationFolderId
                    ]);
                    $childFolder->update(['parent_id' => $destinationFolderId]);
                }

                Log::info('All contents moved successfully', [
                    'videos_moved' => $videoCount,
                    'folders_moved' => $childFolderCount
                ]);

                // Delete the empty folder
                $folder->delete();
                Log::info('Empty folder deleted successfully');

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Contents moved and folder deleted successfully'
                ]);
            }

            Log::warning('Invalid delete option provided', [
                'delete_option' => $deleteOption
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid delete option'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete folder', [
                'folder_id' => $folder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete folder: ' . $e->getMessage()
            ], 500);
        }
    }

    private function isFolderDescendant($folder, $ancestor)
    {
        if (!$folder || !$ancestor) {
            return false;
        }

        if ($folder->parent_id === $ancestor->id) {
            return true;
        }

        if ($folder->parent_id) {
            return $this->isFolderDescendant($folder->parent, $ancestor);
        }

        return false;
    }

    private function deleteRecursively(Folder $folder)
    {
        // First, handle all child folders recursively
        foreach ($folder->children as $childFolder) {
            $this->deleteRecursively($childFolder);
        }

        // Delete all videos in the folder
        foreach ($folder->videos as $video) {
            $video_uuid = preg_match('/videos\/original\/([^\/]+)\//', $video->file_path, $matches) ? $matches[1] : null;
            // Delete video files from storage
            if (Storage::disk('public')->exists($video->file_path)) {
                Storage::disk('public')->delete($video->file_path);
            }

            // Delete HLS segments
            $hlsPath = 'videos/hls/' . $video_uuid;
            if (Storage::disk('public')->exists($hlsPath)) {
                Storage::disk('public')->deleteDirectory($hlsPath);
            }

            // Delete thumbnail
            if ($video->thumbnail_path && Storage::disk('public')->exists($video->thumbnail_path)) {
                Storage::disk('public')->delete($video->thumbnail_path);
            }

            // Delete video record from database
            $video->delete();
        }

        // Finally, delete the folder itself
        $folder->delete();
    }

    private function moveContents(Folder $folder, $destinationFolderId)
    {
        // Move all direct child folders
        foreach ($folder->children as $childFolder) {
            $childFolder->parent_id = $destinationFolderId;
            $childFolder->save();
        }

        // Move all videos
        foreach ($folder->videos as $video) {
            $video->folder_id = $destinationFolderId;
            $video->save();
        }
    }

    private function isFolderAncestor(Folder $ancestor, ?Folder $descendant): bool
    {
        if (!$descendant) {
            return false;
        }

        if ($descendant->parent_id === $ancestor->id) {
            return true;
        }

        if ($descendant->parent_id) {
            return $this->isFolderAncestor($ancestor, $descendant->parent);
        }

        return false;
    }

    public function moveVideos(Request $request)
    {
        $request->validate([
            'video_ids' => 'required|array',
            'video_ids.*' => 'exists:videos,id',
            'folder_id' => 'nullable|exists:folders,id'
        ]);

        // Check if target folder belongs to user
        if ($request->folder_id) {
            $folder = Folder::findOrFail($request->folder_id);
            if ($folder->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to target folder'
                ], 403);
            }
        }

        // Move videos
        Video::whereIn('id', $request->video_ids)
            ->where('user_id', Auth::id())
            ->update(['folder_id' => $request->folder_id]);

        return response()->json(['success' => true]);
    }

    public function list(Request $request)
    {
        $folders = Folder::where('user_id', Auth::id())
            ->orderBy('name')
            ->get()
            ->map(function ($folder) {
                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'parent_id' => $folder->parent_id,
                    'has_children' => $folder->children->isNotEmpty(),
                    'children_count' => $folder->children->count(),
                    'videos_count' => $folder->videos->count()
                ];
            });
            
        return response()->json([
            'folders' => $folders,
            'current_folder' => $request->query('folder_id') ? Folder::find($request->query('folder_id')) : null
        ]);
    }

    public function copy(Request $request)
    {
        try {
            $request->validate([
                'folder_id' => 'required|exists:folders,id',
                'target_folder_id' => 'nullable|exists:folders,id'
            ]);

            $sourceFolder = Folder::findOrFail($request->folder_id);
            
            // Check ownership
            if ($sourceFolder->user_id !== Auth::id()) {
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

            // Create a copy of the folder
            $newFolder = $sourceFolder->replicate();
            $newFolder->parent_id = $request->target_folder_id;
            $newFolder->name = $sourceFolder->name . ' (Copy)';
            $newFolder->save();

            // Copy all videos in this folder
            foreach ($sourceFolder->videos as $video) {
                $newVideo = $video->replicate();
                $newVideo->folder_id = $newFolder->id;
                $newVideo->save();
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error copying folder: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to copy folder'
            ], 500);
        }
    }

    public function move(Request $request)
    {
        Log::info('Starting folder move operation', [
            'folder_id' => $request->folder_id,
            'target_folder_id' => $request->target_folder_id
        ]);

        try {
            Log::info('Validating request parameters');
            $request->validate([
                'folder_id' => 'required|exists:folders,id',
                'target_folder_id' => 'nullable|exists:folders,id'
            ]);

            Log::info('Finding source folder');
            $folder = Folder::findOrFail($request->folder_id);
            
            // Check ownership
            Log::info('Checking source folder ownership');
            if ($folder->user_id !== Auth::id()) {
                Log::warning('Unauthorized access attempt to move folder', [
                    'folder_id' => $folder->id,
                    'user_id' => Auth::id()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check if target folder exists and belongs to user
            if ($request->parent_id) {
                Log::info('Checking target folder', ['target_folder_id' => $request->parent_id]);
                $targetFolder = Folder::findOrFail($request->parent_id);
                if ($targetFolder->user_id !== Auth::id()) {
                    Log::warning('Unauthorized access attempt to target folder', [
                        'target_folder_id' => $targetFolder->id,
                        'user_id' => Auth::id()
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access to target folder'
                    ], 403);
                }
            }

            Log::info('Moving folder', [
                'folder_id' => $folder->id,
                'old_parent_id' => $folder->parent_id,
                'new_parent_id' => $request->parent_id
            ]);
            
            $folder->parent_id = $request->parent_id;
            $folder->save();

            Log::info('Folder move completed successfully');
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error moving folder', [
                'error' => $e->getMessage(),
                'folder_id' => $request->folder_id,
                'parent_id' => $request->parent_id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to move folder'
            ], 500);
        }
    }

    public function rename(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255'
            ]);

            $folder = Folder::findOrFail($id);
            
            // Check ownership
            if ($folder->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $folder->name = $request->name;
            $folder->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error renaming folder: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to rename folder'
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $searchQuery = $request->get('search');
            $folderId = $request->get('folder_id');
            $isRecursive = $request->boolean('recursive', false);
            
            // Get all descendant folder IDs if recursive search is enabled
            $searchableFolderIds = [];
            if ($isRecursive && $folderId) {
                $searchableFolderIds[] = $folderId;
                $this->getAllDescendantFolderIds($folderId, $searchableFolderIds);
            } else if ($folderId) {
                $searchableFolderIds[] = $folderId;
            }

            // Get folders based on search
            $foldersQuery = Folder::where('user_id', Auth::id())
                ->when($searchQuery, function($query) use ($searchQuery) {
                    $query->where('name', 'like', '%' . $searchQuery . '%');
                });

            // Apply folder filtering
            if (!empty($searchableFolderIds)) {
                $foldersQuery->whereIn('parent_id', $searchableFolderIds);
            } else if (!$isRecursive) {
                $foldersQuery->whereNull('parent_id');
            }

            $folders = $foldersQuery->orderBy('name')
                ->limit(5)
                ->get()
                ->map(function($folder) {
                    return [
                        'id' => $folder->id,
                        'name' => $folder->name
                    ];
                });

            // Get videos based on search
            $videosQuery = Video::where('user_id', Auth::id());

            // Add search conditions for videos
            if ($searchQuery) {
                $videosQuery->where(function($query) use ($searchQuery) {
                    $query->where('title', 'like', '%' . $searchQuery . '%')
                          ->orWhere('description', 'like', '%' . $searchQuery . '%')
                          ->orWhere('original_name', 'like', '%' . $searchQuery . '%');
                });
            }

            // Apply video filtering based on folders
            if (!empty($searchableFolderIds)) {
                $videosQuery->whereIn('folder_id', $searchableFolderIds);
            } else if (!$isRecursive && $folderId) {
                $videosQuery->where('folder_id', $folderId);
            } else if (!$isRecursive) {
                $videosQuery->whereNull('folder_id');
            }

            $videos = $videosQuery->latest()
                ->limit(5)
                ->get()
                ->map(function($video) {
                    return [
                        'id' => $video->id,
                        'title' => $video->title,
                        'thumbnail_url' => $video->thumbnail_url
                    ];
                });

            return response()->json([
                'folders' => $folders,
                'videos' => $videos
            ]);
            
        } catch (\Exception $e) {
            Log::error('Search error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'An error occurred while searching',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all descendant folder IDs recursively
     */
    private function getAllDescendantFolderIds($folderId, &$folderIds)
    {
        $children = Folder::where('parent_id', $folderId)->get();
        foreach ($children as $child) {
            $folderIds[] = $child->id;
            $this->getAllDescendantFolderIds($child->id, $folderIds);
        }
    }
}
