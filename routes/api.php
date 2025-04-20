<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PenitipController;
use App\Http\Controllers\ResetPasswordController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/updateAllPassword', [AuthController::class, 'updateAllPassword']);

Route::post('/register', [AuthController::class, 'register']);


// Route::group(['middleware' => ['auth:sanctum', 'check.roles:penitip,pembeli,organisasi']], function () {
//     Route::post('/forgot-password', [ResetPasswordController::class, 'passwordEmail']);
    
// });

Route::group(['middleware' => ['auth:sanctum', 'check.roles:admin']], function () {
    Route::resource('pegawai', PegawaiController::class);
    Route::resource('jabatan', JabatanController::class);
});


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/getUser', [AuthController::class, 'getUser']);
});

Route::group(['middleware' => ['auth:sanctum', 'check.roles:cs']], function () {
    Route::resource('penitip', PenitipController::class);
});


