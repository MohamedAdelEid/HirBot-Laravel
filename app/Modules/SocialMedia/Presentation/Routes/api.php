<?php

use App\Shared\Enums\UserRoleEnum;
use Illuminate\Support\Facades\Route;
use App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1\PostController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::group([
    'middleware' => ['auth', 'role:' . UserRoleEnum::COMPANY->value .',' . UserRoleEnum::USER->value ],
], function () {

    // Posts Resource Routes (Handles CRUD Operations)
    Route::apiResource('posts', PostController::class);

});

