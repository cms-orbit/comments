<?php

namespace CmsOrbit\Comments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use CmsOrbit\Comments\Events\CommentCreated;
use CmsOrbit\Comments\Events\CommentReplied;

class Comment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'author_type',
        'author_id',
        'parent_id',
        'content',
        'guest_name',
        'guest_email',
        'is_approved',
        'is_spam',
        'ip_address',
        'user_agent',
        'rating_data',
        'reaction_data',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_spam' => 'boolean',
        'rating_data' => 'array',
        'reaction_data' => 'array',
    ];

    protected $appends = [
        'author_name',
        'author_avatar',
        'reactions_summary',
        'rating_summary',
        'depth',
    ];

    protected $dispatchesEvents = [
        'created' => CommentCreated::class,
    ];

    /**
     * Get the parent commentable model.
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the author of the comment.
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the parent comment.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Get all replies recursively.
     */
    public function allReplies(): HasMany
    {
        return $this->replies()->with('allReplies');
    }

    /**
     * Get the reactions for this comment.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    /**
     * Get the ratings for this comment.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(CommentRating::class);
    }

    /**
     * Scope to get approved comments only.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope to get top-level comments only.
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get comments by depth.
     */
    public function scopeByDepth($query, $depth = 0)
    {
        return $query->where('depth', $depth);
    }

    /**
     * Get the author name.
     */
    public function getAuthorNameAttribute(): string
    {
        if ($this->author) {
            return $this->author->name ?? $this->author->email ?? 'Unknown';
        }

        return $this->guest_name ?? 'Guest';
    }

    /**
     * Get the author avatar.
     */
    public function getAuthorAvatarAttribute(): ?string
    {
        if ($this->author && method_exists($this->author, 'profilePhotoUrl')) {
            return $this->author->profilePhotoUrl;
        }

        return null;
    }

    /**
     * Get reactions summary.
     */
    public function getReactionsSummaryAttribute(): array
    {
        $reactions = config('comments.reactions.types', []);
        $summary = [];

        foreach ($reactions as $type => $emoji) {
            $count = $this->reactions()->where('type', $type)->count();
            if ($count > 0) {
                $summary[$type] = [
                    'emoji' => $emoji,
                    'count' => $count,
                ];
            }
        }

        return $summary;
    }

    /**
     * Get rating summary.
     */
    public function getRatingSummaryAttribute(): array
    {
        $ratings = $this->ratings()->get();
        $summary = [];

        foreach ($ratings as $rating) {
            $summary[$rating->category] = [
                'average' => $rating->average_rating,
                'count' => $rating->rating_count,
            ];
        }

        return $summary;
    }

    /**
     * Get comment depth.
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    /**
     * Check if comment is a reply.
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Check if comment has replies.
     */
    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['content', 'is_approved', 'is_spam'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($comment) {
            if (is_null($comment->depth)) {
                $comment->depth = $comment->getDepthAttribute();
            }
        });

        static::created(function ($comment) {
            if ($comment->isReply()) {
                event(new CommentReplied($comment));
            }
        });
    }
} 