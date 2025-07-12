<?php

namespace CmsOrbit\Comments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommentRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'rater_type',
        'rater_id',
        'category',
        'rating',
        'ip_address',
    ];

    protected $casts = [
        'rating' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the comment that was rated.
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Get the user who rated.
     */
    public function rater(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get ratings by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get ratings by rating value.
     */
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Get the average rating for a category.
     */
    public static function getAverageRatingForCategory($commentId, $category): float
    {
        return static::where('comment_id', $commentId)
            ->where('category', $category)
            ->avg('rating') ?? 0.0;
    }

    /**
     * Get the rating count for a category.
     */
    public static function getRatingCountForCategory($commentId, $category): int
    {
        return static::where('comment_id', $commentId)
            ->where('category', $category)
            ->count();
    }

    /**
     * Get the category display name.
     */
    public function getCategoryDisplayNameAttribute(): string
    {
        $categories = config('comments.ratings.categories.custom', []);
        return $categories[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Check if this rating is from a guest.
     */
    public function isFromGuest(): bool
    {
        return is_null($this->rater_id);
    }

    /**
     * Get the rater name.
     */
    public function getRaterNameAttribute(): string
    {
        if ($this->rater) {
            return $this->rater->name ?? $this->rater->email ?? 'Unknown';
        }

        return 'Guest';
    }

    /**
     * Get the star rating display.
     */
    public function getStarRatingAttribute(): string
    {
        $fullStars = floor($this->rating);
        $hasHalfStar = ($this->rating - $fullStars) >= 0.5;
        
        $stars = str_repeat('★', $fullStars);
        if ($hasHalfStar) {
            $stars .= '☆';
        }
        
        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
        $stars .= str_repeat('☆', $emptyStars);
        
        return $stars;
    }
} 