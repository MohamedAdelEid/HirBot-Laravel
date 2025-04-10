<?php

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

        // Get Feed for Authenticated User
        Route::get('/feed', [FeedController::class, 'index'])
            ->name('feed.index');


        // Connection Routes (Friend Requests, Accept, Reject, etc.)
        Route::prefix('connections')->group(function () {

            // Send a Connection Request
            Route::post('/', [ConnectionController::class, 'sendRequest'])
                ->name('connections.send');

            // Accept a Connection Request
            Route::post('/accept', [ConnectionController::class, 'acceptRequest'])
                ->name('connections.accept');

            // Reject a Connection Request
            Route::post('/reject', [ConnectionController::class, 'rejectRequest'])
                ->name('connections.reject');

            // Get Pending Connection Requests
            Route::get('/pending', [ConnectionController::class, 'getPendingRequests'])
                ->name('connections.pending');

            // Get All Connections
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
