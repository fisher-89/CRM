<?php

use Illuminate\Support\Facades\Route;
use Fisher\SSO\API\Controllers as API;
use Illuminate\Contracts\Routing\Registrar as RouteRegisterContract;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'api/package-sso'], function (RouteRegisterContract $api) {

    // Test route.
    // @ANY /api/package-sso
    $api->any('/', API\HomeController::class.'@index');
});
