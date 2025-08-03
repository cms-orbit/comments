<?php

namespace CmsOrbit\Comments\Notifications;

use CmsOrbit\Comments\Entities\Comment\Comment;

class NewReplyNotification extends CommentNotification
{
    /**
     * Get the email subject.
     */
    protected function getEmailSubject(): string
    {
        return '귀하의 댓글에 답글이 작성되었습니다';
    }

    /**
     * Get the email content.
     */
    protected function getEmailContent(): string
    {
        $commentable = $this->comment->commentable;
        $title = method_exists($commentable, 'title') ? $commentable->title : '게시물';

        return '귀하가 작성한 댓글에 답글이 작성되었습니다. (' . $title . ')';
    }

    /**
     * Get the email greeting.
     */
    protected function getEmailGreeting($notifiable): string
    {
        return '안녕하세요, ' . $notifiable->name . '님!';
    }
}
