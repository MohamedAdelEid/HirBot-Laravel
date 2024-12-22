<?php

use app\Modules\SocialMedia\Presentation\Http\Controllers\Api\V1\TestController;
use Illuminate\Support\Facades\Route;
use App\Modules\SocialMedia\Presentation\Http\Controllers\SocialMediaController;

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

Route::middleware(['auth'])->group(function () {
    Route::apiResource('socialmedia', SocialMediaController::class)->names('socialmedia');
    Route::get('/',[TestController::class , 'index']);
});
