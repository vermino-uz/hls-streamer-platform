<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'hls_path',
        'thumbnail_path',
        'duration',
        'status',
        'user_id',
        'slug',
        'views'
    ];

    protected $casts = [
        'duration' => 'integer',
        'views' => 'integer',
        'is_public' => 'boolean',
        'metadata' => 'array',
        'size' => 'integer'
    ];

    protected $appends = ['hls_url'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($video) {
            if (! $video->slug) {
                $video->slug = Str::slug($video->title);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStreamUrlAttribute()
    {
        return url("stream/{$this->hls_path}/master.m3u8");
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail_path ? url("storage/{$this->thumbnail_path}") : null;
    }

    public function getHlsUrlAttribute()
    {
        if (!$this->hls_path) {
            return null;
        }

        // Extract UUID from the HLS path
        $uuid = basename(dirname($this->hls_path));
        $filename = basename($this->hls_path);

        // Generate the proper HLS URL using the route
        return route('hls.serve', [
            'uuid' => $uuid,
            'file' => $filename
        ]);
    }

    public function incrementViews()
    {
        $this->increment('views');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category_id', $category);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
