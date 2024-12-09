<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('OPTIONS')) {
            // Handle preflight request
            $response = response('', 200);
        } else {
            $response = $next($request);
        }

        // Essential CORS headers for streaming
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Range, Origin');
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Length, Content-Range, Accept-Ranges');
        $response->headers->set('Accept-Ranges', 'bytes');
        
        // Cache control for streaming files
        if ($request->path() !== '/') {
            $extension = pathinfo($request->path(), PATHINFO_EXTENSION);
            if ($extension === 'ts') {
                $response->headers->set('Cache-Control', 'public, max-age=86400'); // 24 hours for segments
            } elseif ($extension === 'm3u8') {
                $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');
            }
        }

        return $response;
    }
}
