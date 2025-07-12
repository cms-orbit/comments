<?php

namespace CmsOrbit\Comments\Notifications;

use CmsOrbit\Comments\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

abstract class CommentNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected Comment $comment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = [];

        if (config('comments.notifications.email.enabled', true)) {
            $channels[] = 'mail';
        }

        if (config('comments.notifications.database.enabled', true)) {
            $channels[] = 'database';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $commentable = $this->comment->commentable;
        $author = $this->comment->author;

        $subject = $this->getEmailSubject();
        $greeting = $this->getEmailGreeting($notifiable);
        $content = $this->getEmailContent();

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content)
            ->line('댓글 내용: ' . $this->comment->content)
            ->action('보기', $this->getActionUrl());

        if ($author) {
            $message->line('작성자: ' . $author->name);
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'commentable_type' => $this->comment->commentable_type,
            'commentable_id' => $this->comment->commentable_id,
            'author_type' => $this->comment->author_type,
            'author_id' => $this->comment->author_id,
            'content' => $this->comment->content,
            'is_reply' => $this->comment->isReply(),
            'parent_id' => $this->comment->parent_id,
            'created_at' => $this->comment->created_at,
        ];
    }

    /**
     * Get the email subject.
     */
    abstract protected function getEmailSubject(): string;

    /**
     * Get the email greeting.
     */
    protected function getEmailGreeting($notifiable): string
    {
        return '안녕하세요, ' . $notifiable->name . '님!';
    }

    /**
     * Get the email content.
     */
    abstract protected function getEmailContent(): string;

    /**
     * Get the action URL.
     */
    protected function getActionUrl(): string
    {
        $commentable = $this->comment->commentable;
        
        // Try to get the URL from the commentable model
        if (method_exists($commentable, 'getShowPageUrl')) {
            return $commentable->getShowPageUrl();
        }

        // Fallback to a generic URL
        return url('/');
    }
} 