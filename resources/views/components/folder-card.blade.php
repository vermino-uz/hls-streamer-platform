@props(['folder'])

<div class="group relative overflow-hidden rounded-xl bg-[#1a1c24] shadow-lg transition-transform duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/20">
    <div class="p-4">
        <div class="flex items-center gap-3">
            <!-- Folder Icon -->
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600/10 text-blue-500">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 6h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 12H4V6h5.17l2 2H20v10z"/>
                </svg>
            </div>
            
            <!-- Folder Info -->
            <div class="flex-1 min-w-0">
                <a href="{{ route('folders.index', ['folder_id' => $folder->id]) }}" 
                   class="block">
                    <h3 class="text-lg font-semibold text-white truncate group-hover:text-blue-400 transition-colors">
                        {{ $folder->name }}
                    </h3>
                    <div class="flex items-center gap-3 text-sm text-gray-400">
                        <span>{{ $folder->children->count() }} folders</span>
                        <span>•</span>
                        <span>{{ $folder->videos->count() }} videos</span>
                    </div>
                </a>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <button 
                    onclick="moveFolder('{{ $folder->id }}')"
                    class="rounded p-1.5 text-gray-300 hover:bg-white/10 hover:text-blue-500"
                    title="Move"
                >
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                    </svg>
                </button>
                <button 
                    onclick="renameFolder('{{ $folder->id }}', '{{ $folder->name }}')"
                    class="rounded p-1.5 text-gray-300 hover:bg-white/10 hover:text-blue-500"
                    title="Rename"
                >
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                    </svg>
                </button>
                <button 
                    onclick="showDeleteModal('{{ $folder->id }}')"
                    class="rounded p-1.5 text-gray-300 hover:bg-white/10 hover:text-red-500"
                    title="Delete"
                >
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="delete-modal-{{ $folder->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
        <div class="inline-block transform overflow-hidden rounded-lg bg-[#1a1c24] text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
            <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="mb-4 text-lg font-medium text-white">Delete Folder</h3>
                <p class="text-gray-300 mb-4">How would you like to handle the folder contents?</p>
                
                <!-- Delete Options -->
                <div class="space-y-4">
                    <label class="flex items-center space-x-3 p-3 rounded-lg border border-gray-700 cursor-pointer hover:bg-gray-800/50">
                        <input type="radio" name="delete_option_{{ $folder->id }}" value="with_contents" class="text-blue-500">
                        <div>
                            <div class="text-white font-medium">Delete folder and all contents</div>
                            <div class="text-gray-400 text-sm">This will permanently delete the folder and all its contents (subfolders and videos)</div>
                        </div>
                    </label>
                    
                    <label class="flex items-center space-x-3 p-3 rounded-lg border border-gray-700 cursor-pointer hover:bg-gray-800/50">
                        <input type="radio" name="delete_option_{{ $folder->id }}" value="move_contents" class="text-blue-500">
                        <div>
                            <div class="text-white font-medium">Move contents and delete folder</div>
                            <div class="text-gray-400 text-sm">Move all contents to another folder before deleting this folder</div>
                        </div>
                    </label>
                </div>

                <!-- Destination Folder Selection (initially hidden) -->
                <div id="destination-select-{{ $folder->id }}" class="mt-4 hidden">
                    <label class="block text-sm font-medium text-white mb-2">Select Destination Folder</label>
                    <div id="folder-select-{{ $folder->id }}" class="bg-[#2a2c34] border border-gray-700 rounded-lg overflow-hidden">
                        <!-- Folder tree will be inserted here -->
                    </div>
                </div>
            </div>
            <div class="bg-[#2a2c34] px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button type="button" onclick="deleteFolderWithModal('{{ $folder->id }}')" class="delete-btn-{{ $folder->id }} inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                    Delete Folder
                </button>
                <button type="button" onclick="closeDeleteModal('{{ $folder->id }}')" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-700 bg-[#1a1c24] px-4 py-2 text-base font-medium text-gray-400 shadow-sm hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showDeleteModal(folderId) {
    const modal = document.getElementById(`delete-modal-${folderId}`);
    modal.classList.remove('hidden');

    // Initialize folder tree for destination selection
    initializeFolderTree(`folder-select-${folderId}`, folderId);

    // Add radio button change handlers
    const radioButtons = modal.querySelectorAll(`input[name="delete_option_${folderId}"]`);
    const destinationSelect = document.getElementById(`destination-select-${folderId}`);
    
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'move_contents') {
                destinationSelect.classList.remove('hidden');
            } else {
                destinationSelect.classList.add('hidden');
            }
        });
    });
}

function closeDeleteModal(folderId) {
    const modal = document.getElementById(`delete-modal-${folderId}`);
    modal.classList.add('hidden');
}

async function deleteFolderWithModal(folderId) {
    const modal = document.getElementById(`delete-modal-${folderId}`);
    const deleteOption = modal.querySelector(`input[name="delete_option_${folderId}"]:checked`)?.value;
    
    console.log('Starting folder deletion', {
        folderId,
        deleteOption
    });
    
    if (!deleteOption) {
        console.warn('No delete option selected');
        showToast('Please select a deletion option', 'error');
        return;
    }

    const deleteBtn = modal.querySelector(`.delete-btn-${folderId}`);
    deleteBtn.disabled = true;
    deleteBtn.classList.add('opacity-50', 'cursor-not-allowed');

    try {
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        formData.append('delete_option', deleteOption);
        
        if (deleteOption === 'move_contents') {
            const selectedFolder = modal.querySelector('input[name="folder_id"]:checked');
            if (!selectedFolder) {
                console.warn('No destination folder selected');
                showToast('Please select a destination folder', 'error');
                deleteBtn.disabled = false;
                deleteBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                return;
            }
            formData.append('destination_folder_id', selectedFolder.value);
            console.log('Moving contents to folder', selectedFolder.value);
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            throw new Error('CSRF token not found');
        }

        // Log the actual form data being sent
        const formDataObj = {};
        for (let [key, value] of formData.entries()) {
            formDataObj[key] = value;
        }
        console.log('Form data being sent:', formDataObj);

        const response = await fetch(`/folders/${folderId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            }
        });

        console.log('Received response', {
            status: response.status,
            statusText: response.statusText
        });

        const result = await response.json();
        console.log('Delete operation result', result);
        
        if (result.success) {
            showToast('Folder deleted successfully');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(result.message || 'Failed to delete folder');
        }
    } catch (error) {
        console.error('Error during folder deletion:', error);
        showToast(error.message || 'Failed to delete folder', 'error');
        deleteBtn.disabled = false;
        deleteBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

// Folder tree functions for destination selection
function initializeFolderTree(containerId, currentFolderId) {
    fetch('/folders/list')
        .then(response => response.json())
        .then(data => {
            window.allFolders = data.folders;
            const container = document.getElementById(containerId);
            if (!container) return;

            container.innerHTML = `
                <div class="space-y-1 p-4 max-h-60 overflow-y-auto">
                    ${buildFolderTree(data.folders.filter(f => !f.parent_id), currentFolderId)}
                </div>
            `;

            initializeFolderToggles(container);
        })
        .catch(error => {
            console.error('Error loading folder tree:', error);
            showToast('Failed to load folder structure', 'error');
        });
}

function buildFolderTree(folders, currentFolderId, isLastArray = []) {
    if (!Array.isArray(folders)) return '';
    
    return folders.map((folder, index, array) => {
        // Skip the current folder being deleted
        if (folder.id === parseInt(currentFolderId)) {
            return '';
        }

        const children = window.allFolders.filter(f => f.parent_id === folder.id);
        const hasChildren = children.length > 0;
        const isLast = index === array.length - 1;
        
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

function initializeFolderToggles(container) {
    const toggles = container.querySelectorAll('.folder-toggle');
    toggles.forEach(toggle => {
        if (toggle.textContent === '►') {
            toggle.addEventListener('click', function() {
                const folderItem = this.closest('.folder-item');
                const content = folderItem.querySelector('.folder-content');
                if (content) {
                    content.classList.toggle('hidden');
                    this.textContent = content.classList.contains('hidden') ? '►' : '▼';
                }
            });
        }
    });
}
</script>

@once
    @push('scripts')
    <script>
        // Toast notification functionality
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 z-50 rounded-lg px-4 py-2 text-sm font-medium text-white transform transition-all duration-300 opacity-0 translate-y-2 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);

            // Trigger animation
            setTimeout(() => {
                toast.classList.remove('opacity-0', 'translate-y-2');
            }, 10);

            // Remove toast after delay
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
    @endpush
@endonce 