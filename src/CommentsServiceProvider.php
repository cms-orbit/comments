<?php

namespace CmsOrbit\Comments;

use App\Console\Commands\BuildThemeScripts;
use App\Lang\LoadLangTrait;
use App\Providers\SettingsServiceProvider;
use App\Services\CmsHelper;
use App\Services\ThemePathRegister;
use App\Exceptions\ReservedAliasException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use CmsOrbit\Comments\Events\CommentCreated;
use CmsOrbit\Comments\Events\CommentReplied;
use CmsOrbit\Comments\Listeners\SendCommentNotification;
use CmsOrbit\Comments\Listeners\SendReplyNotification;
use Orchid\Platform\ItemPermission;

class CommentsServiceProvider extends ServiceProvider
{
    use LoadLangTrait;
    /**
     * Register services.
     * @throws ReservedAliasException
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/orbit-comments.php', 'orbit-comments'
        );
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/orbit-comment-api.php');

        $commentFrontendPath = new ThemePathRegister('@orbit/comments', __DIR__.'/../resources/js');
        BuildThemeScripts::registerPath($commentFrontendPath);
        BuildThemeScripts::registerTailwindBase(__DIR__.'/../resources/js/**/**/*.vue');

        //관리자 화면에 엔티티 등록
        CmsHelper::addEntityPath("CmsOrbit\\Comments\\Entities",__DIR__ . "/Entities");
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadJsonLang(__DIR__.'/../resources/lang');
        $permissionGroup = "Comments";
        SettingsServiceProvider::$permissions[$permissionGroup] = ItemPermission::group($permissionGroup);
        SettingsServiceProvider::$permissions[$permissionGroup]->addPermission('orbit_comments.read_secret', __('Read Secret Comments'));
        SettingsServiceProvider::$permissions[$permissionGroup]->addPermission('orbit_comments.delete_any', __('Delete Any Comments'));
        SettingsServiceProvider::$permissions[$permissionGroup]->addPermission('orbit_comments.spam_any', __('Make Spam Any Comments'));

        // Register event listeners
        Event::listen(CommentCreated::class, SendCommentNotification::class);
        Event::listen(CommentReplied::class, SendReplyNotification::class);

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/orbit-comments.php' => config_path('orbit-comments.php'),
        ], 'comments-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'comments-migrations');
    }
}
