<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Oauth google
Route::middleware(['guest'])->group(function () {
    Route::prefix('google')->group(function () {
        Route::get('/', 'AuthController@google');
        Route::get('/callback', 'AuthController@googleAPI');
    });
});

//verify email
Route::get('verify/{verified}', [AuthController::class, 'verifyEmail']);

//forgot password
Route::get('/reset-password', [AuthController::class, 'resetPasswordLoad']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
