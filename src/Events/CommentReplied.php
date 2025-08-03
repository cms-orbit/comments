<?php

namespace CmsOrbit\Comments\Events;

use CmsOrbit\Comments\Entities\Comment\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentReplied
{
    use Dispatchable, SerializesModels;

    public Comment $reply;
    public Comment $parentComment;

    /**
     * Create a new event instance.
     */
    public function __construct(Comment $reply)
    {
        $this->reply = $reply;
        $this->parentComment = $reply->parent;
    }

    /**
     * Get the commentable model.
     */
    public function getCommentable()
    {
        return $this->reply->commentable;
    }

    /**
     * Get the reply author.
     */
    public function getReplyAuthor()
    {
        return $this->reply->author;
    }

    /**
     * Get the parent comment author.
     */
    public function getParentAuthor()
    {
        return $this->parentComment->author;
    }
}
