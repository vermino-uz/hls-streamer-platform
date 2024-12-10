<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HLSController extends Controller
{
    public function serve($uuid, $file): BinaryFileResponse
    {
        $path = "videos/hls/{$uuid}/{$file}";
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
