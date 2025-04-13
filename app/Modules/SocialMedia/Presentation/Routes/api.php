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
|
| All routes are loaded by the RouteServiceProvider within the "api" middleware group.
| Below is the grouping of Social Media related routes with role-based access control.
|
*/

/*
|--------------------------------------------------------------------------
| Routes for Company & User Roles
|--------------------------------------------------------------------------
|
| Accessible by users with the "company" or "user" roles.
| Includes post management and user feed.
|
*/
Route::middleware(['auth', 'role:' . UserRoleEnum::COMPANY->value . ',' . UserRoleEnum::USER->value])
    ->group(function () {

        // Post Resource Routes (CRUD)
        Route::apiResource('posts', PostController::class);

        // Force Delete a Post
        Route::delete('posts/{id}/force', [PostController::class, 'forceDestroy'])
            ->name('posts.force-destroy');

        // Post Interaction Routes
        Route::post('posts/{postId}/interact', [PostController::class, 'interact'])
            ->name('posts.interact');
        Route::delete('posts/{postId}/interact', [PostController::class, 'removeInteraction'])
            ->name('posts.remove-interaction');

        // Get Feed for Authenticated User
        Route::get('/feed', [FeedController::class, 'index'])
            ->name('feed.index');

        // Comment Routes
        Route::get('posts/{postId}/comments', [CommentController::class, 'index'])
            ->name('comments.index');
        Route::post('posts/{postId}/comments', [CommentController::class, 'store'])
            ->name('comments.store');
        Route::delete('comments/{commentId}', [CommentController::class, 'destroy'])
            ->name('comments.destroy');

        // Comment Interaction Routes
        Route::post('comments/{commentId}/interact', [CommentController::class, 'interact'])
            ->name('comments.interact');
        Route::delete('comments/{commentId}/interact', [CommentController::class, 'removeInteraction'])
            ->name('comments.remove-interaction');


        // Connection Routes (Friend Requests, Accept, Reject, etc.)
        Route::prefix('connections')->group(function () {

            Route::post('/', [ConnectionController::class, 'sendRequest'])
                ->name('connections.send');

            Route::post('/accept', [ConnectionController::class, 'acceptRequest'])
                ->name('connections.accept');

            Route::post('/reject', [ConnectionController::class, 'rejectRequest'])
                ->name('connections.reject');

            Route::get('/pending', [ConnectionController::class, 'getPendingRequests'])
                ->name('connections.pending');

            Route::get('/', [ConnectionController::class, 'getConnections'])
                ->name('connections.index');
        });
    });

/*
|--------------------------------------------------------------------------
| Routes for User Role Only
|--------------------------------------------------------------------------
|
| Accessible only by users with the "user" role.
| Includes user-to-user connection features.
|
*/
Route::middleware(['auth', 'role:' . UserRoleEnum::USER->value])
    ->group(function () {


    });
