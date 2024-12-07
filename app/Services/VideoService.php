<?php

namespace App\Services;

use App\Models\Video;
use App\Jobs\ProcessVideo;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VideoService
{
    protected $ffmpeg;

    public function __construct()
    {
        $this->ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'timeout'          => 3600,
            'ffmpeg.threads'   => 12,
        ]);
    }

    public function store($file, $title, $userId, $description = null)
    {
        try {
            if (!$file || !$file->isValid()) {
                Log::error('Invalid file upload', [
                    'error' => $file ? $file->getErrorMessage() : 'No file provided',
                    'user_id' => $userId
                ]);
                throw new \Exception($file ? 'Invalid file: ' . $file->getErrorMessage() : 'No file provided');
            }

            // Log incoming request details
            Log::info('Video upload started', [
                'title' => $title,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            // Generate a UUID for the video
            $uuid = Str::uuid()->toString();
            $extension = $file->getClientOriginalExtension();
            $filename = $uuid . '.' . $extension;
            
            // Ensure directory exists
            $directory = 'videos/original';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            // Store the original video
            $path = $file->storeAs($directory, $filename, 'public');
            
            if (!$path || !Storage::disk('public')->exists($path)) {
                throw new \Exception('Failed to store video file');
            }

            Log::info('Video file stored', ['path' => $path]);

            // Create video record
            $video = Video::create([
                'title' => $title,
                'description' => $description,
                'user_id' => $userId,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'status' => 'pending',
                'slug' => Str::slug($title) . '-' . Str::random(6)
            ]);

            Log::info('Video record created', ['video_id' => $video->id]);

            // Generate thumbnail
            $thumbnailPath = $this->generateThumbnail($path, pathinfo($path, PATHINFO_FILENAME));
            Log::info('Thumbnail generated', ['thumbnail_path' => $thumbnailPath]);
            if ($thumbnailPath) {
                $video->thumbnail_path = $thumbnailPath;
            }
            $video->save();
            // Generate HLS segments
            $this->generateHLSSegments($video);

            // Log successful file storage
            Log::info('Video file stored successfully', [
                'video_id' => $video->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'user_id' => $userId
            ]);

            return $video;
        } catch (\Exception $e) {
            Log::error('Video storage failed', [
                'error' => $e->getMessage(),
                'file_name' => $file ? $file->getClientOriginalName() : 'unknown',
                'user_id' => $userId,
                'trace' => $e->getTraceAsString()
            ]);

            // Clean up any uploaded file if database insert fails
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }

    protected function generateThumbnail($videoPath, $filename)
    {
        try {
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => '/usr/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/bin/ffprobe'
            ]);

            $video = $ffmpeg->open(Storage::disk('public')->path($videoPath));
            
            // Generate thumbnail filename
            $thumbnailFilename = $filename . '.jpg';
            $thumbnailPath = 'videos/thumbnails/' . $thumbnailFilename;
            
            // Create thumbnails directory if it doesn't exist
            if (!Storage::disk('public')->exists('videos/thumbnails')) {
                Storage::disk('public')->makeDirectory('videos/thumbnails');
            }
            
            // Extract frame at 2 seconds
            $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(2))
                ->save(Storage::disk('public')->path($thumbnailPath));
            
            Log::info('Thumbnail saved', ['thumbnail_path' => $thumbnailPath]);
            
            return $thumbnailPath;
        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed', [
                'error' => $e->getMessage(),
                'video_path' => $videoPath
            ]);
            // If thumbnail generation fails, return a default thumbnail path
            return 'videos/thumbnails/default.jpg';
        }
    }

    protected function generateHLSSegments(Video $video)
    {
        try {
            $inputPath = Storage::disk('public')->path($video->file_path);
            $videoId = pathinfo($video->file_path, PATHINFO_FILENAME);
            $outputDir = Storage::disk('public')->path('videos/hls/' . $videoId);
            
            // Create output directory
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            // Update video status to processing
            $video->update(['status' => 'processing']);

            // Define quality levels
            $qualities = [
                '720' => [
                    'resolution' => '1280x720',
                    'bitrate' => '2800k'
                ],
                '480' => [
                    'resolution' => '854x480',
                    'bitrate' => '1400k'
                ],
                '360' => [
                    'resolution' => '640x360',
                    'bitrate' => '800k'
                ]
            ];

            // Create master playlist
            $masterPlaylist = "#EXTM3U\n#EXT-X-VERSION:3\n";
            
            // Process each quality
            foreach ($qualities as $quality => $settings) {
                $qualityDir = $outputDir . '/' . $quality;
                if (!file_exists($qualityDir)) {
                    mkdir($qualityDir, 0777, true);
                }

                // FFmpeg command for this quality
                $command = sprintf(
                    'ffmpeg -i %s ' .
                    '-vf scale=%s ' .        // Resolution
                    '-c:v libx264 ' .        // Video codec
                    '-preset fast ' .        // Encoding speed
                    '-crf 22 ' .            // Quality
                    '-c:a aac ' .           // Audio codec
                    '-b:a 128k ' .          // Audio bitrate
                    '-b:v %s ' .            // Video bitrate
                    '-hls_time 10 ' .       // Segment duration
                    '-hls_list_size 0 ' .   // Keep all segments
                    '-hls_segment_filename %s/segment_%%03d.ts ' . // Segment pattern
                    '-f hls ' .             // Format
                    '%s/playlist.m3u8',      // Output playlist
                    escapeshellarg($inputPath),
                    $settings['resolution'],
                    $settings['bitrate'],
                    escapeshellarg($qualityDir),
                    escapeshellarg($qualityDir)
                );

                // Execute FFmpeg command
                $process = proc_open($command, [
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ], $pipes);

                if (!is_resource($process)) {
                    throw new \Exception("Failed to start FFmpeg process for {$quality}p");
                }

                // Read and log output
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                
                $result = proc_close($process);
                
                if ($result !== 0) {
                    Log::error("FFmpeg failed for {$quality}p", [
                        'command' => $command,
                        'output' => $output,
                        'error' => $error,
                        'result' => $result
                    ]);
                    continue;
                }

                // Add to master playlist
                $bandwidth = intval(str_replace(['k', 'K'], '000', $settings['bitrate']));
                $masterPlaylist .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$settings['resolution']}\n";
                $masterPlaylist .= "{$quality}/playlist.m3u8\n";

                Log::info("Generated {$quality}p HLS stream", [
                    'video_id' => $video->id,
                    'quality' => $quality,
                    'resolution' => $settings['resolution']
                ]);
            }

            // Write master playlist
            file_put_contents($outputDir . '/master.m3u8', $masterPlaylist);

            // Get video duration
            $durationCmd = sprintf(
                'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
                escapeshellarg($inputPath)
            );
            $duration = (int)floatval(shell_exec($durationCmd));

            // Update video status and paths
            $video->update([
                'status' => 'completed',
                'hls_path' => 'videos/hls/' . $videoId . '/master.m3u8',
                'duration' => $duration
            ]);

            Log::info('Multi-quality HLS conversion completed', [
                'video_id' => $video->id,
                'output_dir' => $outputDir,
                'duration' => $duration,
                'qualities' => array_keys($qualities)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('HLS conversion failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $video->update(['status' => 'failed']);
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
