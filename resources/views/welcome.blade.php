<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Video Streaming Platform') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-[#1a1c23] h-screen flex flex-col">
    <div class="flex-1 flex flex-col">
        @if (Route::has('login'))
            <div class="p-6 text-right">
                @auth
                    <a href="{{ route('dashboard') }}" class="font-semibold text-gray-400 hover:text-white transition duration-150">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="font-semibold text-gray-400 hover:text-white transition duration-150">Log in</a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="ml-4 font-semibold text-gray-400 hover:text-white transition duration-150">Register</a>
                    @endif
                @endauth
            </div>
        @endif

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-white mb-4">
                    Video Streaming Platform
                </h1>
                <p class="text-xl text-gray-400 mb-8">
                    Manage and stream your video content with ease
                </p>
                
                @auth
                    <a href="{{ route('dashboard') }}" 
                       class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300">
                        Go to Dashboard
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @else
                    <div class="space-x-4">
                        <a href="{{ route('login') }}" 
                           class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300">
                            Get Started
                        </a>
                        <a href="{{ route('register') }}" 
                           class="inline-flex items-center px-6 py-3 border border-gray-700 text-base font-medium rounded-md text-gray-300 bg-transparent hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-300">
                            Create Account
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Features Section -->
            <div class="mt-auto grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-6 bg-[#2a2d36] rounded-lg">
                    <div class="w-20 h-20 bg-blue-500/10 rounded-lg flex items-center justify-center mb-4 mx-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2 text-center">Video Management</h3>
                    <p class="text-gray-400 text-center">Upload, organize, and manage your video content with our intuitive interface.</p>
                </div>

                <div class="p-6 bg-[#2a2d36] rounded-lg">
                    <div class="w-20 h-20 bg-indigo-500/10 rounded-lg flex items-center justify-center mb-4 mx-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2 text-center">HLS Streaming</h3>
                    <p class="text-gray-400 text-center">Stream your videos efficiently using HTTP Live Streaming (HLS) protocol.</p>
                </div>

                <div class="p-6 bg-[#2a2d36] rounded-lg">
                    <div class="w-20 h-20 bg-purple-500/10 rounded-lg flex items-center justify-center mb-4 mx-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2 text-center">Secure Access</h3>
                    <p class="text-gray-400 text-center">Control access to your content with secure authentication and authorization.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
