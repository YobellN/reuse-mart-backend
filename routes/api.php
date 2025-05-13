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
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\OrganisasiController;
use App\Http\Controllers\AlamatController;
use App\Http\Controllers\DiskusiController;
use App\Models\Organisasi;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/updateAllPassword', [AuthController::class, 'updateAllPassword']);

Route::post('/register', [AuthController::class, 'register']);

Route::post('/register-organisasi', [AuthController::class, 'registerOrganisasi']);

Route::post('/password/reset-link', [ResetPasswordController::class, 'sendResetLink']);
Route::post('/password/validate-token', [ResetPasswordController::class, 'validateToken']);
Route::post('/password/reset', [ResetPasswordController::class, 'resetPassword']);

Route::get('diskusi/getDiskusiProduk/{id}', [DiskusiController::class, 'getDiskusiProduk']);

Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
    Route::get('pegawai', [PegawaiController::class, 'index']);
    Route::post('pegawai', [PegawaiController::class, 'store']);
    Route::delete('pegawai/{id}', [PegawaiController::class, 'destroy']);
    Route::put('pegawai/{id}', [PegawaiController::class, 'update']);
    Route::patch('pegawai/{id}', [PegawaiController::class, 'update']);
    Route::resource('jabatan', JabatanController::class);
    Route::resource('organisasi', OrganisasiController::class);
    // ini reset pegawai
    Route::post('/password/reset-password-pegawai', [ResetPasswordController::class, 'resetPasswordPegawai']);
});


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/getUser', [AuthController::class, 'getUser']);
    Route::post('diskusi/', [DiskusiController::class, 'store']);
});

Route::group(['middleware' => ['auth:sanctum', 'cs']], function () {
    Route::get('penitip', [PenitipController::class, 'index']);
    Route::post('penitip', [PenitipController::class, 'store']);
    Route::delete('penitip/{id}', [PenitipController::class, 'destroy']);
    Route::put('penitip/{id}', [PenitipController::class, 'update']);
    Route::patch('penitip/{id}', [PenitipController::class, 'update']);

    Route::get('diskusi/', [DiskusiController::class, 'index']);
    Route::delete('diskusi/{id}', [DiskusiController::class, 'destroy']);
});

Route::group(['middleware' => ['auth:sanctum', 'gudang']], function () {
    Route::get('gudang/penitipan/produk-titipan', [PenitipanController::class, 'getProdukTitipan']);
});


Route::group(['middleware' => ['auth:sanctum', 'pembeli']], function () {
    Route::resource('penjualan', PenjualanController::class);
    Route::resource('alamat', AlamatController::class);
    Route::post('alamat/gantiAlamatUtama/{id}', [AlamatController::class, 'gantiAlamatUtama']);

    Route::get('diskusi/', [DiskusiController::class, 'index']);
});

Route::group(['middleware' => ['auth:sanctum', 'penitip']], function () {
    Route::get('penitip/penitipan/produk-titipan', [PenitipanController::class, 'getProdukTitipan']);
    Route::patch('penitipan/konfirmasi-perpanjangan/{id}', [PenitipanController::class, 'konfirmasiPerpanjangan']);
    Route::patch('penitipan/konfirmasi-pengambilan/{id}', [PenitipanController::class, 'konfirmasiPengambilan']);
    Route::patch('penitipan/{id}/konfirmasi-donasi', [PenitipanController::class, 'konfirmasiDonasi']);
    Route::get('/get-detail-penjualan-penitip', [PenjualanController::class, 'getDetailPenjualanByPenitip']);
});

Route::group(['middleware' => ['auth:sanctum', 'organisasi']], function () {
    Route::resource('request-donasi', RequestDonasiController::class);
});

Route::resource('/produk', ProdukController::class);
Route::get('produk/getAllProduk', [ProdukController::class, 'getAllProduk']);
Route::get('penitip/{id}', [PenitipController::class, 'show']);
Route::get('get-produk-by-penitip/{id}', [ProdukController::class, 'getProdukByPenitip']);
