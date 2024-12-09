<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\VideoService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoController extends Controller
{
    protected $videoService;

    public function __construct(VideoService $videoService)
    {
        $this->videoService = $videoService;
    }

    public function index()
    {
        $videos = Video::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(9);

        return view('dashboard', compact('videos'));
    }

    /**
     * Display the specified video.
     */
    public function show(Video $video)
    {
        // Check if user has access to this video
        if ($video->user_id !== auth()->id()) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to view this video.');
        }

        return view('videos.show', compact('video'));
    }

    public function create()
    {
        return view('videos.upload');
    }

    public function store(Request $request)
    {
        // Increase memory limit and execution time for large uploads
        ini_set('memory_limit', '4G');
        set_time_limit(300);

        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo|max:4194304'
            ]);

            // Check if file was actually uploaded
            if (!$request->hasFile('video') || !$request->file('video')->isValid()) {
                throw new \Exception('No valid video file was uploaded');
            }

            $video = $this->videoService->store(
                $request->file('video'),
                $request->input('title'),
                auth()->id(),
                $request->input('description')
            );
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Video '{$video->title}' uploaded successfully!",
                    'video' => $video
                ]);
            }

            return redirect()->route('dashboard')->with('success', "Video '{$video->title}' uploaded successfully!");

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            Log::error('Video upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload video: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to upload video: ' . $e->getMessage());
        }
    }

    public function destroy(Video $video)
    {
        try {
            // Check if user owns the video
            if ($video->user_id !== auth()->id()) {
                return redirect()->route('dashboard')
                    ->with('error', 'You do not have permission to delete this video.');
            }

            $this->videoService->delete($video);

            return redirect()->route('dashboard')
                ->with('success', 'Video deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Video deletion failed in controller', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'Failed to delete video: ' . $e->getMessage());
        }
    }

    public function stream(Video $video)
    {
        // Check if user has access to this video
        if ($video->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $videoPath = Storage::disk('public')->path($video->file_path);
        $playlistPath = str_replace('.mp4', '.m3u8', $video->file_path);
        
        // If HLS playlist doesn't exist, create it
        if (!Storage::disk('public')->exists($playlistPath)) {
            $this->videoService->generateHLSPlaylist($video);
        }

        return response()->json([
            'hls_url' => Storage::disk('public')->url($playlistPath),
            'video' => $video
        ]);
    }

    public function segment(Request $request, $video_id, $segment)
    {
        $filePath = storage_path("app/public/videos/hls/{$video_id}/{$segment}");
        
        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->file($filePath, [
            'Content-Type' => 'video/MP2T',
            'Cache-Control' => 'public, max-age=86400'
        ]);
    }
}
