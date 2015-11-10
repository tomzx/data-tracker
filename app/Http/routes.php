<?php

Route::get('/', [
	'as' => 'index',
	'uses' => '\App\Http\Controllers\HomeController@index'
]);

Route::get('logs', [
	'as' => 'logs.index',
	'uses' => '\App\Http\Controllers\LogController@index'
]);

Route::post('logs', [
	'as' => 'logs.store',
	'uses' => '\App\Http\Controllers\LogController@store'
]);

Route::post('logs/bulk', [
	'as' => 'logs.bulk',
	'uses' => '\App\Http\Controllers\LogController@storeBulk'
]);

Route::get('logs/{key}', [
	'as' => 'logs.show',
	'uses' => '\App\Http\Controllers\LogController@show'
]);

Route::get('all', [
	'as' => 'logs.show',
	'uses' => '\App\Http\Controllers\LogController@all'
]);
