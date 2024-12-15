@foreach($videos as $video)
    <div class="video-card group relative overflow-hidden rounded-xl bg-[#242731]" data-video-id="{{ $video->id }}">
        <div class="aspect-w-16 aspect-h-9">
            <img 
                src="{{ $video->thumbnail_url }}" 
                alt="{{ $video->title }}" 
                class="h-full w-full object-cover"
            >
        </div>
        <div class="p-4">
            <h3 class="mb-2 text-lg font-semibold text-white">{{ $video->title }}</h3>
            <p class="text-sm text-gray-400">{{ Str::limit($video->description, 100) }}</p>
            <div class="mt-4 flex items-center justify-between">
                <span class="text-sm text-gray-500">{{ $video->created_at->diffForHumans() }}</span>
                <div class="flex items-center space-x-2">
                    <button onclick="moveVideo({{ $video->id }})" class="text-blue-500 hover:text-blue-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </button>
                    <button onclick="deleteVideo({{ $video->id }})" class="text-red-500 hover:text-red-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="absolute left-4 top-4">
            <input 
                type="checkbox" 
                class="video-checkbox h-5 w-5 rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500"
            >
        </div>
    </div>
@endforeach 