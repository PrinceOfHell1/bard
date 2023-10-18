<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AuthController::class)->group(function () {
    //login & register
    Route::post('registration', 'register');
    Route::post('login', 'login');

    //forgot password
    Route::post('sendEmail', 'sendEmail');
    Route::post('resendToken/{email}', 'resendToken');
    Route::post('checkToken', 'checkToken');
    Route::post('forget-password/{token}', 'forgetPassword');

    // restore account
    Route::post('restore', 'restore');
})->middleware('guest');

Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('profile', 'profile');
        Route::get('logout', 'logout');
        Route::get('delete', 'delete');
    });
});
