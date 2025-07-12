<?php

namespace CmsOrbit\Comments\Events;

use CmsOrbit\Comments\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated
{
    use Dispatchable, SerializesModels;

    public Comment $comment;

    /**
     * Create a new event instance.
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get the commentable model.
     */
    public function getCommentable()
    {
        return $this->comment->commentable;
    }

    /**
     * Get the comment author.
     */
    public function getAuthor()
    {
        return $this->comment->author;
    }

    /**
     * Check if the comment is a reply.
     */
    public function isReply(): bool
    {
        return $this->comment->isReply();
    }

    /**
     * Get the parent comment if this is a reply.
     */
    public function getParentComment(): ?Comment
    {
        return $this->comment->parent;
    }
} 