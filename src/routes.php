<?php

use Illuminate\Support\Facades\Route;
use CmsOrbit\Comments\Http\Controllers\CommentController;
use CmsOrbit\Comments\Http\Controllers\CommentReactionController;
use CmsOrbit\Comments\Http\Controllers\CommentRatingController;

Route::prefix('api')->group(function () {
    // 댓글 CRUD
    Route::get('/comments', [CommentController::class, 'index']);
    Route::post('/comments', [CommentController::class, 'store']);
    Route::get('/comments/{comment}', [CommentController::class, 'show']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // 댓글 반응
    Route::post('/comments/{comment}/reactions', [CommentReactionController::class, 'toggle']);

    // 댓글 평점
    Route::post('/comments/{comment}/ratings', [CommentRatingController::class, 'store']);
    Route::put('/comments/{comment}/ratings', [CommentRatingController::class, 'update']);
    Route::delete('/comments/{comment}/ratings', [CommentRatingController::class, 'destroy']);
}); 