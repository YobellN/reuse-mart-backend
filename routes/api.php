<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PenitipController;
use App\Http\Middleware\CheckRole;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'register']);

Route::resource('pegawai', PegawaiController::class);

// Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
//     Route::resource('pegawai', PegawaiController::class);
// });

Route::group(['middleware' => ['auth:sanctum', 'cs']], function () {
    Route::resource('penitip', PenitipController::class);
});

