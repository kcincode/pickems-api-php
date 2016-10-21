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

Route::get('picks', 'PicksController@picks');
Route::post('picks', 'PicksController@postPicks');
Route::get('picks/{team}', 'PicksController@allPicks');

Route::get('picks-filter', 'PicksController@filter');
Route::get('team-picks', 'PicksController@teamPick');

Route::get('stats/weekly', 'StatsController@weekly');
Route::get('stats/best', 'StatsController@best');
Route::get('stats/most', 'StatsController@most');
Route::get('stats/rankings', 'StatsController@rankings');
