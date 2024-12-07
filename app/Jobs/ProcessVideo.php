<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 2; // Number of retries

    protected $video;

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle(VideoService $videoService)
    {
        try {
            $videoService->processVideo($this->video);
        } catch (\Exception $e) {
            $this->video->update([
                'status' => 'failed',
                'metadata' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        $this->video->update([
            'status' => 'failed',
            'metadata' => [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]
        ]);
    }
}
