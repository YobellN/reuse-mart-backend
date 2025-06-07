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
use App\Http\Controllers\DonasiController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\OrganisasiController;
use App\Http\Controllers\AlamatController;
use App\Http\Controllers\DiskusiController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\NotifController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\DetailKeranjangController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\PembeliController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\DetailPenjualanController;
use App\Http\Controllers\LaporanController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/loginMobile', [AuthController::class, 'loginMobile']);

Route::post('/updateAllPassword', [AuthController::class, 'updateAllPassword']);

Route::post('/register', [AuthController::class, 'register']);

Route::post('/register-organisasi', [AuthController::class, 'registerOrganisasi']);

Route::post('/password/reset-link', [ResetPasswordController::class, 'sendResetLink']);
Route::post('/password/validate-token', [ResetPasswordController::class, 'validateToken']);
Route::post('/password/reset', [ResetPasswordController::class, 'resetPassword']);

Route::get('diskusi/getDiskusiProduk/{id}', [DiskusiController::class, 'getDiskusiProduk']);

Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
    Route::get('pegawai', [PegawaiController::class, 'index']);
    Route::get('pegawai/{id}', [PegawaiController::class, 'show']);
    Route::post('pegawai', [PegawaiController::class, 'store']);
    Route::delete('pegawai/{id}', [PegawaiController::class, 'destroy']);
    Route::put('pegawai/{id}', [PegawaiController::class, 'update']);
    Route::patch('pegawai/{id}', [PegawaiController::class, 'update']);
    Route::resource('jabatan', JabatanController::class);
    Route::resource('organisasi', OrganisasiController::class);
    // ini reset pegawai
    Route::post('/password/reset-password-pegawai', [ResetPasswordController::class, 'resetPasswordPegawai']);
});

Route::group(['middleware' => ['auth:sanctum', 'owner']], function () {
    Route::get('request-donasi-aktif', [RequestDonasiController::class, 'getActiveRequest']);
    Route::get('owner/request-donasi/{id}', [RequestDonasiController::class, 'show']);
    Route::get('produk-untuk-donasi', [ProdukController::class, 'getProdukUntukDonasi']);
    Route::get('donasi', [DonasiController::class, 'index']);
    Route::post('donasi', [DonasiController::class, 'store']);
    Route::get('laporan-penjualan-per-kategori', [LaporanController::class, 'laporanPenjualanKategori']);
    Route::get('laporan-barang-hangus', [LaporanController::class, 'laporanBarangHangus']);
});


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/getUser', [AuthController::class, 'getUser']);
    Route::post('diskusi/', [DiskusiController::class, 'store']);
    Route::post('/notif', [NotifController::class, 'notifyUser']);
    Route::post('/updateFCMToken', [AuthController::class, 'updateFCMToken']);
});

Route::group(['middleware' => ['auth:sanctum', 'cs']], function () {
    Route::get('cs/penitip', [PenitipController::class, 'index']);
    Route::get('cs/penitip/{id}', [PenitipController::class, 'show']);
    Route::post('cs/penitip', [PenitipController::class, 'store']);
    Route::delete('cs/penitip/{id}', [PenitipController::class, 'destroy']);
    Route::put('cs/penitip/{id}', [PenitipController::class, 'update']);
    Route::patch('cs/penitip/{id}', [PenitipController::class, 'update']);

    Route::get('diskusi/', [DiskusiController::class, 'index']);
    Route::delete('diskusi/{id}', [DiskusiController::class, 'destroy']);
    
    // VERIFIKASI PEMBAYARAN
    Route::post('konfirmasiPembayaran/{id_penjualan}', [PembayaranController::class, 'konfirmasiPembayaran']);
    Route::post('tolakPembayaran/{id_penjualan}', [PembayaranController::class, 'tolakPembayaran']);
    Route::get('getPembayaranPending', [PembayaranController::class, 'getPembayaranPending']);
    Route::get('getPembayaranBukanPending', [PembayaranController::class, 'getPembayaranBukanPending']);
});

Route::group(['middleware' => ['auth:sanctum', 'gudang']], function () {
    Route::get('gudang/penitipan/produk-titipan', [PenitipanController::class, 'getProdukTitipan']);
    Route::patch('penitipan/pengambilan-produk-titipan/{id}', [PenitipanController::class, 'pengambilanProdukTitipan']);
    Route::get('gudang/penjualan', [PenjualanController::class, 'index']);
    Route::patch('gudang/penjadwalan-pengiriman/{id}', [PengirimanController::class, 'update']);
    Route::get('gudang/get-all-kurir', [PegawaiController::class, 'getAllKurir']);
    Route::get('gudang/get-pengiriman/{id}', [PengirimanController::class, 'show']);
    Route::patch('gudang/penjadwalan-pengambilan/{id}', [PengirimanController::class, 'jadwalkanPengambilan']);
    Route::patch('gudang/konfirmasi-pengambilan-transaksi/{id}', [PengirimanController::class, 'konfirmasiPengambilanTransaksi']);
    Route::patch('gudang/tambah-poin-saldo/{id}', [PenjualanController::class, 'tambahPoinSaldo']);
    Route::get('penitipan/all', [PenitipanController::class, 'index']);
    Route::get('penitipan/detail/{id}', [PenitipanController::class, 'show']);

    //untuk input form penitipan baru
    Route::get('gudang/get-pegawai-qc', [PenitipanController::class, 'getPegawaiQC']);
    Route::get('gudang/get-pegawai-hunter', [PenitipanController::class, 'getPegawaiHunter']);
    Route::get('penitip', [PenitipController::class, 'index']);

    //transaksi penitipan di gudang
    Route::post('gudang/new-penitipan', [PenitipanController::class, 'store']);

    //edit penitipan
    Route::post('gudang/edit-penitipan/{id}', [PenitipanController::class, 'update']);
});

// untuk kurir
Route::group(['middleware' => ['auth:sanctum', 'kurir']], function () {
    Route::patch('kurir/konfirmasi-mengirim-kurir/{id}', [PengirimanController::class, 'dikirimKurir']);
});

Route::group(['middleware' => ['auth:sanctum', 'pembeli']], function () {
    Route::resource('penjualan', PenjualanController::class);
    Route::resource('alamat', AlamatController::class);
    Route::post('alamat/gantiAlamatUtama/{id}', [AlamatController::class, 'gantiAlamatUtama']);
    Route::get('diskusi/', [DiskusiController::class, 'index']);

    // route untuk transaksi
    Route::post('keranjang', [KeranjangController::class, 'store']);
    Route::get('detail-keranjang', [DetailKeranjangController::class, 'show']);
    Route::post('detail-keranjang', [DetailKeranjangController::class, 'store']);
    Route::put('detail-keranjang', [DetailKeranjangController::class, 'update']);
    Route::delete('detail-keranjang/{id}', [DetailKeranjangController::class, 'destroy']);
    Route::post('detail-keranjang/destroy-all', [DetailKeranjangController::class, 'destroyAll']);

    // POIN
    Route::get('poinPembeli', [PembeliController::class, 'getPoinPembeli']);
    Route::post('getTotalHarga', [DetailKeranjangController::class, 'getTotalHarga']);

    // PEMBAYARAN
    Route::post('pembayaran', [PembayaranController::class, 'store']);

    // TAGIHAN
    Route::get('tagihan/{id}', [PenjualanController::class, 'getTagihanPembayaran']);

    // DETAIL PENJUALAN
    Route::resource('detail-penjualan', DetailPenjualanController::class);
    Route::get('cekStok', [DetailKeranjangController::class, 'cekStok']);

    //RATING PRODUK
    Route::post('rate-produk-pembelian/{id}', [ProdukController::class, 'rateProdukPembelian']);

});

Route::group(['middleware' => ['auth:sanctum', 'penitip']], function () {
    Route::get('penitip/penitipan/produk-titipan', [PenitipanController::class, 'getProdukTitipan']);
    Route::patch('penitipan/konfirmasi-perpanjangan/{id}', [PenitipanController::class, 'konfirmasiPerpanjangan']);
    Route::patch('penitipan/konfirmasi-pengambilan/{id}', [PenitipanController::class, 'konfirmasiPengambilan']);
    Route::patch('penitipan/konfirmasi-donasi/{id}', [PenitipanController::class, 'konfirmasiDonasi']);
    Route::get('/get-detail-penjualan-penitip', [PenjualanController::class, 'getDetailPenjualanByPenitip']);
});

Route::group(['middleware' => ['auth:sanctum', 'organisasi']], function () {
    Route::resource('request-donasi', RequestDonasiController::class);
});

Route::resource('/produk', ProdukController::class);
Route::get('produk/getAllProduk', [ProdukController::class, 'getAllProduk']);
//JANGAN DI HAPUS ATO DIUBAH
Route::get('penitip/{id}', [PenitipController::class, 'show']);
Route::get('informasi-penitip/{id}', [PenitipController::class, 'showNullableRating']);
Route::get('get-produk-by-penitip/{id}', [ProdukController::class, 'getProdukByPenitip']);
Route::get('kategori-produk', [KategoriController::class, 'index']);

// buat tes
Route::get('komisi/{id}', [PenjualanController::class,  'tesKomisi']);
Route::post('update-all-komisi', [PenjualanController::class, 'updateAllKomisi']);
Route::get('tes-tambah-saldo', [PenjualanController::class, 'tesTambahSaldo']);
Route::get('tes-tambah-poin', [PenjualanController::class, 'tesTambahPoin']);