<?php

use App\Http\Controllers\Admin\DashboardController;
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

Route::middleware(['guest'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        //google
        Route::get('google', 'google');

        //verify email
        Route::get('verify/{verified}', 'verifyEmail');
    });

    //dashboard admin
    Route::prefix('/dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
    });
});
