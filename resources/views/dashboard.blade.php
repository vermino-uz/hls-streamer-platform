<x-app-layout>
    <div class="min-h-screen bg-[#0f1015]">


        <!-- Main Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if($videos->isEmpty())
                <!-- Empty State -->
                <div class="flex min-h-[400px] items-center justify-center rounded-xl bg-[#1a1c24] p-8">
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-600/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white">No videos yet</h3>
                        <p class="mt-2 text-gray-400">Get started by uploading your first video</p>
                        <a href="{{ route('videos.create') }}" 
                           class="group mt-6 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:bg-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Upload Video
                        </a>
                    </div>
                </div>
            @else
<!-- Video Grid -->
<div class="grid grid-cols-1 gap-x-2 gap-y-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @foreach($videos as $video)
        <div class="rounded-lg border border-gray-700 bg-[#1f2128] p-4 flex flex-col">
            <!-- Thumbnail -->
            <div class="relative" style="padding-top: 56.25%;">
                <img 
                    src="{{ Storage::url($video->thumbnail_path ?? 'thumbnails/default.jpg') }}" 
                    alt="Video thumbnail" 
                    class="absolute top-0 left-0 h-full w-full object-contain bg-black rounded"
                >
                <div class="absolute bottom-2 right-2 bg-black/70 px-2 py-1 text-xs text-white rounded">
                    {{ gmdate("H:i:s", $video->duration ?? 0) }}
                </div>
            </div>

            <!-- Details -->
            <div class="flex-1">
                <h3 class="mt-4 text-lg text-white truncate">{{ $video->title }}</h3>
                @if($video->description)
                    <p class="mt-2 text-sm text-gray-400 line-clamp-2">{{ $video->description }}</p>
                @endif
                
                <div class="mt-4 text-sm text-gray-400">
                    <p>{{ $video->created_at->diffForHumans() }}</p>
                    <p>Quality: {{ $video->quality ?? 'HD' }}</p>
                </div>
            </div>

            <!-- Actions - Fixed at bottom -->
            <div class="mt-4 flex gap-2">
                <a href="{{ route('videos.show', $video) }}" 
                   class="flex-1 rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-500 text-center">
                    Watch
                </a>

                @if($video->status === 'completed')
                    <button 
                        onclick="copyToClipboard(this, '{{ $video->hls_url }}')" 
                        class="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-500">
                        Copy URL
                    </button>
                @else
                    <span class="px-4 py-2 text-sm text-gray-400">
                        {{ ucfirst($video->status) }}...
                    </span>
                @endif

                <form action="{{ route('videos.destroy', $video) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-500"
                            onclick="return confirm('Delete this video?')">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    @endforeach
</div>


                <!-- Pagination -->
                <div class="mt-6">
                    {{ $videos->links() }}
                </div>
            @endif
        </main>
    </div>

    <!-- Copy to Clipboard Script -->
    <script>
        function copyToClipboard(button, text) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        }
    </script>
</x-app-layout>
