<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MyClientController;

Route::apiResource('clients', MyClientController::class);
Route::get('/', function () {
    return view('welcome');
});
