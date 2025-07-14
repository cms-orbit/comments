<?php

use Illuminate\Support\Facades\Route;
use CmsOrbit\Comments\Http\Controllers\CommentController;
use CmsOrbit\Comments\Http\Controllers\CommentReactionController;
use CmsOrbit\Comments\Http\Controllers\CommentRatingController;

// 공통 라우트 (인증 없음) - 설정 조회, 댓글 목록 조회
Route::prefix('api/orbit-comments/comment')
    ->name('orbit-comments.comment.')
    ->middleware(['web'])
    ->controller(CommentController::class)
    ->group(function () {
    // 댓글 설정 조회
    Route::get('/configs', 'configs')->name('configs');
    // 댓글 목록 조회
    Route::get('/', 'index')->name('index');
    Route::get('/{comment}', 'show')->name('show');
});

// 인증된 사용자용 댓글 라우트
Route::prefix('api/orbit-comments/comment')
    ->name('orbit-comments.comment.')
    ->middleware(['web','auth:sanctum'])
    ->controller(CommentController::class)
    ->group(function () {
    // 댓글 CRUD (인증 필요)
    Route::post('/', 'store')->name('store');
    Route::put('/{comment}', 'update')->name('update');
    Route::delete('/{comment}', 'destroy')->name('destroy');

    // 스팸 처리 (권한 필요)
    Route::post('/{comment}/spam', 'markAsSpam')->name('mark-as-spam');
    Route::delete('/{comment}/spam', 'unmarkAsSpam')->name('unmark-as-spam');
});

// 게스트 댓글용 라우트 (인증 없음)
Route::prefix('api/orbit-comments/guest-comment')
    ->name('orbit-comments.guest-comment.')
    ->middleware(['web'])
    ->controller(CommentController::class)
    ->group(function () {
    // 댓글 CRUD
    Route::post('/', 'store')->name('store');
});

Route::prefix('api/orbit-comments/reactions')
    ->name('orbit-comments.reactions.')
    ->middleware(['web'])
    ->controller(CommentReactionController::class)
    ->group(function () {
        // 댓글 반응
        Route::post('/{comment}', 'toggle')->name('toggle');
});

Route::prefix('api/orbit-comments/ratings')
    ->name('orbit-comments.ratings.')
    ->middleware(['web'])
    ->controller(CommentRatingController::class)
    ->group(function () {
        // 댓글 평점
        Route::post('/{comment}', 'store')->name('store');
        Route::put('/{comment}', 'update')->name('update');
        Route::delete('/{comment}', 'destroy')->name('destroy');
});
