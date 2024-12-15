<x-app-layout>
    <div class="min-h-screen bg-[#0f1015]">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-[#1a1c24] overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-white">
                        <div class="mb-6">
                            <h2 class="text-2xl font-bold">Edit Video</h2>
                        </div>

                        <!-- Video Preview -->
                        <div class="mb-8">
                            <div class="aspect-video rounded-lg overflow-hidden bg-black">
                                <video
                                    id="videoPlayer"
                                    class="w-full h-full"
                                    controls
                                    poster="{{ $video->thumbnail_url }}"
                                    playsinline
                                >
                                    <source src="{{ $video->hls_url }}" type="application/x-mpegURL">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        </div>

                        <form id="editForm" class="space-y-6" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            
                            <!-- Title -->
                            <div>
                                <x-input-label for="title" :value="__('Title')" class="text-white" />
                                <x-text-input 
                                    id="title" 
                                    name="title" 
                                    type="text" 
                                    class="mt-1 block w-full bg-[#242731] border-gray-700 text-white focus:border-blue-500 focus:ring-blue-500" 
                                    :value="$video->title"
                                    required 
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('title')" />
                            </div>

                            <!-- Description -->
                            <div>
                                <x-input-label for="description" :value="__('Description')" class="text-white" />
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    class="mt-1 block w-full bg-[#242731] border-gray-700 text-white focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    rows="4"
                                >{{ $video->description }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('description')" />
                            </div>

                            <!-- Folder Selection -->
                            <div>
                                <x-input-label for="folder_id" :value="__('Folder')" class="text-white mb-2" />
                                <div id="folder-select" class="bg-[#2a2c34] border border-gray-700 rounded-lg overflow-hidden">
                                    <!-- Folder tree will be inserted here -->
                                </div>
                                <input type="hidden" id="folder_id" name="folder_id" value="{{ $video->folder_id }}">
                            </div>

                            <!-- Thumbnail -->
                            <div>
                                <x-input-label for="thumbnail" :value="__('Thumbnail')" class="text-white" />
                                <div class="mt-2 flex items-center gap-4">
                                    <img src="{{ $video->thumbnail_url }}" alt="Current thumbnail" class="h-32 w-auto rounded">
                                    <div>
                                        <input 
                                            type="file" 
                                            id="thumbnail" 
                                            name="thumbnail" 
                                            accept="image/*"
                                            class="mt-1 block w-full text-sm text-white
                                                file:mr-4 file:py-2 file:px-4
                                                file:rounded-md file:border-0
                                                file:text-sm file:font-semibold
                                                file:bg-blue-600 file:text-white
                                                hover:file:bg-blue-500"
                                        />
                                        <p class="mt-1 text-sm text-gray-400">
                                            Optional. Upload a new thumbnail image (max 2MB)
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end gap-4">
                                <a 
                                    href="{{ url()->previous() }}" 
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Cancel
                                </a>
                                <button 
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        // Initialize HLS player
        const video = document.getElementById('videoPlayer');
        if (Hls.isSupported()) {
            const hls = new Hls();
            hls.loadSource('{{ $video->hls_url }}');
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, function() {
                video.play();
            });
        }
        // For browsers that support HLS natively (Safari)
        else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = '{{ $video->hls_url }}';
        }

        // Initialize folder tree
        function initializeFolderTree(containerId = 'folder-select', initialFolderId = null) {
            fetch('/folders/list')
                .then(response => response.json())
                .then(data => {
                    window.allFolders = data.folders;
                    const container = document.getElementById(containerId);
                    if (!container) return;

                    container.innerHTML = `
                        <div class="space-y-1 p-4 max-h-60 overflow-y-auto">
                            ${getRootFolderOption()}
                            ${buildFolderTree(data.folders.filter(f => !f.parent_id))}
                        </div>
                    `;

                    // Initialize folder toggles
                    initializeFolderToggles(container);

                    // Set initial folder if provided
                    if (initialFolderId) {
                        const radioButton = container.querySelector(`input[value="${initialFolderId}"]`);
                        if (radioButton) {
                            radioButton.checked = true;
                            // Expand parent folders
                            let parentFolder = radioButton.closest('.folder-content');
                            while (parentFolder) {
                                parentFolder.classList.remove('hidden');
                                const toggleButton = parentFolder.parentElement.querySelector('.folder-toggle');
                                if (toggleButton) {
                                    toggleButton.textContent = toggleButton.textContent.replace('►', '▼');
                                }
                                parentFolder = parentFolder.parentElement.closest('.folder-content');
                            }
                        }
                    }
                });
        }

        function buildFolderTree(folders, currentFolderId, isLastArray = []) {
            if (!Array.isArray(folders)) return '';
            
            return folders.map((folder, index, array) => {
                const children = window.allFolders.filter(f => f.parent_id === folder.id);
                const hasChildren = children.length > 0;
                const isLast = index === array.length - 1;
                
                // Create the tree line prefix
                const prefix = isLastArray.map(isLast => isLast ? '   ' : '│  ').join('');
                const linePrefix = isLast ? '└─' : '├─';
                const arrow = hasChildren ? '►' : '•';
                
                return `
                    <div class="folder-item">
                        <div class="flex items-center py-2 rounded-md hover:bg-gray-800/70 group transition-all duration-150 ease-in-out">
                            <div class="flex items-center flex-1 min-w-0">
                                <div class="flex items-center space-x-1 whitespace-pre font-mono text-sm min-w-0">
                                    <span class="text-gray-500/70 select-none font-light">${prefix}${linePrefix}</span>
                                    <span class="text-gray-400 select-none folder-toggle ${hasChildren ? 'hover:text-blue-400 cursor-pointer transition-colors duration-150' : ''}">${arrow}</span>
                                    <label class="flex flex-1 items-center cursor-pointer select-none min-w-0 pl-1">
                                        <input type="radio" name="folder_id" value="${folder.id}" 
                                            class="mr-2 text-blue-500 focus:ring-offset-0 focus:ring-1 focus:ring-blue-500/50 h-3.5 w-3.5 cursor-pointer 
                                            border-gray-600/50 bg-gray-700/50 checked:bg-blue-500">
                                        <span class="text-gray-300 group-hover:text-white transition-colors duration-150 text-sm truncate">
                                            ${folder.name}
                                        </span>
                                    </label>
                                </div>
                                ${hasChildren ? `
                                    <div class="flex items-center space-x-2 ml-3 text-[11px] text-gray-500 select-none shrink-0">
                                        <span class="flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                            </svg>
                                            ${folder.children_count}
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            ${folder.videos_count}
                                        </span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        ${hasChildren ? `
                            <div class="folder-content ml-[17px] hidden space-y-0.5">
                                ${buildFolderTree(children, currentFolderId, [...isLastArray, isLast])}
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
        }

        // Root folder option template
        function getRootFolderOption() {
            return `
                <div class="folder-item">
                    <div class="flex items-center py-2 rounded-md hover:bg-gray-800/70 group transition-all duration-150 ease-in-out">
                        <div class="flex items-center flex-1 min-w-0">
                            <div class="flex items-center space-x-1 whitespace-pre font-mono text-sm min-w-0">
                                <span class="text-gray-400 select-none">•</span>
                                <label class="flex flex-1 items-center cursor-pointer select-none min-w-0 pl-1">
                                    <input type="radio" name="folder_id" value="" 
                                        class="mr-2 text-blue-500 focus:ring-offset-0 focus:ring-1 focus:ring-blue-500/50 h-3.5 w-3.5 cursor-pointer 
                                        border-gray-600/50 bg-gray-700/50 checked:bg-blue-500">
                                    <span class="text-gray-300 group-hover:text-white transition-colors duration-150 text-sm truncate">
                                        Root Folder
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Initialize folder toggles
        function initializeFolderToggles(container) {
            // Add click handler for the folder items to expand/collapse
            const folderItems = container.querySelectorAll('.folder-item');
            folderItems.forEach(item => {
                const toggle = item.querySelector('.folder-toggle');
                const content = item.querySelector('.folder-content');
                
                if (content && toggle && toggle.textContent.includes('►')) {
                    toggle.style.cursor = 'pointer';
                    
                    toggle.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        if (content.classList.contains('hidden')) {
                            content.classList.remove('hidden');
                            toggle.textContent = toggle.textContent.replace('►', '▼');
                        } else {
                            content.classList.add('hidden');
                            toggle.textContent = toggle.textContent.replace('▼', '►');
                        }
                    });
                }
            });
        }

        // Initialize folder tree when page loads
        document.addEventListener('DOMContentLoaded', () => {
            initializeFolderTree('folder-select', '{{ $video->folder_id }}');
        });

        // Handle form submission
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = new FormData(this);
            
            try {
                const response = await fetch(`/videos/{{ $video->id }}`, {
                    method: 'POST',
                    body: form,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast('Video updated successfully');
                    setTimeout(() => window.location.href = '{{ url()->previous() }}', 1000);
                } else {
                    throw new Error(result.message || 'Failed to update video');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast(error.message || 'Failed to update video', 'error');
            }
        });

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
    </script>
    @endpush
</x-app-layout> 