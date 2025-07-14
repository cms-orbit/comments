<?php

namespace CmsOrbit\Comments\Traits;

use CmsOrbit\Comments\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasComments
{
    /**
     * Get comment configuration for this model.
     * 각 모델은 이 메서드를 오버라이드하여 자신만의 댓글 설정을 정의할 수 있습니다.
     */
    public static function commentConfigs(): array
    {
        return config('orbit-comments');
    }

    /**
     * Get all comments for the model.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get approved comments only.
     */
    public function approvedComments(): MorphMany
    {
        return $this->comments()->approved();
    }

    /**
     * Get top-level comments only.
     */
    public function topLevelComments(): MorphMany
    {
        return $this->comments()->topLevel();
    }

    /**
     * Get comments with replies.
     */
    public function commentsWithReplies(): MorphMany
    {
        return $this->comments()->with(['replies', 'author', 'reactions', 'ratings']);
    }

    /**
     * Get comments by depth.
     */
    public function commentsByDepth(int $depth = 0): MorphMany
    {
        return $this->comments()->byDepth($depth);
    }

    /**
     * Add a comment to the model.
     */
    public function addComment(array $data): Comment
    {
        $data['commentable_type'] = static::class;
        $data['commentable_id'] = $this->id;
        $data['ip_address'] = request()->ip();
        $data['user_agent'] = request()->userAgent();

        // Set author if user is authenticated
        if (Auth::check()) {
            $data['author_type'] = Auth::user()->getMorphClass();
            $data['author_id'] = Auth::id();
        }

        // Auto-approve if configured
        $configs = static::commentConfigs();
        if ($configs['moderation']['auto_approve']) {
            $data['is_approved'] = true;
        }

        return $this->comments()->create($data);
    }

    /**
     * Add a reply to a comment.
     */
    public function addReply(Comment $parentComment, array $data): Comment
    {
        $data['parent_id'] = $parentComment->id;
        return $this->addComment($data);
    }

    /**
     * Get the comment count.
     */
    public function getCommentCountAttribute(): int
    {
        return $this->comments()->approved()->count();
    }

    /**
     * Get the reply count.
     */
    public function getReplyCountAttribute(): int
    {
        return $this->comments()->approved()->whereNotNull('parent_id')->count();
    }

    /**
     * Get the total comment count (including replies).
     */
    public function getTotalCommentCountAttribute(): int
    {
        return $this->comment_count + $this->reply_count;
    }

    /**
     * Check if the model has comments.
     */
    public function hasComments(): bool
    {
        return $this->comments()->exists();
    }

    /**
     * Check if the model has approved comments.
     */
    public function hasApprovedComments(): bool
    {
        return $this->comments()->approved()->exists();
    }

    /**
     * Get comments with pagination.
     */
    public function getPaginatedComments(int $perPage = null): \Illuminate\Pagination\LengthAwarePaginator
    {
        $configs = static::commentConfigs();
        $perPage = $perPage ?? $configs['display']['per_page'];

        return $this->comments()
            ->approved()
            ->topLevel()
            ->with(['replies', 'author', 'reactions', 'ratings'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get recent comments.
     */
    public function getRecentComments(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->comments()
            ->approved()
            ->with(['author', 'reactions'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular comments (by reactions).
     */
    public function getPopularComments(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->comments()
            ->approved()
            ->with(['author', 'reactions'])
            ->withCount('reactions')
            ->orderBy('reactions_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Scope to get models with comments.
     */
    public function scopeWithComments(Builder $query): Builder
    {
        return $query->has('comments');
    }

    /**
     * Scope to get models with approved comments.
     */
    public function scopeWithApprovedComments(Builder $query): Builder
    {
        return $query->has('comments', '>=', 1, function ($query) {
            $query->approved();
        });
    }

    /**
     * Scope to get models ordered by comment count.
     */
    public function scopeOrderByCommentCount(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->withCount('comments')->orderBy('comments_count', $direction);
    }

    /**
     * Scope to get models with recent comments.
     */
    public function scopeWithRecentComments(Builder $query, int $days = 7): Builder
    {
        return $query->whereHas('comments', function ($query) use ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        });
    }

    /**
     * Get the average rating for the model.
     */
    public function getAverageRatingAttribute(): float
    {
        $ratings = $this->comments()
            ->approved()
            ->with('ratings')
            ->get()
            ->flatMap(function ($comment) {
                return $comment->ratings;
            });

        if ($ratings->isEmpty()) {
            return 0.0;
        }

        return $ratings->avg('rating') ?? 0.0;
    }

    /**
     * Get the rating count for the model.
     */
    public function getRatingCountAttribute(): int
    {
        return $this->comments()
            ->approved()
            ->whereHas('ratings')
            ->count();
    }

    /**
     * Get rating summary by category.
     */
    public function getRatingSummaryAttribute(): array
    {
        $configs = static::commentConfigs();
        $categories = $configs['ratings']['categories']['custom'] ?? [];
        $summary = [];

        foreach ($categories as $category => $displayName) {
            $average = $this->comments()
                ->approved()
                ->whereHas('ratings', function ($query) use ($category) {
                    $query->where('category', $category);
                })
                ->get()
                ->flatMap(function ($comment) use ($category) {
                    return $comment->ratings->where('category', $category);
                })
                ->avg('rating') ?? 0.0;

            $count = $this->comments()
                ->approved()
                ->whereHas('ratings', function ($query) use ($category) {
                    $query->where('category', $category);
                })
                ->count();

            $summary[$category] = [
                'display_name' => $displayName,
                'average' => $average,
                'count' => $count,
            ];
        }

        return $summary;
    }
}
