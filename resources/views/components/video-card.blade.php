@props(['video'])

<article class="group relative overflow-hidden rounded-xl bg-[#1a1c24] shadow-lg transition-transform duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/20">
    <div class="relative aspect-video">
        <!-- Thumbnail -->
        <img src="{{ $video->thumbnail_url ?? '/storage/thumbnails/default.jpg' }}" 
             alt="{{ $video->title }}" 
             class="h-full w-full object-cover"
        >
        
        <!-- Overlay with gradient -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
        
        <!-- Video Duration -->
        @if($video->duration)
            <div class="absolute bottom-2 right-2 flex items-center gap-1 rounded-md bg-black/80 px-2 py-1 text-xs">
                <svg class="h-3.5 w-3.5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                </svg>
                <span class="font-medium text-white">{{ gmdate("H:i:s", $video->duration) }}</span>
            </div>
        @endif
        
        <!-- Status Badge -->
        @if($video->status !== 'completed')
            <div class="absolute left-2 top-2 flex items-center gap-1.5 rounded-full bg-yellow-500/90 px-3 py-1">
                <svg class="h-3.5 w-3.5 text-yellow-900" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                </svg>
                <span class="font-semibold text-xs text-yellow-900">{{ ucfirst($video->status) }}</span>
            </div>
        @endif
        
        <!-- Play Button Overlay -->
        <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
            <a href="{{ route('videos.show', $video) }}" 
               class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition-all duration-300 hover:scale-110 hover:bg-blue-500">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8 5v14l11-7z"/>
                </svg>
            </a>
        </div>
    </div>

    <div class="p-4">
        <!-- Title and Description -->
        <div class="mb-4 space-y-2">
            <h3 class="text-lg font-semibold text-white line-clamp-1 group-hover:text-blue-400 transition-colors">
                {{ $video->title }}
            </h3>
            @if($video->description)
                <p class="text-sm text-gray-400 line-clamp-2">
                    {{ $video->description }}
                </p>
            @endif
        </div>

        <!-- Video Stats and Actions -->
        <div class="flex items-center justify-between border-t border-gray-800/80 pt-3">
            <div class="flex items-center gap-4">
                <!-- Views Count -->
                <div class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                        <path d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="text-xs font-medium text-gray-400">{{ number_format($video->views) }}</span>
                </div>
                
                <!-- Upload Date -->
                <div class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1z"/>
                    </svg>
                    <span class="text-xs font-medium text-gray-400">{{ $video->created_at->diffForHumans() }}</span>
                </div>
            </div>

            <!-- Copy URL Button -->
            @if($video->status === 'completed')
                <button 
                    onclick="copyToClipboard(this, '{{ $video->hls_url }}')"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600/10 px-3 py-1.5 text-xs font-medium text-blue-500 transition-colors hover:bg-blue-600/20"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                    </svg>
                    Copy URL
                </button>
            @endif
        </div>
    </div>
</article>

@once
    @push('scripts')
    <script>
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white px-4 py-2 rounded shadow-lg transition-opacity duration-500 z-50`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        function copyToClipboard(text) {
            return navigator.clipboard.writeText(text)
                .then(() => showToast('URL copied to clipboard!'))
                .catch(err => {
                    console.error('Failed to copy:', err);
                    showToast('Failed to copy URL', 'error');
                });
        }

        function copyVideoUrl(url) {
            copyToClipboard(url);
        }

        function copyHlsUrl(url) {
            copyToClipboard(url);
        }
    </script>
    @endpush
@endonce
