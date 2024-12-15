<x-app-layout>
    <div class="min-h-screen bg-[#0f1015] py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-6 flex items-center space-x-2 text-sm text-gray-400">
                <a href="{{ route('folders.index') }}" class="hover:text-blue-500">Home</a>
                @if($currentFolder)
                    @foreach($currentFolder->ancestors as $ancestor)
                        <span class="text-gray-600">/</span>
                        <a href="{{ route('folders.index', ['folder_id' => $ancestor->id]) }}" class="hover:text-blue-500">
                            {{ $ancestor->name }}
                        </a>
                    @endforeach
                    <span class="text-gray-600">/</span>
                    <span class="text-white">{{ $currentFolder->name }}</span>
                @endif
            </nav>

            <!-- Actions -->
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-white">
                    {{ $currentFolder ? $currentFolder->name : 'My Videos' }}
                </h1>
                <div class="flex items-center gap-4">
                    <!-- Search Bar and Results -->
                    <div class="relative" id="search-container">
                        <div class="flex flex-col gap-1">
                            <!-- Search Input -->
                            <div class="relative">
                                <input 
                                    type="text" 
                                    id="search-input"
                                    class="w-64 px-4 py-2 text-sm bg-[#1B1D24] border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                                    placeholder="Search folders and videos..."
                                >
                                <div class="absolute right-3 top-2.5 text-gray-400">
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Recursive Search Option -->
                            <label class="flex items-center px-1 text-sm text-gray-300 hover:text-white cursor-pointer">
                                <input type="checkbox" id="recursive-search" class="mr-2 text-blue-500 focus:ring-blue-500 h-3.5 w-3.5 cursor-pointer border-gray-600 bg-gray-700 checked:bg-blue-500">
                                Search in subfolders
                            </label>
                        </div>
                        
                        <!-- Search Results -->
                        <div id="search-results" class="absolute top-[85px] left-0 w-64 bg-[#1B1D24] border border-gray-700 rounded-lg shadow-lg overflow-hidden z-50 hidden">
                            <!-- Results Container -->
                            <div class="max-h-96 overflow-y-auto">
                                <!-- Folders Section -->
                                <div id="folder-results" class="p-2 border-b border-gray-700 hidden">
                                    <h4 class="px-2 py-1 text-xs font-semibold text-gray-400 uppercase">Folders</h4>
                                    <div id="folders-list" class="space-y-1"></div>
                                </div>
                                
                                <!-- Videos Section -->
                                <div id="video-results" class="p-2 hidden">
                                    <h4 class="px-2 py-1 text-xs font-semibold text-gray-400 uppercase">Videos</h4>
                                    <div id="videos-list" class="space-y-1"></div>
                                </div>
                                
                                <!-- No Results Message -->
                                <div id="no-results" class="px-4 py-3 text-sm text-gray-400 text-center hidden">
                                    No results found
                                </div>
                            </div>
                        </div>
                    </div>
                    <button 
                        onclick="createFolder()"
                        class="inline-flex items-center rounded-lg bg-blue-600/10 px-4 py-2 text-sm font-semibold text-blue-500 hover:bg-blue-600/20"
                    >
                        <svg class="mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                        </svg>
                        New Folder
                    </button>
                    <a 
                        href="{{ route('videos.create', ['folder_id' => $currentFolder?->id]) }}" 
                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:bg-blue-500"
                    >
                        <svg class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                        Upload Video
                    </a>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <div id="bulk-actions" class="fixed bottom-0 left-0 right-0 bg-[#1a1c24] border-t border-gray-800 p-4 transform transition-transform duration-300 ease-in-out hidden z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <span class="text-white">
                                <span id="selected-count">0</span> videos selected
                            </span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <button
                                onclick="bulkMoveVideos()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                </svg>
                                Move to Folder
                            </button>
                            <button
                                id="bulk-delete-button"
                                onclick="bulkDeleteVideos()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            >
                                <svg class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Delete Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Folders Grid -->
            @if($folders->isNotEmpty() || $videos->isNotEmpty())
                @if($folders->isNotEmpty())
                    <div class="mb-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($folders as $folder)
                            <x-folder-card :folder="$folder" />
                        @endforeach
                    </div>
                @endif

                <!-- Videos Grid -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($videos as $video)
                        <x-video-card :video="$video" />
                    @endforeach
                </div>
            @else
                <div class="col-span-full">
                    <div class="flex flex-col items-center justify-center rounded-xl bg-[#1a1c24] py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mb-4 h-16 w-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                        <h3 class="mb-2 text-xl font-semibold text-white">No Videos Here</h3>
                        <p class="mb-4 text-gray-400">Upload a video or create a folder to organize your content!</p>
                        <div class="flex items-center space-x-4">
                            <button 
                                onclick="createFolder()"
                                class="inline-flex items-center rounded-lg bg-blue-600/10 px-4 py-2 text-sm font-semibold text-blue-500 hover:bg-blue-600/20"
                            >
                                <svg class="mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                </svg>
                                New Folder
                            </button>
                            <a 
                                href="{{ route('videos.create', ['folder_id' => $currentFolder?->id]) }}" 
                                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:bg-blue-500"
                            >
                                <svg class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                                </svg>
                                Upload Video
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Pagination -->
            @if($videos->hasPages())
                <div class="mt-6">
                    {{ $videos->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Create/Edit Folder Modal -->
    <div id="folderModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
            <div class="inline-block transform overflow-hidden rounded-lg bg-[#1a1c24] text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <form id="folderForm" onsubmit="submitFolder(event)">
                    <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="mb-4 text-lg font-medium text-white" id="modalTitle">Create New Folder</h3>
                        <input type="hidden" id="folderId">
                        <input type="hidden" id="parentId" value="{{ $currentFolder?->id }}" name="folder_id">
                        <div>
                            <label for="folderName" class="block text-sm font-medium text-gray-400">Folder Name</label>
                            <input type="text" name="name" id="folderName" required
                                class="mt-1 block w-full rounded-md border-gray-700 bg-[#2a2c34] text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="Enter folder name">
                        </div>
                    </div>
                    <div class="bg-[#2a2c34] px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
                        </button>
                        <button type="button" onclick="closeModal()"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-700 bg-[#1a1c24] px-4 py-2 text-base font-medium text-gray-400 shadow-sm hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Add styles only if they haven't been added yet
        if (!document.getElementById('folder-tree-styles')) {
            const treeStyles = document.createElement('style');
            treeStyles.id = 'folder-tree-styles';
            treeStyles.textContent = `
                .folder-content {
                    position: relative;
                }
                .folder-content::before {
                    content: '';
                    position: absolute;
                    left: -1px;
                    top: 0;
                    bottom: 0;
                    width: 1px;
                    background-color: rgba(75, 85, 99, 0.5);
                }
                .folder-item:last-child > .folder-content::before {
                    height: 20px;
                }
                .folder-toggle svg {
                    transition: transform 0.2s ease-in-out;
                }
                .custom-scrollbar::-webkit-scrollbar {
                    width: 8px;
                }
                .custom-scrollbar::-webkit-scrollbar-track {
                    background: #1a1c24;
                    border-radius: 4px;
                }
                .custom-scrollbar::-webkit-scrollbar-thumb {
                    background: #2a2c34;
                    border-radius: 4px;
                }
                .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                    background: #3a3c44;
                }
            `;
            document.head.appendChild(treeStyles);
        }

        // Clipboard Management
        let clipboardItem = JSON.parse(localStorage.getItem('clipboardItem')) || null;
        let clipboardOperation = localStorage.getItem('clipboardOperation') || null;

        // Folder Management Functions
        function createFolder() {
            document.getElementById('modalTitle').textContent = 'Create New Folder';
            document.getElementById('folderForm').reset();
            document.getElementById('folderId').value = '';
            document.getElementById('folderModal').classList.remove('hidden');
        }

        function editFolder(id, name) {
            document.getElementById('modalTitle').textContent = 'Edit Folder';
            document.getElementById('folderId').value = id;
            document.getElementById('folderName').value = name;
            document.getElementById('folderModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('folderModal').classList.add('hidden');
        }

        async function submitFolder(event) {
            event.preventDefault();
            const form = event.target;
            const folderId = document.getElementById('folderId').value;
            const parentFolderId = document.getElementById('parentId').value;
            const name = document.getElementById('folderName').value;

            try {
                const response = await fetch(folderId ? `/folders/${folderId}` : '/folders', {
                    method: folderId ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        name, 
                        folder_id: parentFolderId 
                    })
                });

                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to save folder');
                }

                window.location.reload();
            } catch (error) {
                console.error('Error:', error);
                alert(error.message);
            }
        }

        // Clipboard Functions
        function updateClipboard(item, operation) {
            clipboardItem = item;
            clipboardOperation = operation;
            if (item && operation) {
                localStorage.setItem('clipboardItem', JSON.stringify(item));
                localStorage.setItem('clipboardOperation', operation);
            } else {
                localStorage.removeItem('clipboardItem');
                localStorage.removeItem('clipboardOperation');
            }
            updatePasteButton();
        }

        function clearClipboard() {
            updateClipboard(null, null);
        }

        function updatePasteButton() {
            const pasteButton = document.getElementById('pasteButton');
            if (pasteButton) {
                pasteButton.disabled = !clipboardItem;
                if (clipboardItem) {
                    pasteButton.title = `Paste ${clipboardItem.type} (${clipboardOperation})`;
                } else {
                    pasteButton.title = 'Nothing to paste';
                }
            }
        }

        function pasteItem() {
            if (!clipboardItem) return;
            
            const currentFolderId = '{{ $currentFolder?->id }}';
            
            if (clipboardItem.type === 'folder') {
                pasteFolder(currentFolderId);
            } else if (clipboardItem.type === 'video') {
                pasteVideo(currentFolderId);
            }
        }

        function pasteVideo(targetFolderId = null) {
            if (!clipboardItem || clipboardItem.type !== 'video') return;

            const endpoint = clipboardOperation === 'copy' ? '/videos/copy' : '/videos/move';
            
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    video_id: clipboardItem.id,
                    target_folder_id: targetFolderId
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(`Video ${clipboardOperation}ed successfully`);
                    if (clipboardOperation === 'cut') {
                        clearClipboard();
                    }
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(result.message || `Failed to ${clipboardOperation} video`);
                }
            })
            .catch(error => {
                console.error(`Error ${clipboardOperation}ing video:`, error);
                showToast(`Failed to ${clipboardOperation} video`, 'error');
            });
        }

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

            if ((e.ctrlKey || e.metaKey) && e.key === 'v') {
                e.preventDefault();
                pasteItem();
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', updatePasteButton);

        // Update the updateBulkActionsVisibility function
        function updateBulkActionsVisibility() {
            const bulkActions = document.getElementById('bulk-actions');
            if (bulkActions) {
                if (selectedVideos.size > 0) {
                    bulkActions.classList.remove('hidden');
                    bulkActions.style.transform = 'translateY(0)';
                    // Update selected count
                    document.getElementById('selected-count').textContent = selectedVideos.size;
                } else {
                    bulkActions.style.transform = 'translateY(100%)';
                    setTimeout(() => {
                        if (selectedVideos.size === 0) {
                            bulkActions.classList.add('hidden');
                        }
                    }, 300);
                }
            }
        }

        // Add this before your existing scripts
        let searchTimeout;
        const searchInput = document.getElementById('search-input');
        const searchResults = document.getElementById('search-results');
        const foldersList = document.getElementById('folders-list');
        const videosList = document.getElementById('videos-list');
        const noResults = document.getElementById('no-results');
        const folderResults = document.getElementById('folder-results');
        const videoResults = document.getElementById('video-results');
        
        // Show/hide search results
        searchInput.addEventListener('focus', () => {
            if (searchInput.value.trim()) {
                searchResults.classList.remove('hidden');
            }
        });

        document.addEventListener('click', (e) => {
            if (!searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.classList.add('hidden');
            }
        });
        
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const searchQuery = e.target.value.trim();
            
            if (!searchQuery) {
                searchResults.classList.add('hidden');
                return;
            }
            
            searchTimeout = setTimeout(() => {
                const currentFolderId = new URLSearchParams(window.location.search).get('folder_id');
                const url = `{{ route('folders.search') }}?search=${encodeURIComponent(searchQuery)}${currentFolderId ? '&folder_id=' + currentFolderId : ''}`;
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.classList.remove('hidden');
                        
                        // Update folders list
                        if (data.folders && data.folders.length > 0) {
                            foldersList.innerHTML = data.folders.map(folder => `
                                <a href="{{ route('folders.index') }}?folder_id=${folder.id}" 
                                   class="flex items-center px-3 py-2 text-white hover:bg-[#242731] rounded-lg">
                                    <svg class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                    </svg>
                                    ${folder.name}
                                </a>
                            `).join('');
                            folderResults.classList.remove('hidden');
                        } else {
                            folderResults.classList.add('hidden');
                        }
                        
                        // Update videos list
                        if (data.videos && data.videos.length > 0) {
                            videosList.innerHTML = data.videos.map(video => `
                                <a href="/videos/${video.id}" 
                                   class="flex items-center px-3 py-2 text-white hover:bg-[#242731] rounded-lg">
                                    <svg class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                    </svg>
                                    ${video.title}
                                </a>
                            `).join('');
                            videoResults.classList.remove('hidden');
                        } else {
                            videoResults.classList.add('hidden');
                        }
                        
                        // Show/hide no results message
                        if ((!data.folders || data.folders.length === 0) && (!data.videos || data.videos.length === 0)) {
                            noResults.classList.remove('hidden');
                        } else {
                            noResults.classList.add('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }, 300);
        });

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
            const urlParams = new URLSearchParams(window.location.search);
            const currentFolderId = urlParams.get('folder_id');
            initializeFolderTree('folder-select', currentFolderId);
        });

        function moveFolder(folderId) {
            fetch('/folders/list')
                .then(response => response.json())
                .then(data => {
                    window.allFolders = data.folders;
                    
                    // Get all descendant folder IDs to disable them
                    const disabledFolderIds = getDescendantFolderIds(folderId);
                    // Also disable the folder itself
                    disabledFolderIds.push(parseInt(folderId));
                    
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
                                    <h3 class="mb-4 text-lg font-medium text-white">Move Folder</h3>
                                    <div class="max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                                        <div id="move-folder-tree" class="space-y-1">
                                            <!-- Folder tree will be inserted here -->
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-[#2a2c34] px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                    <button 
                                        type="button" 
                                        id="move-button"
                                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                                        onclick="submitMoveFolder('${folderId}')"
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

                    // Initialize the move folder tree after adding the modal
                    initializeFolderTree('move-folder-tree', null, disabledFolderIds);
                });
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

        function bulkMoveVideos() {
            if (selectedVideos.size === 0) {
                showToast('Please select videos to move', 'error');
                return;
            }

            fetch('/folders/list')
                .then(response => response.json())
                .then(data => {
                    window.allFolders = data.folders;
                    
                    // Get current folder ID from URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const currentFolderId = urlParams.get('folder_id');
                    
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
                                    <h3 class="mb-4 text-lg font-medium text-white">Move Videos</h3>
                                    <div class="max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                                        <div id="move-videos-tree" class="space-y-1">
                                            <!-- Folder tree will be inserted here -->
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-[#2a2c34] px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                    <button 
                                        type="button" 
                                        id="move-button"
                                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                                        onclick="submitBulkMoveVideos(Array.from(selectedVideos))"
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
                    
                    // Initialize the move videos tree after adding the modal
                    initializeFolderTree('move-videos-tree', currentFolderId);
                });
        }

        function submitMoveFolder(folderId) {
            console.log('Starting submitMoveFolder with folderId:', folderId);

            const selectedFolder = document.querySelector('input[name="folder_id"]:checked');
            console.log('Selected destination folder:', selectedFolder?.value);

            if (!selectedFolder) {
                console.log('No destination folder selected');
                showToast('Please select a destination folder', 'error');
                return;
            }

            const moveButton = document.getElementById('move-button');
            console.log('Found move button:', moveButton);
            moveButton.disabled = true;
            moveButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Moving...
            `;

            console.log('Preparing fetch request data:', {
                folder_id: folderId,
                parent_id: selectedFolder.value || null
            });

            fetch('/folders/move', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    folder_id: folderId,
                    parent_id: selectedFolder.value || null
                })
            })
            .then(async response => {
                console.log('Received response:', response);
                const result = await response.json();
                console.log('Parsed response data:', result);
                
                if (response.ok && result.success) {
                    console.log('Move operation successful');
                    // Remove modal first
                    const modal = document.querySelector('.fixed.inset-0.z-50');
                    console.log('Found modal to remove:', modal);
                    if (modal) {
                        modal.remove();
                    }
                    
                    // Show success message
                    showToast('Folder moved successfully');
                    
                    // Reload after a brief delay to ensure toast is visible
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    console.log('Move operation failed with result:', result);
                    throw new Error(result.message || 'Failed to move folder');
                }
            })
            .catch(error => {
                console.error('Error in move operation:', error);
                showToast('Failed to move folder: ' + error.message, 'error');
                // Re-enable button and restore original text
                moveButton.disabled = false;
                moveButton.innerHTML = 'Move';
            });
        }

        function submitBulkMoveVideos(videoIds) {
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
                    video_ids: videoIds,
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
                    showToast('Videos moved successfully');
                    
                    // Reload after a brief delay to ensure toast is visible
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    throw new Error(result.message || 'Failed to move videos');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to move videos: ' + error.message, 'error');
                // Re-enable button and restore original text
                moveButton.disabled = false;
                moveButton.innerHTML = 'Move';
            });
        }

        // Update search function
        function performSearch() {
            const searchInput = document.getElementById('search-input');
            const searchResults = document.getElementById('search-results');
            const searchQuery = searchInput.value.trim();
            const recursiveSearch = document.getElementById('recursive-search').checked;
            const urlParams = new URLSearchParams(window.location.search);
            const currentFolderId = urlParams.get('folder_id');
            
            // Always show search results container when searching
            searchResults.classList.remove('hidden');
            
            if (searchQuery === '') {
                hideSearchResults();
                return;
            }

            // Show loading state
            const noResults = document.getElementById('no-results');
            noResults.classList.remove('hidden');
            noResults.innerHTML = `
                <div class="flex items-center justify-center py-4">
                    <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-2 text-gray-400">Searching...</span>
                </div>
            `;

            // Build search URL
            const searchParams = new URLSearchParams();
            searchParams.append('search', searchQuery);
            if (currentFolderId) {
                searchParams.append('folder_id', currentFolderId);
            }
            searchParams.append('recursive', recursiveSearch);

            fetch(`/folders/search?${searchParams.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                updateSearchResults(data);
            })
            .catch(error => {
                console.error('Search error:', error);
                noResults.innerHTML = `
                    <div class="flex items-center justify-center py-4 text-red-500">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="ml-2">Error performing search. Please try again.</span>
                    </div>
                `;
                noResults.classList.remove('hidden');
            });
        }

        function updateSearchResults(data) {
            const folderResults = document.getElementById('folder-results');
            const videoResults = document.getElementById('video-results');
            const foldersList = document.getElementById('folders-list');
            const videosList = document.getElementById('videos-list');
            const noResults = document.getElementById('no-results');
            
            // Update folders list
            if (data.folders && data.folders.length > 0) {
                foldersList.innerHTML = data.folders.map(folder => `
                    <a href="/folders?folder_id=${folder.id}" 
                       class="flex items-center px-3 py-2 text-white hover:bg-gray-800/50 rounded-lg">
                        <svg class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                        </svg>
                        ${folder.name}
                    </a>
                `).join('');
                folderResults.classList.remove('hidden');
            } else {
                folderResults.classList.add('hidden');
            }
            
            // Update videos list
            if (data.videos && data.videos.length > 0) {
                videosList.innerHTML = data.videos.map(video => `
                    <a href="/videos/${video.id}" 
                       class="flex items-center px-3 py-2 text-white hover:bg-gray-800/50 rounded-lg">
                        <svg class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="truncate">${video.title}</p>
                        </div>
                    </a>
                `).join('');
                videoResults.classList.remove('hidden');
            } else {
                videoResults.classList.add('hidden');
            }
            
            // Show/hide no results message
            if (!data.folders.length && !data.videos.length) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }

            // For debugging
            console.log('Search Results:', {
                folders: data.folders.length,
                videos: data.videos.length,
                data: data
            });
        }

        function hideSearchResults() {
            const searchResults = document.getElementById('search-results');
            const folderResults = document.getElementById('folder-results');
            const videoResults = document.getElementById('video-results');
            const noResults = document.getElementById('no-results');
            
            searchResults.classList.add('hidden');
            folderResults.classList.add('hidden');
            videoResults.classList.add('hidden');
            noResults.classList.add('hidden');
        }

        // Initialize search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchContainer = document.getElementById('search-container');
            const searchInput = document.getElementById('search-input');
            const recursiveCheckbox = document.getElementById('recursive-search');
            let searchTimeout;

            // Handle input changes
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 300);
            });

            // Handle recursive checkbox changes
            recursiveCheckbox.addEventListener('change', performSearch);

            // Handle clicks outside search container
            document.addEventListener('click', function(e) {
                if (!searchContainer.contains(e.target)) {
                    hideSearchResults();
                }
            });

            // Show results when focusing on input
            searchInput.addEventListener('focus', function() {
                if (this.value.trim() !== '') {
                    document.getElementById('search-results').classList.remove('hidden');
                }
            });
        });

        function closeMoveToFolderModal() {
            const modal = document.querySelector('.fixed.inset-0.z-50');
            if (modal) {
                modal.classList.add('transition-opacity', 'duration-300', 'opacity-0');
                setTimeout(() => modal.remove(), 300);
            }
        }

        function renameFolder(id, currentName) {
            // Show the folder modal with rename mode
            document.getElementById('modalTitle').textContent = 'Rename Folder';
            document.getElementById('folderId').value = id;
            document.getElementById('folderName').value = currentName;
            document.getElementById('folderModal').classList.remove('hidden');
        }
    </script>
    @endpush
</x-app-layout> 