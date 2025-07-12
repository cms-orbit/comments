<?php

namespace CmsOrbit\Comments\Notifications;

use CmsOrbit\Comments\Models\Comment;

class NewCommentNotification extends CommentNotification
{
    /**
     * Get the email subject.
     */
    protected function getEmailSubject(): string
    {
        $commentable = $this->comment->commentable;
        $title = method_exists($commentable, 'title') ? $commentable->title : '게시물';
        
        return '새 댓글이 작성되었습니다: ' . $title;
    }

    /**
     * Get the email content.
     */
    protected function getEmailContent(): string
    {
        $commentable = $this->comment->commentable;
        $title = method_exists($commentable, 'title') ? $commentable->title : '게시물';
        
        return '귀하의 ' . $title . '에 새 댓글이 작성되었습니다.';
    }
} 