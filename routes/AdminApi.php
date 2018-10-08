<?php

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['namespace' => 'Auth\\'], function () {
    // Route::post('login', 'AuthController@login')->name('login');
    Route::post('/register', 'RegisterController@register')->name('register');
    Route::post('/credential/{activation_token}', 'RegisterController@credential')->name('credential');

    Route::post('/forget-password', 'PasswordResetController@create')->name('create');
    Route::post('/forget-password/{token}', 'PasswordResetController@authenticateToken')->name('authenticate_token');
    Route::put('/forget-password', 'PasswordResetController@reset')->name('reset');

    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('logout', 'AuthController@logout')->name('logout');
        Route::get('user', 'AuthController@user')->name('user');

        Route::put('user', 'AuthController@update')->name('update');

        Route::post('change-password', 'AuthController@changePassword')->name('change_password');
    });
});

Route::group(['middleware' => ['authentication', 'auth:api']], function () {

    Route::group(['prefix' => 'posts', 'as' => 'posts.'], function () {
        Route::get('/', 'PostController@index')->name('index');
        Route::get('/{id}', 'PostController@show')->name('show');

        Route::post('/uploadFile', 'PostController@uploadFile')->name('upload_file');

        Route::post('/', 'PostController@store')->name('store');

        Route::put('/{id}', 'PostController@update')->name('update');

        Route::put('/hot/{id}', 'PostController@showHot')->name('show_how');

        Route::put('/slider/{id}', 'PostController@showSlider')->name('show_slider');

        Route::delete('/{id}', 'PostController@destroy')->name('destroy');
    });

    Route::group(['prefix' => 'categories', 'as' => 'categories.'], function () {
        Route::get('/', 'CategoryController@index')->name('index');

        Route::post('/', 'CategoryController@store')->name('store');

        Route::get('/edit/{id}', 'CategoryController@edit')->name('edit');
        Route::put('/{id}', 'CategoryController@update')->name('update');

        Route::delete('/{id}', 'CategoryController@destroy')->name('destroy');
    });

    Route::group(['prefix' => 'tags', 'as' => 'tags.'], function () {
        Route::get('/', 'TagController@index')->name('index');

        Route::put('/{id}', 'TagController@update')->name('update');

        Route::delete('/{id}', 'TagController@destroy')->name('destroy');
    });
});

