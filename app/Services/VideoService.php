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
    public function store($path, $title, $userId, $description = null, $folderId = null)
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
                'folder_id' => $folderId,
                'status' => 'pending',
                'slug' => Str::slug($title) . '-' . Str::random(6)
            ]);

            Log::info('Video record created', ['video_id' => $video->id]);

            // Dispatch the processing job immediately using sync driver
            try {
                ProcessVideoForHLS::dispatch($video);
                Log::info('Processing job completed', [
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

    public function processVideo(Video $video)
    {
        try {
            $inputPath = Storage::disk('public')->path($video->file_path);
            $uuid = basename(dirname($video->file_path));
            $outputPath = "videos/hls/{$uuid}";
            
            // Create output directory if it doesn't exist
            if (!Storage::disk('public')->exists($outputPath)) {
                Storage::disk('public')->makeDirectory($outputPath);
            }

            // Get video duration for thumbnail generation
            $duration = $this->getDuration($inputPath);
            $video->duration = $duration;
            $video->save();

            // Generate thumbnail at 1/3 of the video
            $thumbnailTime = $duration / 3;
            $thumbnailPath = "{$outputPath}/thumbnail.jpg";
            $this->generateThumbnail($inputPath, Storage::disk('public')->path($thumbnailPath), $thumbnailTime);
            $video->thumbnail_path = $thumbnailPath;

            // Convert video to HLS
            $playlistPath = "{$outputPath}/playlist.m3u8";
            $this->convertToHLS($inputPath, Storage::disk('public')->path($outputPath));
            $video->hls_path = $playlistPath;

            // Update video status
            $video->status = 'completed';
            $video->save();

            Log::info('Video processed successfully', [
                'video_id' => $video->id,
                'duration' => $duration,
                'thumbnail' => $thumbnailPath,
                'hls_path' => $playlistPath
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Video processing failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $video->status = 'failed';
            $video->save();

            throw $e;
        }
    }

    protected function getDuration($videoPath)
    {
        try {
            $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 \"{$videoPath}\" 2>&1";
            $duration = shell_exec($command);
            return floatval($duration);
        } catch (\Exception $e) {
            Log::error('Failed to get video duration', [
                'error' => $e->getMessage(),
                'video_path' => $videoPath
            ]);
            return 0;
        }
    }

    protected function generateThumbnail($inputPath, $outputPath, $time)
    {
        $command = "ffmpeg -i \"{$inputPath}\" -ss {$time} -vframes 1 -f image2 \"{$outputPath}\" 2>&1";
        shell_exec($command);
    }

    protected function convertToHLS($inputPath, $outputDir)
    {
        $command = "ffmpeg -i \"{$inputPath}\" -profile:v baseline -level 3.0 -start_number 0 -hls_time 10 -hls_list_size 0 -f hls \"{$outputDir}/playlist.m3u8\" 2>&1";
        shell_exec($command);
    }
}
