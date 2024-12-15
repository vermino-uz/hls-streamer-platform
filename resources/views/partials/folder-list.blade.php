@foreach($folders as $folder)
    <div class="folder-item group flex items-center justify-between rounded-lg p-2 hover:bg-[#242731]">
        <a 
            href="{{ route('folders.index', ['folder_id' => $folder->id]) }}" 
            class="flex items-center space-x-2"
        >
            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
            </svg>
            <span class="text-white">{{ $folder->name }}</span>
        </a>
        <div class="hidden space-x-2 group-hover:flex">
            <button 
                onclick="moveFolder({{ $folder->id }})" 
                class="text-blue-500 hover:text-blue-400"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
            </button>
            <button 
                onclick="deleteFolder({{ $folder->id }})" 
                class="text-red-500 hover:text-red-400"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
    </div>
@endforeach 