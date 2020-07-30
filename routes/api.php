<?php

use Illuminate\Support\Facades\Route;

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

Route::post('/login', 'AuthenticateController@login')->name('auth.login');
Route::post('/register', 'AuthenticateController@store')->name('auth.register');

Route::middleware(['auth.user'])->group(function () {
    Route::get('/me', 'AuthenticateController@me')->name('auth.me');
    Route::post('/logout', 'AuthenticateController@logout')->name('auth.logout');
});
