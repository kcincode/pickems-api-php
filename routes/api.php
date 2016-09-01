<?php

use Illuminate\Http\Request;

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

Route::get('/', function (Request $request) {
    return response()->json(['status' => 'ok']);
})->middleware('jwt.auth');

Route::post('/token', 'AuthController@token');

Route::resource('users', 'UsersController', ['except' => ['create', 'edit']]);

Route::resource('teams', 'TeamsController', ['except' => ['create', 'edit']]);