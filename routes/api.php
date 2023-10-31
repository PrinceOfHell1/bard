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

Route::middleware(['api', 'guest'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        //login & register
        Route::post('registration', 'register');
        Route::post('login', 'login');

        //google
        Route::get('google', 'google');
        Route::get('google/callback', 'googleAPI');

        //forgot password
        Route::post('sendEmail', 'sendEmail');
        Route::post('resendOTP/{email}', 'resendOTP');
        Route::post('checkOTP', 'checkOTP');
        Route::post('forget-password/{otp}', 'forgetPassword');

        // restore account
        Route::post('restore', 'restore');
    });
});

//Account
Route::middleware(['api', 'jwt.auth'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('profile', 'profile');
        Route::get('logout', 'logout');
        Route::get('delete', 'delete');
    });
});
