<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
        'parent_id'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($folder) {
            if (!$folder->slug) {
                $baseSlug = Str::slug($folder->name);
                $folder->slug = $baseSlug . '-' . Str::random(6);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function allVideos()
    {
        $videos = $this->videos;
        
        foreach ($this->children as $child) {
            $videos = $videos->merge($child->allVideos());
        }
        
        return $videos;
    }

    public function ancestors()
    {
        return $this->parent ? collect([$this->parent])->merge($this->parent->ancestors()) : collect();
    }

    public function getAncestorsAttribute()
    {
        return $this->ancestors()->reverse();
    }

    public function getPathAttribute()
    {
        return $this->ancestors()->pluck('name')->push($this->name)->implode('/');
    }
}
