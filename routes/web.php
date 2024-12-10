<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HLSController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::get('/', function () {
   return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Video routes
    Route::resource('videos', VideoController::class);
    Route::post('/videos/upload-chunk', [VideoController::class, 'uploadChunk'])->name('videos.upload-chunk');
    Route::get('/videos/{video}/conversion-progress', [VideoController::class, 'checkConversionProgress'])
        ->name('videos.conversion-progress');
});

// HLS Streaming routes
Route::get('/hls/{uuid}/{file}', [HLSController::class, 'serve'])
    ->where('uuid', '[a-f0-9\-]{36}')
    ->where('file', '.*')
    ->name('hls.serve');

require __DIR__.'/auth.php';
