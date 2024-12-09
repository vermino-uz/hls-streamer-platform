<x-app-layout>
    <div class="min-h-screen bg-[#0f1015] py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Video Player Section -->
            <div class="overflow-hidden rounded-xl bg-[#1a1c24] shadow-xl">
                <div class="relative">
                    <!-- Video Player -->
                    <div class="aspect-w-16 aspect-h-9">
                        <video
                            id="videoPlayer"
                            class="w-full"
                            controls
                            crossorigin
                            playsinline
                            poster="{{ Storage::url($video->thumbnail_path ?? '/videos/thumbnails/default.jpg') }}"
                        >
                            @if($video->status === 'completed' && $video->hls_path)
                                <source src="{{ Storage::url($video->hls_path) }}" type="application/x-mpegURL">
                            @endif
                            Your browser does not support the video tag.
                        </video>
                    </div>

                    <!-- Video Status Overlay (if not completed) -->
                    @if($video->status !== 'completed')
                        <div class="absolute inset-0 flex items-center justify-center bg-[#1a1c24]/95">
                            <div class="text-center">
                                <div class="mb-4 inline-block rounded-full bg-blue-600/10 p-4">
                                    <svg class="h-12 w-12 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-white">{{ ucfirst($video->status) }}</h3>
                                <p class="text-gray-400">Please wait while we process your video...</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Video Info -->
                <div class="border-t border-gray-800 bg-[#1a1c24] p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h1 class="text-2xl font-semibold text-white">{{ $video->title }}</h1>
                            <div class="mt-2 flex items-center space-x-4 text-sm text-gray-400">
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>{{ number_format($video->views) }} views</span>
                                </div>
                                <span class="text-gray-600">•</span>
                                <span>{{ $video->created_at->diffForHumans() }}</span>
                                @if($video->duration)
                                    <span class="text-gray-600">•</span>
                                    <span>{{ gmdate("H:i:s", $video->duration) }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-2">
                            @if($video->status === 'completed')
                                <button 
                                    onclick="copyToClipboard(this, '{{ $video->hls_url }}')"
                                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:bg-blue-500"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                    </svg>
                                    Copy HLS URL
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($video->description)
                        <div class="mt-6 rounded-xl bg-[#13151b] p-4">
                            <h3 class="text-lg font-semibold text-white">Description</h3>
                            <p class="mt-2 whitespace-pre-wrap text-gray-400">{{ $video->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Related Videos Section -->
            @if(isset($relatedVideos) && $relatedVideos->count() > 0)
                <div class="mt-8">
                    <h2 class="mb-6 text-xl font-semibold text-white">Related Videos</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($relatedVideos as $relatedVideo)
                            <x-video-card :video="$relatedVideo" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('videoPlayer');
            if (Hls.isSupported() && video.querySelector('source')) {
                const hls = new Hls({
                    maxLoadingDelay: 4,
                    maxBufferLength: 30,
                    enableWorker: true
                });
                hls.loadSource(video.querySelector('source').src);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, function() {
                    video.play();
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
