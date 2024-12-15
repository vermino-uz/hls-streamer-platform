<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Log;
class HLSController extends Controller
{
    public function serve($uuid, $file): BinaryFileResponse
    {
        Log::info('HLS request received', ['uuid' => $uuid, 'file' => $file]);
        $path = "videos/hls/{$uuid}/{$file}";
        if($file === 'playlist.m3u8') {
            $video = Video::where('hls_path', $path)->first();
            if($video) {
                $video->views = $video->views + 1;
                $video->save();
            }
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }
        
        $filePath = Storage::disk('public')->path($path);
        
        // Set MIME type based on extension
        $mimetype = str_ends_with($file, '.m3u8') 
            ? 'application/vnd.apple.mpegurl'
            : 'video/MP2T';
        
        return response()->file($filePath, [
            'Content-Type' => $mimetype,
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'max-age=3600'
        ]);
    }
}
