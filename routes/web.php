<?php

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

Route::group(['middleware' => ['user.auth']], function () {
    Route::get('/', 'Home\HomeController@index')->name("home");
});

Route::get('/login', 'AuthController@login')->name('login');
Route::post('/otp', 'AuthController@send_otp')->name('send_otp');
Route::post('/doLogin', 'AuthController@do_login')->name('do_login');
Route::get('/logout', 'AuthController@logout')->name('logout');

