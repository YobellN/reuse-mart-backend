<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PenitipController;
use App\Http\Controllers\PenitipanController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\RequestDonasiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/updateAllPassword', [AuthController::class, 'updateAllPassword']);

Route::post('/register', [AuthController::class, 'register']);


// Route::group(['middleware' => ['auth:sanctum', 'check.roles:penitip,pembeli,organisasi']], function () {
//     Route::post('/forgot-password', [ResetPasswordController::class, 'passwordEmail']);

// });

Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
    Route::resource('pegawai', PegawaiController::class);
    Route::resource('jabatan', JabatanController::class);
});


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/getUser', [AuthController::class, 'getUser']);
});

Route::group(['middleware' => ['auth:sanctum', 'cs']], function () {
    Route::get('penitip', [PenitipController::class, 'index']);
    Route::post('penitip',[PenitipController::class, 'store']);
    Route::delete('penitip/{id}', [PenitipController::class, 'destroy']);
    Route::put('penitip/{id}', [PenitipController::class, 'update']);
    Route::patch('penitip/{id}', [PenitipController::class, 'update']);
});


Route::group(['middleware' => ['auth:sanctum', 'pembeli']], function () {
    Route::resource('penjualan', PenjualanController::class);
});

Route::group(['middleware' => ['auth:sanctum', 'penitip']], function () {
    Route::resource('penitipan', PenitipanController::class);
    Route::resource('produk', ProdukController::class);
    Route::patch('penitipan/{id}/konfirmasi-perpanjangan', [PenitipanController::class, 'konfirmasiPerpanjangan']);
    Route::patch('penitipan/{id}/konfirmasi-pengambilan', [PenitipanController::class, 'konfirmasiPengambilan']);
    Route::patch('penitipan/{id}/konfirmasi-donasi', [PenitipanController::class, 'konfirmasiDonasi']);
});

Route::group(['middleware' => ['auth:sanctum', 'organisasi']], function () {
    Route::resource('request-donasi', RequestDonasiController::class);
});

Route::resource('/produk', ProdukController::class);
Route::get('penitip/{id}', [PenitipController::class, 'show']);
Route::get('get-produk-by-penitip/{id}', [ProdukController::class, 'getProdukByPenitip']);
