<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        $videos = Video::where('user_id', auth()->id())
            ->latest()
            ->paginate(100)
            ->through(function ($video) {
                $video->hls_url = $video->hls_path ? url("/storage/" . $video->hls_path) : null;
                return $video;
            });

        return view('dashboard', compact('videos'));
    }
}
