<?php

namespace App\Services;

use App\Models\Video;
use App\Jobs\ProcessVideoForHLS;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class VideoService
{
    public function store($path, $title, $userId, $description = null)
    {
        try {
            if (!Storage::disk('public')->exists($path)) {
                Log::error('Video file not found', [
                    'path' => $path,
                    'user_id' => $userId
                ]);
                throw new \Exception('Video file not found');
            }

            // Log incoming request details
            Log::info('Video upload started', [
                'title' => $title,
                'path' => $path,
                'size' => Storage::disk('public')->size($path)
            ]);

            // Create video record
            $video = Video::create([
                'title' => $title,
                'description' => $description,
                'user_id' => $userId,
                'file_path' => $path,
                'status' => 'processing',
                'slug' => Str::slug($title) . '-' . Str::random(6)
            ]);

            Log::info('Video record created', ['video_id' => $video->id]);

            // Dispatch the processing job immediately using sync driver
            try {
                ProcessVideoForHLS::dispatchSync($video);
                Log::info('Processing job completed synchronously', [
                    'video_id' => $video->id,
                    'path' => $path
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to process video', [
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            return $video;

        } catch (\Exception $e) {
            Log::error('Video storage failed', [
                'error' => $e->getMessage(),
                'path' => $path,
                'user_id' => $userId,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Delete a video and all its associated files
     */
    public function delete(Video $video)
    {
        try {
            // Get the video ID from the file path
            $videoId = pathinfo($video->file_path, PATHINFO_FILENAME);
            
            // Delete original video file
            if (Storage::disk('public')->exists($video->file_path)) {
                Storage::disk('public')->delete($video->file_path);
            }

            // Delete HLS directory and all its contents
            $hlsPath = "videos/hls/{$videoId}";
            if (Storage::disk('public')->exists($hlsPath)) {
                Storage::disk('public')->deleteDirectory($hlsPath);
            }

            // Delete the database record
            $video->delete();

            Log::info('Video deleted successfully', [
                'video_id' => $video->id,
                'file_path' => $video->file_path,
                'hls_path' => $hlsPath
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete video', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
