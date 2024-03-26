<?php

use App\Http\Controllers\getStreams;
use App\Http\Controllers\getUsers;
use App\Http\Controllers\getTopOftheTops;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/get-live-streams', [getStreams::class, 'getLiveStreams']);
Route::get('/get-users', [getUsers::class, 'getUserInfo']);
Route::get('/get-Top-Of-The-Tops', [getTopOftheTops::class, 'fetchData']);
