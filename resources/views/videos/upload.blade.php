<x-app-layout>
    <div class="min-h-screen bg-[#0f1015] py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-white">Upload Video</h1>
            </div>

            <div class="mt-6">
                <div class="bg-[#1a1c24] rounded-lg p-6">
                    <form id="upload-form" method="POST" action="{{ route('videos.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        @if ($errors->any())
                        <div class="bg-red-900/50 border border-red-500 text-white px-4 py-3 rounded relative mb-4">
                            <strong class="font-bold">Error!</strong>
                            <ul class="mt-2">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        <!-- Title Input -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-white">Title</label>
                            <input type="text" name="title" id="title" class="mt-1 block w-full bg-[#2a2c34] border border-gray-700 rounded-lg shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Description Input -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-white">Description</label>
                            <textarea name="description" id="description" rows="3" class="mt-1 block w-full bg-[#2a2c34] border border-gray-700 rounded-lg shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>

                        <!-- File Input -->
                        <div>
                            <label for="video" class="block text-sm font-medium text-white">Video File</label>
                            <div id="drop-zone" class="mt-1 relative border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-500 transition-colors duration-200">
                                <input type="file" id="video" name="video" accept="video/*"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                    required
                                    onchange="updateFileName(this)" />
                                <div class="space-y-2">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4-4m4-12h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="text-sm text-white">
                                        <span class="font-medium">Click to upload</span> or drag and drop
                                    </div>
                                    <p id="file-name" class="text-sm text-white mt-2 hidden"></p>
                                    <p class="text-xs text-gray-400">
                                        Supported formats: MP4, MOV, AVI (Max size: 4GB)
                                    </p>
                                </div>
                            </div>
                        </div>
                        <!-- Folder Selection -->
                        <div>
                            <label class="block text-sm font-medium text-white mb-2">Select Folder</label>
                            <div id="folder-select" class="bg-[#2a2c34] border border-gray-700 rounded-lg overflow-hidden">
                                <!-- Folder tree will be inserted here -->
                            </div>
                            <input type="hidden" id="folder_id" name="folder_id" value="{{ request()->query('folder_id') }}">
                        </div>

                        <!-- Upload Progress -->
                        <div id="upload-progress" class="hidden">
                            <div class="mb-2">
                                <p class="text-sm font-medium text-white">Upload Progress</p>
                                <div class="w-full bg-gray-700 rounded-full h-2.5">
                                    <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                                </div>
                                <p id="progress-text" class="mt-2 text-sm text-white">0% uploaded</p>
                            </div>

                            <!-- HLS Conversion Progress -->
                            <div id="conversion-progress" class="hidden mt-4">
                                <p class="text-sm font-medium text-white">Video Processing</p>
                                <div class="w-full bg-gray-700 rounded-full h-2.5">
                                    <div id="conversion-progress-bar" class="bg-green-600 h-2.5 rounded-full" style="width: 0%"></div>
                                </div>
                                <p id="conversion-progress-text" class="mt-2 text-sm text-white">0% processed</p>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end">
                            <button type="submit" id="submit-button" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Upload Video') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function updateFileName(input) {
            const fileNameElement = document.getElementById('file-name');
            if (input.files && input.files[0]) {
                fileNameElement.textContent = 'Selected file: ' + input.files[0].name;
                fileNameElement.classList.remove('hidden');
            } else {
                fileNameElement.classList.add('hidden');
            }
        }
        let conversionCheckInterval = null;

        function checkConversionProgress(videoId) {
            fetch(`/videos/${videoId}/conversion-progress`, {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                        throw new Error('Session expired');
                    }
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const conversionProgress = document.getElementById('conversion-progress');
                    const conversionProgressBar = document.getElementById('conversion-progress-bar');
                    const conversionProgressText = document.getElementById('conversion-progress-text');

                    conversionProgress.classList.remove('hidden');
                    conversionProgressBar.style.width = data.progress + '%';
                    conversionProgressText.textContent = data.progress + '% processed';

                    if (data.error) {
                        clearInterval(conversionCheckInterval);
                        conversionProgressText.textContent = 'Error processing video';
                        conversionProgressText.classList.add('text-red-500');
                    } else if (data.complete) {
                        clearInterval(conversionCheckInterval);
                        conversionProgressText.textContent = 'Processing complete!';
                        setTimeout(() => {
                            window.location.href = "{{ route('dashboard') }}";
                        }, 1000);
                    }
                })
                .catch(error => {
                    console.error('Error checking conversion progress:', error);
                    if (error.message !== 'Session expired') {
                        clearInterval(conversionCheckInterval);
                        const conversionProgressText = document.getElementById('conversion-progress-text');
                        conversionProgressText.textContent = 'Error checking progress';
                        conversionProgressText.classList.add('text-red-500');
                    }
                });
        }

        document.getElementById('upload-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const form = e.target;
            const file = document.getElementById('video').files[0];
            const submitButton = document.getElementById('submit-button');
            const progressDiv = document.getElementById('upload-progress');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const folderId = document.getElementById('folder_id').value;

            if (!file) {
                alert('Please select a video file');
                return;
            }

            // Show progress bar and disable submit button
            progressDiv.classList.remove('hidden');
            submitButton.disabled = true;
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');

            try {
                // Generate UUID for this upload
                const uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    const r = Math.random() * 16 | 0;
                    const v = c == 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });

                // Calculate optimal chunk size based on file size
                let chunkSize = 5 * 1024 * 1024; // Start with 5MB
                if (file.size > 200 * 1024 * 1024) { // If file is larger than 200MB
                    chunkSize = 10 * 1024 * 1024; // Use 10MB chunks
                }
                const totalChunks = Math.ceil(file.size / chunkSize);
                let uploadedChunks = 0;
                let uploadedBytes = 0;
                let retryCount = 0;
                const maxRetries = 3;

                // Upload chunks with retry mechanism
                for (let chunkNumber = 0; chunkNumber < totalChunks; chunkNumber++) {
                    let chunkUploaded = false;
                    retryCount = 0;

                    while (!chunkUploaded && retryCount < maxRetries) {
                        try {
                            const start = chunkNumber * chunkSize;
                            const end = Math.min(start + chunkSize, file.size);
                            const chunk = file.slice(start, end);

                            const formData = new FormData();
                            formData.append('file', chunk);
                            formData.append('chunkNumber', chunkNumber);
                            formData.append('totalChunks', totalChunks);
                            formData.append('uuid', uuid);
                            formData.append('originalName', file.name);

                            const response = await fetch("{{ route('videos.upload-chunk') }}", {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                credentials: 'same-origin'
                            });

                            if (response.redirected) {
                                window.location.href = response.url;
                                throw new Error('Session expired');
                            }

                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }

                            const result = await response.json();

                            if (result.session_expired) {
                                window.location.href = "{{ route('login') }}";
                                throw new Error('Session expired');
                            }

                            if (!result.success) {
                                throw new Error(result.message || 'Upload failed');
                            }

                            uploadedChunks++;
                            uploadedBytes += chunk.size;
                            chunkUploaded = true;

                            // Update progress
                            const percent = Math.round((uploadedBytes / file.size) * 100);
                            progressBar.style.width = percent + '%';
                            progressText.textContent = `${percent}% uploaded (${uploadedChunks}/${totalChunks} chunks)`;

                            // If all chunks are uploaded, submit the form with video details
                            if (result.complete) {
                                // Handle final form submission
                                const finalFormData = new FormData();
                                finalFormData.append('folder_id', folderId);
                                finalFormData.append('path', result.path);
                                finalFormData.append('title', document.getElementById('title').value);
                                finalFormData.append('description', document.getElementById('description').value);

                                try {
                                    const finalResponse = await fetch("{{ route('videos.store') }}", {
                                        method: 'POST',
                                        body: finalFormData,
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        }
                                    });

                                    const finalResult = await finalResponse.json();

                                    if (finalResult.success) {
                                        // Show success message
                                        progressText.textContent = 'Upload complete! Processing video...';
                                        progressText.classList.remove('text-red-500');
                                        progressText.classList.add('text-green-500');

                                        // Redirect to current folder page
                                        window.location.href = "{{ route('folders.index', ['folder_id' => request()->query('folder_id')]) }}";
                                    } else {
                                        throw new Error(finalResult.message || 'Failed to process video');
                                    }
                                } catch (error) {
                                    console.error('Final submission failed:', error);
                                    progressText.textContent = 'Upload failed: ' + error.message;
                                    progressText.classList.remove('text-green-500');
                                    progressText.classList.add('text-red-500');
                                    resetUpload();
                                }
                            }
                        } catch (error) {
                            console.error(`Chunk ${chunkNumber} upload failed (attempt ${retryCount + 1}):`, error);
                            retryCount++;
                            if (retryCount === maxRetries) {
                                throw new Error(`Failed to upload chunk ${chunkNumber} after ${maxRetries} attempts`);
                            }
                            // Wait before retrying
                            await new Promise(resolve => setTimeout(resolve, 1000 * retryCount));
                        }
                    }
                }
            } catch (error) {
                console.error('Upload failed:', error);
                if (error.message !== 'Session expired') {
                    alert('Upload failed: ' + error.message);
                    resetForm();
                }
            }

            function resetForm() {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                progressDiv.classList.add('hidden');
                progressBar.style.width = '0%';
                progressText.textContent = '0% uploaded';

                // Reset conversion progress if visible
                const conversionProgress = document.getElementById('conversion-progress');
                if (!conversionProgress.classList.contains('hidden')) {
                    conversionProgress.classList.add('hidden');
                    document.getElementById('conversion-progress-bar').style.width = '0%';
                    document.getElementById('conversion-progress-text').textContent = '0% processed';
                }
            }
        });

        // File size validation
        document.getElementById('video').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 4 * 1024 * 1024 * 1024; // 4GB in bytes
                if (file.size > maxSize) {
                    alert('File size exceeds 4GB limit. Please choose a smaller file.');
                    e.target.value = ''; // Clear the file input
                }
            }
        });

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
            const urlParams = new URLSearchParams(window.location.search);
            const currentFolderId = urlParams.get('folder_id');
            initializeFolderTree('folder-select', currentFolderId);
        });
    </script>
    @endpush
</x-app-layout>