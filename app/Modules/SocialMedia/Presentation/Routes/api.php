<?php

use App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1\CommentController;
use App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1\ConnectionController;
use App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1\FeedController;
use App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1\PostController;
use App\Shared\Enums\UserRoleEnum;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Routes are loaded by the RouteServiceProvider within the "api" middleware group.
| This file contains routes related to the Social Media module.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Routes for Company & User Roles
|--------------------------------------------------------------------------
| Accessible by users with the "company" or "user" roles.
| Includes: Posts, Comments, Feed, and Connections.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:' . UserRoleEnum::COMPANY->value . ',' . UserRoleEnum::USER->value])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Posts Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('posts')->controller(PostController::class)->group(function () {
            Route::apiResource('/', PostController::class)->except(['create', 'edit']);
            Route::delete('{id}/force', 'forceDestroy')->name('posts.force-destroy');
            Route::post('{postId}/interact', 'interact')->name('posts.interact');
            Route::delete('{postId}/interact', 'removeInteraction')->name('posts.remove-interaction');

            /*
            |--------------------------------------------------------------------------
            | Poll Voting Routes (Nested Under Posts)
            |--------------------------------------------------------------------------
            */
            Route::post('poll/vote', 'votePoll')->name('poll.vote');
            Route::delete('poll/{pollId}/vote', 'removeVote')->name('poll.remove-vote');

            /*
            |--------------------------------------------------------------------------
            | Comments Routes (Nested Under Posts)
            |--------------------------------------------------------------------------
            */
            Route::get('{postId}/comments', [CommentController::class, 'index'])->name('comments.index');
            Route::get('comments/{commentId}/replies', [CommentController::class, 'getReplies'])->name('comments.replies');
            Route::get('comments/{commentId}/thread', [CommentController::class, 'getThread'])->name('comments.thread');
            Route::post('{postId}/comments', [CommentController::class, 'store'])->name('comments.store');
            Route::delete('comments/{commentId}', [CommentController::class ,'destroy'])->name('comments.destroy');
            Route::post('comments/{commentId}/interact', [CommentController::class ,'interact'])->name('comments.interact');
            Route::delete('comments/{commentId}/interact', [CommentController::class , 'removeInteraction'])->name('comments.remove-interaction');
        });

        /*
        |--------------------------------------------------------------------------
        | Feed Routes
        |--------------------------------------------------------------------------
        */
        Route::get('feed', [FeedController::class, 'index'])->name('feed.index');

        /*
        |--------------------------------------------------------------------------
        | Connection Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('connections')->controller(ConnectionController::class)->group(function () {
            Route::post('/', 'sendRequest')->name('connections.send');
            Route::post('/accept', 'acceptRequest')->name('connections.accept');
            Route::post('/reject', 'rejectRequest')->name('connections.reject');
            Route::get('/pending', 'getPendingRequests')->name('connections.pending');
            Route::get('/', 'getConnections')->name('connections.index');
        });
    });

/*
|--------------------------------------------------------------------------
| Routes for User Role Only
|--------------------------------------------------------------------------
| Accessible only by users with the "user" role.
| Reserved for future user-only features.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:' . UserRoleEnum::USER->value])
    ->group(function () {
        // Future user-specific routes go here
    });
