<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ProcessVideoForHLS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $video;
    public $timeout = 3600;
    public $tries = 1;

    public function __construct(Video $video)
    {
        $this->video = $video;
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

    public function handle()
    {
        try {
            Log::info('Starting video processing', [
                'video_id' => $this->video->id,
                'path' => $this->video->file_path
            ]);

            $inputPath = Storage::disk('public')->path($this->video->file_path);
            
            if (!file_exists($inputPath)) {
                throw new \Exception("Input file not found at: {$inputPath}");
            }

            // Get video duration for progress calculation
            $duration = $this->getDuration($inputPath);
            $progressKey = "video_conversion_progress_{$this->video->id}";
            Cache::put($progressKey, 0, 3600);

            $uuid = basename(dirname($this->video->file_path));
            $outputDir = Storage::disk('public')->path("videos/hls/{$uuid}");
            
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $playlistPath = "{$outputDir}/playlist.m3u8";
            $segmentPattern = "{$outputDir}/segment_%03d.ts";

            // Enhanced FFmpeg command with progress tracking
            $command = "ffmpeg -i \"{$inputPath}\" ".
                      "-y ". // Overwrite output files
                      "-progress - ". // Output progress to stdout
                      "-c:v libx264 ". // Video codec
                      "-crf 23 ". // Constant Rate Factor (quality)
                      "-preset fast ". // Encoding speed preset
                      "-profile:v main ". // H.264 profile
                      "-level:v 3.1 ". // H.264 level
                      "-c:a aac ". // Audio codec
                      "-ar 48000 ". // Audio sample rate
                      "-b:a 128k ". // Audio bitrate
                      "-ac 2 ". // Audio channels
                      "-f hls ". // HLS format
                      "-hls_time 6 ". // Segment duration
                      "-hls_list_size 0 ". // Keep all segments
                      "-hls_segment_type mpegts ". // Segment type
                      "-hls_flags independent_segments ". // Independent segments
                      "-hls_playlist_type vod ". // VOD playlist
                      "-hls_segment_filename \"{$segmentPattern}\" ".
                      "\"{$playlistPath}\" 2>&1";

            Log::info('Executing FFmpeg command', [
                'command' => $command,
                'video_id' => $this->video->id
            ]);

            // Execute command and capture output with progress
            $process = popen($command, 'r');
            
            if ($process === false) {
                throw new \Exception("Failed to start FFmpeg process");
            }

            while (!feof($process)) {
                $line = fgets($process);
                
                // Parse progress information
                if (preg_match("/out_time_ms=(\d+)/", $line, $matches)) {
                    $currentTime = intval($matches[1]) / 1000000; // Convert microseconds to seconds
                    if ($duration > 0) {
                        $progress = min(99, ($currentTime / $duration) * 100); // Cap at 99% until fully complete
                        Cache::put($progressKey, round($progress), 3600);
                        
                        Log::info('Processing progress', [
                            'video_id' => $this->video->id,
                            'progress' => $progress,
                            'current_time' => $currentTime,
                            'duration' => $duration
                        ]);
                    }
                }
            }

            $exitCode = pclose($process);

            if ($exitCode !== 0) {
                throw new \Exception("FFmpeg process failed with exit code: {$exitCode}");
            }

            // Validate generated files
            if (!file_exists($playlistPath)) {
                throw new \Exception("Playlist file not created at: {$playlistPath}");
            }

            $segments = glob("{$outputDir}/segment_*.ts");
            $segmentCount = count($segments);

            if ($segmentCount === 0) {
                throw new \Exception("No segment files were created");
            }

            // Validate playlist content
            $playlistContent = file_get_contents($playlistPath);
            if (!$playlistContent || !str_contains($playlistContent, '#EXTM3U')) {
                throw new \Exception("Invalid playlist file generated");
            }

            // Validate first segment
            if (!empty($segments)) {
                $firstSegment = $segments[0];
                if (filesize($firstSegment) === 0) {
                    throw new \Exception("First segment file is empty");
                }
            }

            Log::info('Output files validated', [
                'segment_count' => $segmentCount,
                'playlist_size' => strlen($playlistContent),
                'first_segment_size' => filesize($segments[0]),
                'video_id' => $this->video->id
            ]);

            // Update video record
            $this->video->hls_path = "videos/hls/{$uuid}/playlist.m3u8";
            $this->video->status = 'completed';
            $this->video->save();

            // Set final progress
            Cache::put($progressKey, 100, 3600);

            // Generate thumbnail
            $this->generateThumbnail($inputPath, $uuid);

            Log::info('Video processing completed', [
                'video_id' => $this->video->id,
                'hls_path' => $this->video->hls_path,
                'segment_count' => $segmentCount
            ]);

        } catch (\Exception $e) {
            Log::error('Video processing failed', [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->video->status = 'failed';
            $this->video->save();

            // Set error progress
            Cache::put($progressKey, -1, 3600);

            throw $e;
        }
    }

    protected function generateThumbnail($videoPath, $uuid)
    {
        try {
            $thumbnailPath = Storage::disk('public')->path("thumbnails/{$uuid}.jpg");
            $thumbnailDir = dirname($thumbnailPath);
            
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0777, true);
            }

            $command = "ffmpeg -i \"{$videoPath}\" -ss 00:00:01 -vframes 1 \"{$thumbnailPath}\" 2>&1";
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($thumbnailPath)) {
                $this->video->thumbnail_path = "thumbnails/{$uuid}.jpg";
                $this->video->save();
                
                Log::info('Thumbnail generated successfully', [
                    'video_id' => $this->video->id,
                    'thumbnail_path' => $this->video->thumbnail_path
                ]);
            } else {
                Log::error('Thumbnail generation failed', [
                    'video_id' => $this->video->id,
                    'command' => $command,
                    'output' => $output
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed', [
                'error' => $e->getMessage(),
                'video_path' => $videoPath
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Job failed', [
            'video_id' => $this->video->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->video->status = 'failed';
        $this->video->save();

        // Set error progress
        Cache::put("video_conversion_progress_{$this->video->id}", -1, 3600);
    }
}