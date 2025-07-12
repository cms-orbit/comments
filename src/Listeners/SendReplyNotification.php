<?php

namespace CmsOrbit\Comments\Listeners;

use CmsOrbit\Comments\Events\CommentReplied;
use CmsOrbit\Comments\Notifications\NewReplyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendReplyNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CommentReplied $event): void
    {
        $reply = $event->reply;
        $parentComment = $event->parentComment;

        // Send notification to the parent comment's author
        if ($parentComment->author) {
            $parentAuthor = $parentComment->author;
            
            // Don't send notification to the reply author themselves
            if ($reply->author && $reply->author->id === $parentAuthor->id) {
                return;
            }

            $parentAuthor->notify(new NewReplyNotification($reply));
        }
    }
} 