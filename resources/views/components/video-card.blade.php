@props(['video'])

<article 
    class="group relative overflow-hidden rounded-xl bg-[#1a1c24] shadow-lg transition-transform duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/20"
    data-status="{{ $video->status }}"
    data-video-id="{{ $video->id }}"
>
    <!-- Selection Checkbox -->
    <div class="absolute top-2 left-2 z-10">
        <input 
            type="checkbox" 
            id="video-{{ $video->id }}" 
            value="{{ $video->id }}"
            class="video-select-checkbox h-5 w-5 rounded border-gray-700 bg-[#242731] text-blue-500 focus:ring-blue-500"
            onchange="handleVideoSelection(this)"
        >
    </div>

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
                <svg class="h-3.5 w-3.5 text-yellow-900 animate-spin" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                </svg>
                <span class="font-semibold text-xs text-yellow-900 progress-text-{{ $video->id }}">{{ ucfirst($video->status) }}</span>
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

            <!-- Action Buttons Menu -->
            <div class="absolute top-2 right-2 flex items-center gap-2 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                <div class="flex items-center gap-1 rounded-lg bg-black/80 p-1 backdrop-blur">
                    <!-- Edit -->
                    <a 
                        href="{{ route('videos.edit', $video->id) }}"
                        class="rounded p-1.5 text-gray-300 hover:bg-white/10 hover:text-blue-500"
                        title="Edit Video"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                        </svg>
                    </a>

                    <!-- Move to Folder -->
                    <button 
                        onclick="moveVideo('{{ $video->id }}')"
                        class="rounded p-1.5 text-gray-300 hover:bg-white/10 hover:text-blue-500"
                        title="Move to Folder"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                        </svg>
                    </button>

                    <!-- Delete -->
                    <button 
                        onclick="deleteVideo('{{ $video->id }}', '{{ $video->title }}')"
                        class="rounded p-1.5 text-gray-300 hover:bg-white/10 hover:text-red-500"
                        title="Delete"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>

                @if($video->status === 'completed')
                    <button 
                        onclick="copyToClipboard('{{ $video->hls_url }}')"
                        class="flex items-center gap-1.5 rounded-lg bg-black/80 px-3 py-1.5 text-xs font-medium text-gray-300 hover:text-blue-500 backdrop-blur"
                        title="Copy URL"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                        URL
                    </button>
                @endif
            </div>
        </div>
    </div>
</article>

@once
    @push('scripts')
    <script>
        // Global toast function
        window.showToast = function(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white px-4 py-2 rounded shadow-lg transition-opacity duration-300 z-50`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Fade in
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
            });
            
            // Fade out and remove after 3 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        };

        function deleteVideo(videoId, title) {
            if (!confirm(`Are you sure you want to delete the video "${title}"?`)) {
                return;
            }

            fetch(`/videos/${videoId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    showToast('Video deleted successfully');
                    // Remove the video card from the DOM
                    const videoCard = document.querySelector(`[data-video-id="${videoId}"]`);
                    if (videoCard) {
                        videoCard.remove();
                    }
                } else {
                    throw new Error(result.message || 'Failed to delete video');
                }
            })
            .catch(error => {
                console.error('Error deleting video:', error);
                showToast(error.message || 'Failed to delete video', 'error');
            });
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text)
                .then(() => showToast('URL copied to clipboard'))
                .catch(err => {
                    console.error('Failed to copy:', err);
                    showToast('Failed to copy URL', 'error');
                });
        }

        function moveVideo(videoId) {
            fetch('/folders/list')
                .then(response => response.json())
                .then(data => {
                    window.allFolders = data.folders;
                    
                    const modal = document.createElement('div');
                    modal.className = 'fixed inset-0 z-50 overflow-y-auto';
                    modal.innerHTML = `
                        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                                <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
                            </div>
                            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
                            <div class="inline-block transform overflow-hidden rounded-lg bg-[#1a1c24] text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="mb-4 text-lg font-medium text-white">Move Video</h3>
                                    <div class="max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                                        <div id="move-video-tree" class="space-y-1">
                                            <!-- Folder tree will be inserted here -->
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-[#2a2c34] px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                    <button 
                                        type="button" 
                                        id="move-button"
                                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                                        onclick="submitMoveVideo('${videoId}')"
                                    >
                                        Move
                                    </button>
                                    <button 
                                        type="button" 
                                        onclick="closeMoveToFolderModal()" 
                                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-700 bg-[#1a1c24] px-4 py-2 text-base font-medium text-gray-400 shadow-sm hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);

                    // Initialize the move video tree after adding the modal
                    initializeFolderTree('move-video-tree');
                });
        }

        function submitMoveVideo(videoId) {
            const selectedFolder = document.querySelector('input[name="folder_id"]:checked');
            if (!selectedFolder) {
                showToast('Please select a destination folder', 'error');
                return;
            }

            const moveButton = document.getElementById('move-button');
            moveButton.disabled = true;
            moveButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Moving...
            `;

            fetch('/folders/move-videos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    video_ids: [videoId],
                    folder_id: selectedFolder.value || null
                })
            })
            .then(async response => {
                const result = await response.json();
                
                if (response.ok && result.success) {
                    // Remove modal first
                    const modal = document.querySelector('.fixed.inset-0.z-50');
                    if (modal) {
                        modal.remove();
                    }
                    
                    // Show success message
                    showToast('Video moved successfully');
                    
                    // Reload after a brief delay to ensure toast is visible
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    throw new Error(result.message || 'Failed to move video');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to move video: ' + error.message, 'error');
                // Re-enable button and restore original text
                moveButton.disabled = false;
                moveButton.innerHTML = 'Move';
            });
        }

        function buildFolderTree(folders, currentFolderId, isLastArray = [], disabledFolderIds = []) {
            if (!Array.isArray(folders)) return '';
            
            return folders.map((folder, index, array) => {
                const children = window.allFolders.filter(f => f.parent_id === folder.id);
                const hasChildren = children.length > 0;
                const isLast = index === array.length - 1;
                const isDisabled = disabledFolderIds.includes(folder.id);
                
                // Create the tree line prefix
                const prefix = isLastArray.map(isLast => isLast ? '   ' : '│  ').join('');
                const linePrefix = isLast ? '└─' : '├─';
                const arrow = hasChildren ? '►' : '•';
                
                return `
                    <div class="folder-item">
                        <div class="flex items-center py-2 rounded-md hover:bg-gray-800/70 group transition-all duration-150 ease-in-out ${isDisabled ? 'opacity-50' : ''}">
                            <div class="flex items-center flex-1 min-w-0">
                                <div class="flex items-center space-x-1 whitespace-pre font-mono text-sm min-w-0">
                                    <span class="text-gray-500/70 select-none font-light">${prefix}${linePrefix}</span>
                                    <span class="text-gray-400 select-none folder-toggle ${hasChildren ? 'hover:text-blue-400 cursor-pointer transition-colors duration-150' : ''}">${arrow}</span>
                                    <label class="flex flex-1 items-center ${isDisabled ? 'cursor-not-allowed' : 'cursor-pointer'} select-none min-w-0 pl-1">
                                        <input type="radio" name="folder_id" value="${folder.id}" 
                                            class="mr-2 text-blue-500 focus:ring-offset-0 focus:ring-1 focus:ring-blue-500/50 h-3.5 w-3.5 cursor-pointer 
                                            border-gray-600/50 bg-gray-700/50 checked:bg-blue-500"
                                            ${isDisabled ? 'disabled' : ''}>
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
                                ${buildFolderTree(children, currentFolderId, [...isLastArray, isLast], disabledFolderIds)}
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
        }

        // Helper function to get all descendant folder IDs
        function getDescendantFolderIds(folderId) {
            const descendants = [];
            const folder = window.allFolders.find(f => f.id === parseInt(folderId));
            if (!folder) return descendants;

            const children = window.allFolders.filter(f => f.parent_id === folder.id);
            children.forEach(child => {
                descendants.push(child.id);
                descendants.push(...getDescendantFolderIds(child.id));
            });

            return descendants;
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

        function closeMoveToFolderModal() {
            const modal = document.querySelector('.fixed.inset-0.z-50');
            if (modal) {
                modal.classList.add('transition-opacity', 'duration-300', 'opacity-0');
                setTimeout(() => modal.remove(), 300);
            }
        }

        // Global variables for selection
        window.selectedVideos = new Set();

        function handleVideoSelection(checkbox) {
            if (checkbox.checked) {
                selectedVideos.add(checkbox.value);
            } else {
                selectedVideos.delete(checkbox.value);
            }
            
            // Update bulk actions visibility
            updateBulkActionsVisibility();
        }

        function updateBulkActionsVisibility() {
            const bulkActions = document.getElementById('bulk-actions');
            if (bulkActions) {
                if (selectedVideos.size > 0) {
                    bulkActions.classList.remove('hidden');
                    // Update selected count
                    document.getElementById('selected-count').textContent = selectedVideos.size;
                } else {
                    bulkActions.classList.add('hidden');
                }
            }
        }

        // Bulk delete function
        function bulkDeleteVideos() {
            if (selectedVideos.size === 0) return;
            
            if (!confirm(`Are you sure you want to delete ${selectedVideos.size} selected videos?`)) {
                return;
            }

            const deleteButton = document.getElementById('bulk-delete-button');
            const originalText = deleteButton.innerHTML;
            deleteButton.disabled = true;
            deleteButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Deleting...
            `;

            fetch('/videos/bulk-delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    video_ids: Array.from(selectedVideos)
                })
            })
            .then(async response => {
                const result = await response.json();
                if (response.ok && result.success) {
                    showToast('Videos deleted successfully');
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    throw new Error(result.message || 'Failed to delete videos');
                }
            })
            .catch(error => {
                console.error('Error deleting videos:', error);
                showToast('Failed to delete videos: ' + error.message, 'error');
                deleteButton.disabled = false;
                deleteButton.innerHTML = originalText;
            });
        }

        // Bulk move function
        function bulkMoveVideos() {
            if (selectedVideos.size === 0) return;

            // Get folders from the server
            fetch('/folders/list')
                .then(response => response.json())
                .then(folders => {
                    // Initialize window.allFolders
                    window.allFolders = folders;
                    
                    // Create modal for folder selection
                    const modal = document.createElement('div');
                    modal.className = 'fixed inset-0 z-50 overflow-y-auto';
                    modal.innerHTML = `
                        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                                <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
                            </div>
                            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
                            <div class="inline-block transform overflow-hidden rounded-lg bg-[#1a1c24] text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="mb-4 text-lg font-medium text-white">Move ${selectedVideos.size} Videos to Folder</h3>
                                    <div class="max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                                        <div class="space-y-1">
                                            <!-- Root folder option -->
                                            <div class="folder-item">
                                                <label class="flex items-center p-2 rounded hover:bg-[#2a2c34] cursor-pointer">
                                                    <input type="radio" name="folder" value="" class="mr-3 text-blue-500 focus:ring-blue-500 h-4 w-4">
                                                    <span class="text-white flex items-center">
                                                        <svg class="w-4 h-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                                        </svg>
                                                        Root Folder
                                                    </span>
                                                </label>
                                            </div>
                                            <!-- Folder tree -->
                                            ${buildFolderTree(folders.filter(f => !f.parent_id), null, [], getDescendantFolderIds(folders[0].id))}
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-[#2a2c34] px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                    <button 
                                        type="button" 
                                        id="bulk-move-button"
                                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                                        onclick="submitBulkMove()"
                                    >
                                        Move
                                    </button>
                                    <button 
                                        type="button" 
                                        onclick="closeMoveToFolderModal()" 
                                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-700 bg-[#1a1c24] px-4 py-2 text-base font-medium text-gray-400 shadow-sm hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);

                    const toggleButtons = modal.querySelectorAll('.folder-toggle');
                    toggleButtons.forEach(button => {
                        button.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            const folderItem = button.closest('.folder-item');
                            const content = folderItem.querySelector('.folder-content');
                            const icon = button.querySelector('svg');
                            
                            if (content.classList.contains('hidden')) {
                                content.classList.remove('hidden');
                                icon.style.transform = 'rotate(90deg)';
                            } else {
                                content.classList.add('hidden');
                                icon.style.transform = 'rotate(0deg)';
                            }
                        });
                    });
                });
        }

        function submitBulkMove() {
            const selectedFolder = document.querySelector('input[name="folder_id"]:checked');
            if (!selectedFolder) {
                showToast('Please select a destination folder', 'error');
                return;
            }

            const folderId = selectedFolder.value;
            const moveButton = document.getElementById('bulk-move-button');
            
            // Disable button and show loading state
            moveButton.disabled = true;
            moveButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Moving...
            `;

            fetch('/folders/move-videos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    video_ids: Array.from(selectedVideos),
                    folder_id: folderId || null
                })
            })
            .then(async response => {
                const result = await response.json();
                
                if (response.ok && result.success) {
                    // Remove modal first
                    const modal = document.querySelector('.fixed.inset-0.z-50');
                    if (modal) {
                        modal.remove();
                    }
                    
                    // Show success message
                    showToast('Videos moved successfully');
                    
                    // Reload after a brief delay to ensure toast is visible
                    setTimeout(() => {
                        window.location = window.location.pathname + '?t=' + new Date().getTime();
                    }, 500);
                } else {
                    throw new Error(result.message || 'Failed to move videos');
                }
            })
            .catch(error => {
                console.error('Error moving videos:', error);
                showToast('Failed to move videos: ' + error.message, 'error');
                // Re-enable button and restore original text
                moveButton.disabled = false;
                moveButton.innerHTML = 'Move';
            });
        }

        function closeMoveToFolderModal() {
            const modal = document.querySelector('.fixed.inset-0.z-50');
            if (modal) {
                modal.classList.add('transition-opacity', 'duration-300', 'opacity-0');
                setTimeout(() => modal.remove(), 300);
            }
        }

        function initializeFolderTree(containerId = 'folder-select', initialFolderId = null, disabledFolderIds = []) {
            fetch('/folders/list')
                .then(response => response.json())
                .then(data => {
                    window.allFolders = data.folders;
                    const container = document.getElementById(containerId);
                    if (!container) return;

                    container.innerHTML = `
                        <div class="space-y-1">
                            ${getRootFolderOption()}
                            ${buildFolderTree(data.folders.filter(f => !f.parent_id), initialFolderId, [], disabledFolderIds)}
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

        // Initialize folder tree when page loads
        document.addEventListener('DOMContentLoaded', initializeFolderTree);

        // Initialize progress tracking for processing videos
        document.addEventListener('DOMContentLoaded', function() {
            const pendingVideos = document.querySelectorAll('[data-status="pending"]');
            const processingVideos = document.querySelectorAll('[data-status="processing"]');
            const allProcessingVideos = [...pendingVideos, ...processingVideos];
            allProcessingVideos.forEach(video => {
                const videoId = video.dataset.videoId;
                if (videoId) {
                    checkVideoProgress(videoId);
                }
            });
        });

        function checkVideoProgress(videoId) {
            const progressElement = document.querySelector(`.progress-text-${videoId}`);
            if (!progressElement) return;

            function updateProgress() {
                fetch(`https://media.maishare.uz/videos/${videoId}/conversion-progress`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'processing') {
                            progressElement.textContent = `Processing ${data.progress}%`;
                            setTimeout(updateProgress, 3000); // Check again in 3 seconds
                        } else if (data.status === 'completed') {
                            window.location.reload(); // Refresh page when complete
                        }
                    })
                    .catch(error => {
                        console.error('Error checking progress:', error);
                    });
            }

            updateProgress();
        }
    </script>
    @endpush
@endonce
