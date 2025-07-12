<?php

namespace CmsOrbit\Comments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommentReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'reactor_type',
        'reactor_id',
        'type',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the comment that was reacted to.
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Get the user who reacted.
     */
    public function reactor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get reactions by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the reaction emoji.
     */
    public function getEmojiAttribute(): string
    {
        $reactions = config('comments.reactions.types', []);
        return $reactions[$this->type] ?? '👍';
    }

    /**
     * Check if this reaction is from a guest.
     */
    public function isFromGuest(): bool
    {
        return is_null($this->reactor_id);
    }

    /**
     * Get the reactor name.
     */
    public function getReactorNameAttribute(): string
    {
        if ($this->reactor) {
            return $this->reactor->name ?? $this->reactor->email ?? 'Unknown';
        }

        return 'Guest';
    }
} 