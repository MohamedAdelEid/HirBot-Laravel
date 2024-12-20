<?php

use App\Modules\SocialMedia\Presentation\Http\Controllers\Api\V1\TestController;
use Illuminate\Support\Facades\Route;

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

Route::group([],function () {
    Route::post('/test', [TestController::class, 'create']);
    Route::get('/test', [TestController::class, 'index']);
});
