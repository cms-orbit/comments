<?php

namespace CmsOrbit\Comments;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use CmsOrbit\Comments\Events\CommentCreated;
use CmsOrbit\Comments\Events\CommentReplied;
use CmsOrbit\Comments\Listeners\SendCommentNotification;
use CmsOrbit\Comments\Listeners\SendReplyNotification;

class CommentsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/Config/comments.php', 'comments'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadViewsFrom(__DIR__.'/views', 'comments');
        $this->loadTranslationsFrom(__DIR__.'/lang', 'comments');

        // Register event listeners
        Event::listen(CommentCreated::class, SendCommentNotification::class);
        Event::listen(CommentReplied::class, SendReplyNotification::class);

        // Publish config
        $this->publishes([
            __DIR__.'/Config/comments.php' => config_path('comments.php'),
        ], 'comments-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/Migrations' => database_path('migrations'),
        ], 'comments-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/views' => resource_path('views/vendor/comments'),
        ], 'comments-views');

        // Publish translations
        $this->publishes([
            __DIR__.'/lang' => resource_path('lang/vendor/comments'),
        ], 'comments-translations');
    }
} 