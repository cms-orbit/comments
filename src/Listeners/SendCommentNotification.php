<?php

namespace CmsOrbit\Comments\Listeners;

use CmsOrbit\Comments\Events\CommentCreated;
use CmsOrbit\Comments\Notifications\NewCommentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCommentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;
        $commentable = $comment->commentable;

        // Don't send notification if it's a reply (handled by SendReplyNotification)
        if ($comment->isReply()) {
            return;
        }

        // Send notification to the commentable model's author if it has one
        if (method_exists($commentable, 'author') && $commentable->author) {
            $author = $commentable->author;
            
            // Don't send notification to the comment author themselves
            if ($comment->author && $comment->author->id === $author->id) {
                return;
            }

            $author->notify(new NewCommentNotification($comment));
        }

        // Send notification to DocumentModel's author if applicable
        if (method_exists($commentable, 'document') && $commentable->document) {
            $document = $commentable->document;
            if ($document->author) {
                $author = $document->author;
                
                // Don't send notification to the comment author themselves
                if ($comment->author && $comment->author->id === $author->id) {
                    return;
                }

                $author->notify(new NewCommentNotification($comment));
            }
        }
    }
} 