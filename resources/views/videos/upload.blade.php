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
                                <div class="w-full bg-gray-700 rounded-full h-2.5">
                                    <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                                </div>
                                <p id="progress-text" class="mt-2 text-sm text-white">0% uploaded</p>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end">
                                <button type="submit" id="submit-button" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('Upload Video') }}
                                </button>
                            </div>
                        </form>

                        <script>
                            document.getElementById('upload-form').addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                const form = e.target;
                                const formData = new FormData(form);
                                const submitButton = document.getElementById('submit-button');
                                const progressDiv = document.getElementById('upload-progress');
                                const progressBar = document.getElementById('progress-bar');
                                const progressText = document.getElementById('progress-text');

                                // Show progress bar and disable submit button
                                progressDiv.classList.remove('hidden');
                                submitButton.disabled = true;
                                submitButton.classList.add('opacity-50', 'cursor-not-allowed');

                                // Create AJAX request
                                const xhr = new XMLHttpRequest();
                                xhr.open('POST', form.action, true);

                                // Setup progress handler
                                xhr.upload.onprogress = function(e) {
                                    if (e.lengthComputable) {
                                        const percent = Math.round((e.loaded / e.total) * 100);
                                        progressBar.style.width = percent + '%';
                                        progressText.textContent = percent + '%';
                                        
                                        if (percent === 100) {
                                            setTimeout(() => {
                                                window.location.replace('{{ route('dashboard') }}');
                                            }, 500); // Small delay to ensure the request completes
                                        }
                                    }
                                };

                                // Handle response
                                xhr.onload = function() {
                                    if (xhr.status === 200) {
                                        const response = JSON.parse(xhr.responseText);
                                        if (!response.success) {
                                            alert('Upload failed: ' + response.message);
                                            resetForm();
                                        }
                                    } else {
                                        const response = JSON.parse(xhr.responseText);
                                        alert('Upload failed: ' + (response.message || 'Unknown error'));
                                        resetForm();
                                    }
                                };

                                // Handle network errors
                                xhr.onerror = function() {
                                    alert('Upload failed. Please check your connection and try again.');
                                    resetForm();
                                };

                                // Send the form data
                                xhr.send(formData);

                                function resetForm() {
                                    submitButton.disabled = false;
                                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                                    progressDiv.classList.add('hidden');
                                    progressBar.style.width = '0%';
                                    progressText.textContent = '0% uploaded';
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