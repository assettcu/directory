<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', ['middleware' => 'auth', 'uses' => 'WelcomeController@index']);
Route::get('logout','AuthController@logout');
Route::get('auth/login','AuthController@index');
Route::post('auth/login','AuthController@login');

Route::get('tags', ['middleware' => 'auth', 'uses' => 'TagsController@index']);

Route::get('people', ['middleware' => 'auth', 'uses' => 'PeopleController@index']);
Route::get('people/{personid}', ['middleware' => 'auth', 'uses' => 'PeopleController@show']);

Route::get('reports', ['middleware' => 'auth', 'uses' => 'ReportsController@index']);
Route::get('report/{reportid}', ['middleware' => 'auth', 'uses' => 'ReportsController@report']);

# Ajax Routes
Route::group(['prefix' => 'ajax'], function()
{
    Route::get('search_names','AjaxController@SearchNames');
    Route::get('people_table','AjaxController@PeopleTable');
    Route::get('people_table_save_state','AjaxController@PeopleTableSaveState');
    Route::get('people_table_load_state','AjaxController@PeopleTableLoadState');
});