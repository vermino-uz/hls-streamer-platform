<x-app-layout>
    <div class="min-h-screen bg-[#0f1015]">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-[#1a1c24] overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-white">
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
                        <form id="upload-form" method="POST" action="{{ route('videos.store') }}" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            
                            <!-- Title -->
                            <div>
                                <x-input-label for="title" :value="__('Title')" class="text-white" />
                                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full bg-[#242731] border-gray-700 text-white focus:border-blue-500 focus:ring-blue-500" required />
                                <x-input-error class="mt-2" :messages="$errors->get('title')" />
                            </div>

                            <!-- Description -->
                            <div>
                                <x-input-label for="description" :value="__('Description')" class="text-white" />
                                <textarea id="description" name="description" class="mt-1 block w-full bg-[#242731] border-gray-700 text-white focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"></textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('description')" />
                            </div>

                            <!-- Video Upload -->
                            <div>
                                <x-input-label for="video" :value="__('Video File')" class="text-white" />
                                <input type="file" id="video" name="video" accept="video/*" 
                                    class="mt-1 block w-full text-sm text-white
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-md file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-600 file:text-white
                                        hover:file:bg-blue-500"
                                    required
                                />
                                <p class="mt-1 text-sm text-white">
                                    Supported formats: MP4, MOV, AVI (Max size: 4GB)
                                </p>
                                <x-input-error class="mt-2" :messages="$errors->get('video')" />
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

                        <script>
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
                                                window.location.href = '{{ route('dashboard') }}';
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

                                                const response = await fetch('{{ route('videos.upload-chunk') }}', {
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
                                                    window.location.href = '{{ route('login') }}';
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
                                                    finalFormData.append('path', result.path);
                                                    finalFormData.append('title', document.getElementById('title').value);
                                                    finalFormData.append('description', document.getElementById('description').value);
                                                    
                                                    try {
                                                        const finalResponse = await fetch('{{ route('videos.store') }}', {
                                                            method: 'POST',
                                                            body: finalFormData,
                                                            headers: {
                                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                            }
                                                        });

                                                        const finalResult = await finalResponse.json();

                                                        if (finalResult.success) {
                                                            // Show success message
                                                            uploadStatus.textContent = 'Upload complete! Processing video...';
                                                            uploadStatus.classList.remove('text-red-500');
                                                            uploadStatus.classList.add('text-green-500');
                                                            
                                                            // Redirect to dashboard after a short delay
                                                            setTimeout(() => {
                                                                window.location.href = '{{ route('dashboard') }}';
                                                            }, 1500);
                                                        } else {
                                                            throw new Error(finalResult.message || 'Failed to process video');
                                                        }
                                                    } catch (error) {
                                                        console.error('Final submission failed:', error);
                                                        uploadStatus.textContent = 'Upload failed: ' + error.message;
                                                        uploadStatus.classList.remove('text-green-500');
                                                        uploadStatus.classList.add('text-red-500');
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
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
