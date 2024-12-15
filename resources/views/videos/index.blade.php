<x-app-layout>
    <div class="min-h-screen bg-[#0f1015] py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-6 flex items-center space-x-2 text-sm text-gray-400">
                <a href="{{ route('videos.index') }}" class="hover:text-blue-500">Videos</a>
                @if($currentFolder)
                    @foreach($currentFolder->ancestors() as $ancestor)
                        <span class="text-gray-600">/</span>
                        <a href="{{ route('videos.index', ['folder_id' => $ancestor->id]) }}" class="hover:text-blue-500">
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
                        href="{{ route('videos.create') }}" 
                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:bg-blue-500"
                    >
                        <svg class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                        Upload Video
                    </a>
                </div>
            </div>

            <!-- Folders Grid -->
            @if($folders->isNotEmpty())
                <div class="mb-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($folders as $folder)
                        <x-folder-card :folder="$folder" />
                    @endforeach
                </div>
            @endif

            <!-- Videos Grid -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($videos as $video)
                    <x-video-card :video="$video" />
                @empty
                    <div class="col-span-full">
                        <div class="flex flex-col items-center justify-center rounded-xl bg-[#1a1c24] py-12">
                            <svg class="mb-4 h-16 w-16 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
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
                                    href="{{ route('videos.create') }}" 
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
                @endforelse
            </div>

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
                        <input type="hidden" id="parentId" value="{{ $currentFolder?->id }}">
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
            const parentId = document.getElementById('parentId').value;
            const name = document.getElementById('folderName').value;

            try {
                const response = await fetch(folderId ? `/folders/${folderId}` : '/folders', {
                    method: folderId ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ name, parent_id: parentId })
                });

                if (!response.ok) throw new Error('Failed to save folder');

                window.location.reload();
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to save folder');
            }
        }

        async function deleteFolder(id, name) {
            if (!confirm(`Are you sure you want to delete the folder "${name}"? All videos will be moved to root.`)) {
                return;
            }

            try {
                const response = await fetch(`/folders/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) throw new Error('Failed to delete folder');

                // window.location.reload();
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete folder');
            }
        }
    </script>
    @endpush
</x-app-layout>
