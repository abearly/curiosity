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

Route::group(['prefix' => 'orders'], function() {
    Route::get('', 'OrderController@getOrders');
    Route::post('/submit', 'OrderController@postSubmitOrder');
    Route::patch('/fulfill', 'OrderController@patchFulfill');
    Route::patch('/cancel', 'OrderController@patchCancelOrder');
});

Route::group(['prefix' => 'products'], function() {
    Route::get('', 'ProductController@getProducts');
    Route::post('', 'ProductController@postAddProduct');
    Route::patch('', 'ProductController@patchEditProduct');
    Route::delete('', 'ProductController@deleteProduct');
});

Route::group(['prefix' => 'users'], function() {
    Route::get('', 'UserController@getUsers');
    Route::get('/{id}', 'UserController@getByUserId');
    Route::patch('', 'UserController@patchUser');
    Route::patch('/password', 'UserController@patchPassword');
    Route::post('', 'UserController@postUser');
    Route::delete('', 'UserController@deleteUser');
});

Route::post('/login', 'UserController@login');
