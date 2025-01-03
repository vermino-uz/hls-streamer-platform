<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HLSController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/', [VideoController::class, 'index'])->name('home');
    Route::get('/dashboard', [VideoController::class, 'index'])->name('dashboard');
    
    Route::get('/phpinfo', function () {
        return phpinfo();
    });

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Video routes
    Route::resource('videos', VideoController::class);
    Route::post('/videos/upload-chunk', [VideoController::class, 'uploadChunk'])->name('videos.upload-chunk');
    Route::get('/videos/{video}/conversion-progress', [VideoController::class, 'checkConversionProgress'])
        ->name('videos.conversion-progress');

    // Folder routes
    Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
    Route::get('/folders/list', [FolderController::class, 'list'])->name('folders.list');
    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::put('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');
    Route::post('/folders/move-videos', [FolderController::class, 'moveVideos'])->name('folders.move-videos');
    Route::middleware(['auth'])->group(function () {
        Route::post('/folders/copy', [FolderController::class, 'copy'])->name('folders.copy');
        Route::post('/folders/move', [FolderController::class, 'move'])->name('folders.move');
        Route::post('/folders/{id}/rename', [FolderController::class, 'rename'])->name('folders.rename');
    });
});

// Public routes (no auth required)
Route::get('/hls/{uuid}/{file}', [HLSController::class, 'serve'])
    ->where('uuid', '[a-f0-9\-]{36}')
    ->where('file', '.*')
    ->name('hls.serve');

// Video routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [FolderController::class, 'index'])->name('dashboard');

    Route::get('/folders/search', [FolderController::class, 'search'])->name('folders.search')->middleware('auth');
    Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
    Route::get('/videos/create', [VideoController::class, 'create'])->name('videos.create');
    Route::post('/videos/copy', [VideoController::class, 'copy'])->name('videos.copy');
    Route::post('/videos/move', [VideoController::class, 'move'])->name('videos.move');
    Route::post('/videos/{id}/rename', [VideoController::class, 'rename'])->name('videos.rename');
    Route::delete('/videos/{id}', [VideoController::class, 'destroy'])->name('videos.destroy');
    Route::post('/videos/bulk-delete', [VideoController::class, 'bulkDelete'])->name('videos.bulk-delete');
});

require __DIR__ . '/auth.php';
