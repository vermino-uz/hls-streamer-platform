<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Title cannot be null
            $table->text('description')->nullable(); // Description can be null
            $table->string('file_path'); // File path cannot be null
            $table->string('hls_path')->nullable(); // Optional HLS path
            $table->string('thumbnail_path')->nullable(); // Optional thumbnail
            $table->string('original_name')->nullable(); // Optional original name
            $table->string('slug')->unique()->nullable(); // Slug must be unique if provided
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User must exist
            $table->integer('duration')->nullable(); // Optional duration in seconds
            $table->enum('status', ['processing', 'ready', 'failed', 'completed', 'pending'])->default('processing'); // Added 'pending' to allowed statuses
            $table->integer('views')->default(0); // Views default to 0
            $table->timestamps(); // Created at and updated at timestamps
            $table->softDeletes(); // Optional soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
