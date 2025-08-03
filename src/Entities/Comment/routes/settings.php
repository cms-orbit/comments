<?php

use CmsOrbit\Comments\Entities\Comment\Screens\CommentEditScreen;
use CmsOrbit\Comments\Entities\Comment\Screens\CommentListScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

// Platform > System > Comments
Route::screen('comments', CommentListScreen::class)
    ->name('comments')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('settings.index')
        ->push(__('Comments'), route('settings.entities.comments')));

// Platform > System > Comments > Comment
Route::screen('comments/{comment}/edit', CommentEditScreen::class)
    ->name('comments.edit')
    ->breadcrumbs(fn (Trail $trail, $comment) => $trail
        ->parent('settings.entities.comments')
        ->push(__('Comment #:id', ['id' => $comment->id]), route('settings.entities.comments.edit', $comment)));

// Platform > System > Comments > Create
Route::screen('comments/create', CommentEditScreen::class)
    ->name('comments.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('settings.entities.comments')
        ->push(__('Create'), route('settings.entities.comments.create')));
