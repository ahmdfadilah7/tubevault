<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavedVideo extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class);
    }

    protected $fillable = [
        'user_id',
        'media_type',
        'youtube_id',
        'spotify_id',
        'spotify_type',
        'playback_youtube_id',
        'title',
        'thumbnail_url',
        'channel_name',
        'notes',
        'watch_count',
        'last_watched_at',
    ];

    protected function casts(): array
    {
        return [
            'last_watched_at' => 'datetime',
        ];
    }

    public function isSpotify(): bool
    {
        return $this->media_type === 'spotify';
    }

    public function isYoutube(): bool
    {
        return $this->media_type === 'youtube';
    }
}
