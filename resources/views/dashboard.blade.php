<x-app-layout>
    <div class="min-h-screen bg-[#0f1015] py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-white">My Videos</h1>
                <a href="{{ route('videos.create') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:bg-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Upload Video
                </a>
            </div>

            <!-- Videos Grid -->
            <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($videos as $video)
                    <div class="group relative overflow-hidden rounded-xl bg-[#1a1c24] shadow-xl transition-all duration-300 hover:shadow-2xl">
                        <!-- Thumbnail -->
                        <div class="relative aspect-video">
                            <img 
                                src="{{ Storage::url($video->thumbnail_path ?? 'videos/thumbnails/default.jpg') }}" 
                                alt="{{ $video->title }}"
                                class="h-full w-full object-cover"
                            >
                            
                            <!-- Status Overlay -->
                            @if($video->status !== 'completed')
                                <div class="absolute inset-0 flex items-center justify-center bg-black/75">
                                    <div class="text-center">
                                        @if($video->status === 'processing')
                                            <div class="mb-2 inline-block rounded-full bg-blue-600/10 p-3">
                                                <svg class="h-6 w-6 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                            <span class="text-sm font-medium text-blue-500">Processing...</span>
                                        @elseif($video->status === 'failed')
                                            <div class="mb-2 inline-block rounded-full bg-red-600/10 p-3">
                                                <svg class="h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <span class="text-sm font-medium text-red-500">Processing Failed</span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <!-- Play Button Overlay -->
                                <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                    <div class="rounded-full bg-white/10 p-3 backdrop-blur-sm transition-transform duration-300 group-hover:scale-110">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Video Info -->
                        <div class="p-4">
                            <h3 class="mb-1 text-lg font-semibold text-white">
                                <a href="{{ route('videos.show', $video) }}" class="hover:text-blue-500">
                                    {{ $video->title }}
                                </a>
                            </h3>
                            <div class="flex items-center space-x-4 text-sm text-gray-400">
                                <div class="flex items-center space-x-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>{{ number_format($video->views) }}</span>
                                </div>
                                <span>{{ $video->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex border-t border-gray-800 divide-x divide-gray-800">
                            <a href="{{ route('videos.show', $video) }}" class="flex flex-1 items-center justify-center py-3 text-sm font-medium text-gray-400 transition-colors duration-300 hover:bg-gray-800 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View
                            </a>
                            <form action="{{ route('videos.destroy', $video) }}" method="POST" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex w-full items-center justify-center py-3 text-sm font-medium text-gray-400 transition-colors duration-300 hover:bg-red-600/10 hover:text-red-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="flex flex-col items-center justify-center rounded-xl bg-[#1a1c24] py-12">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mb-4 h-16 w-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                            </svg>
                            <h3 class="mb-2 text-xl font-semibold text-white">No Videos Yet</h3>
                            <p class="mb-4 text-gray-400">Start by uploading your first video!</p>
                            <a href="{{ route('videos.create') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:bg-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Upload Video
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($videos->hasPages())
                <div class="mt-6">
                    {{ $videos->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
