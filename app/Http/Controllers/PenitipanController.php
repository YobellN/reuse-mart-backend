<?php

namespace App\Http\Controllers;

use App\Models\FotoProduk;
use Illuminate\Support\Facades\Storage;;
use App\Models\Produk;
use App\Models\Pegawai;
use App\Models\Penitip;
use App\Models\Penitipan;
use App\Models\DetailPenitipan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Omaressaouaf\LaravelIdGenerator\IdGenerator;
use App\Services\FcmChannel;

class PenitipanController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $penitipan = Penitipan::with('penitip.user', 'qc.user', 'hunter.user', 'detailPenitipan.produk.fotoProduk',  'detailPenitipan.produk.kategori')->get();

        return response()->json([
            'message' => 'Data Penitipan',
            'data' => $penitipan
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        DB::beginTransaction();

        try {
            $request->validate([
                //bagian penitipan
                'id_penitip' => 'required|exists:penitip,id_penitip',
                'id_qc' => 'required|exists:pegawai,id_pegawai',
                'id_hunter' => 'nullable|exists:pegawai,id_pegawai',
              
                //bagian array produk
                'produk' => 'required|array|min:1',
                'produk.*.nama_produk' => 'required|string|min:3',
                'produk.*.deskripsi_produk' => 'required|string',
                'produk.*.id_kategori' => 'required|exists:kategori_produk,id_kategori',
                'produk.*.harga_produk' => 'required|numeric',
                'produk.*.waktu_garansi' => 'nullable|date',
                //bagian foto produk
                'produk.*.foto_produk' => 'required|array|min:2|max:10',
                'produk.*.foto_produk.*.path_foto' => 'required|file|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',

            ], [
                'id_penitip.required' => 'Penitip tidak boleh kosong',
                'id_penitip.exists' => 'Penitip tidak ditemukan',
                'id_qc.required' => 'QC tidak boleh kosong',
                'id_qc.exists' => 'QC tidak ditemukan',
                'id_hunter.exists' => 'Hunter tidak ditemukan',
               
                'produk.required' => 'Produk tidak boleh kosong',
                'produk.min' => 'Minimal 1 produk',
                'produk.*.nama_produk.required' => 'Nama produk tidak boleh kosong',
                'produk.*.deskripsi_produk.required' => 'Deskripsi produk tidak boleh kosong',
                'produk.*.id_kategori.required' => 'Kategori produk tidak boleh kosong',
                'produk.*.id_kategori.exists' => 'Kategori produk tidak ditemukan',
                'produk.*.harga_produk.required' => 'Harga produk tidak boleh kosong',
                'produk.*.harga_produk.numeric' => 'Harga produk harus berupa angka',
                'produk.*.waktu_garansi.date' => 'Waktu garansi harus berupa tanggal',
                'produk.*.foto_produk.required' => 'Foto produk tidak boleh kosong',
                'produk.*.foto_produk.min' => 'Minimal 2 foto produk',
                'produk.*.foto_produk.max' => 'Maksimal 10 foto produk',
                'produk.*.foto_produk.*.path_foto.image' => 'Foto produk harus berupa gambar',
                'produk.*.foto_produk.*.path_foto.max' => 'Maksimal 2MB untuk foto produk',
            ]);


            //bagian insert data penitipan saja

            $tanggalSekarang = Carbon::now();
            $lastId = Penitipan::orderBy('id_penitipan', 'desc')->value('id_penitipan');

            $nextNumber = $lastId
                ? ((int) substr($lastId, -4)) + 1
                : 1;

            $prefix = now()->format('y.m') . '.';

            $id_penitipan = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
           
            $tenggat_penitipan = $tanggalSekarang->copy()->addDays(30);
            $tenggat_pengambilan = $tenggat_penitipan->copy()->addDays(7);

            $penitipan =  Penitipan::create([
                'id_penitipan' => $id_penitipan,
                'id_penitip' => $request->id_penitip,
                'id_qc' => $request->id_qc,
                'id_hunter' => $request->id_hunter,
                'tanggal_penitipan' => $tanggalSekarang->format('Y-m-d H:i:s'),
                'tenggat_penitipan' => $tenggat_penitipan,
                'tenggat_pengambilan' => $tenggat_pengambilan,
            ]);

            //bagian insert data produknya
            $status_hunting = $penitipan->id_hunter ? 1 : 0;

            foreach ($request->produk as $item) {

                $id_produk = IdGenerator::generate(Produk::class, 'id_produk', 4, 'K');

                $produk = Produk::create([
                    'id_produk' => $id_produk,
                    'nama_produk' => $item['nama_produk'],
                    'deskripsi_produk' => $item['deskripsi_produk'],
                    'id_kategori' => $item['id_kategori'],
                    'harga_produk' => (float) $item['harga_produk'],
                    'status_ketersediaan' => 1,
                    'waktu_garansi' => $item['waktu_garansi'] ?? null,
                    'status_produk_hunting' => $status_hunting,
                ]);

                //insert ke tabel detail_penitipan
                DetailPenitipan::create([
                    'id_penitipan' => $penitipan->id_penitipan,
                    'id_produk' => $produk->id_produk,
                ]);

                //bagian insert foto
                foreach ($item['foto_produk'] as $i => $foto) {

                    $file = $foto['path_foto'];

                    if (!($file instanceof \Illuminate\Http\UploadedFile)) {
                        return response()->json([
                            'status_code' => 422,
                            'message' => 'File tidak valid',
                            'errors' => ['path_foto' => 'File harus berupa gambar yang diupload'],
                        ], 422);
                    }

                    $file_name = $id_produk . '_' . ($i + 1) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('foto_produk', $file_name, 'public');

                    FotoProduk::create(
                        [
                            'id_produk' => $produk->id_produk,
                            'path_foto' => str_replace('foto_produk/', '', $path),
                            'thumbnail' => $i === 0 ? 1 : 0,
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Penitipan berhasil ditambahkan',
                'data' => $penitipan
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagals: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $penitipan = Penitipan::with('penitip.user', 'qc.user', 'hunter.user', 'detailPenitipan.produk.kategori', 'detailPenitipan.produk.fotoProduk')->find($id);

        if (!$penitipan) {
            return response()->json([
                'message' => 'Penitipan tidak ditemukan',
                'errors'  => ['id' => 'Penitipan tidak ditemukan'],
            ], 404);
        }

        return response()->json([
            'message' => 'Data Penitipan',
            'data' => $penitipan
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, $id_penitipan)
{
    DB::beginTransaction();

    try {
        $request->validate([
            'id_penitip' => 'sometimes|exists:penitip,id_penitip',
            'id_qc' => 'sometimes|exists:pegawai,id_pegawai',
            'id_hunter' => 'nullable|exists:pegawai,id_pegawai',

            'produk' => 'sometimes|array|min:1',
            'produk.*.nama_produk' => 'sometimes|string|min:3',
            'produk.*.deskripsi_produk' => 'sometimes|string',
            'produk.*.id_kategori' => 'sometimes|exists:kategori_produk,id_kategori',
            'produk.*.harga_produk' => 'sometimes|numeric',
            'produk.*.waktu_garansi' => 'nullable|date',
            'produk.*.foto_produk' => 'sometimes|array|min:2|max:10',
            'produk.*.foto_produk.*.path_foto' => 'required|file|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $penitipan = Penitipan::findOrFail($id_penitipan);

        $penitipan->fill([
            'id_penitip' => $request->input('id_penitip', $penitipan->id_penitip),
            'id_qc' => $request->input('id_qc', $penitipan->id_qc),
            'id_hunter' => $request->input('id_hunter', $penitipan->id_hunter),
        ])->save();

        if ($request->has('produk')) {
            $status_hunting = $penitipan->id_hunter ? 1 : 0;

            $produkLama = $penitipan->produk;
            foreach ($produkLama as $produk) {
                foreach ($produk->foto_produk as $foto) {
                    Storage::disk('public')->delete('foto_produk/' . $foto->path_foto);
                    $foto->delete();
                }
                $produk->delete();
            }

            DetailPenitipan::where('id_penitipan', $id_penitipan)->delete();

            foreach ($request->produk as $item) {
                $id_produk = IdGenerator::generate(Produk::class, 'id_produk', 4, 'K');

                $produk = Produk::create([
                    'id_produk' => $id_produk,
                    'nama_produk' => $item['nama_produk'],
                    'deskripsi_produk' => $item['deskripsi_produk'],
                    'id_kategori' => $item['id_kategori'],
                    'harga_produk' => (float) $item['harga_produk'],
                    'status_ketersediaan' => 1,
                    'waktu_garansi' => $item['waktu_garansi'] ?? null,
                    'status_produk_hunting' => $status_hunting,
                ]);

                DetailPenitipan::create([
                    'id_penitipan' => $penitipan->id_penitipan,
                    'id_produk' => $produk->id_produk,
                ]);

                foreach ($item['foto_produk'] as $i => $foto) {
                    $file = $foto['path_foto'];

                    $file_name = $id_produk . '_' . ($i + 1) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('foto_produk', $file_name, 'public');

                    FotoProduk::create([
                        'id_produk' => $produk->id_produk,
                        'path_foto' => str_replace('foto_produk/', '', $path),
                        'thumbnail' => $i === 0 ? 1 : 0,
                    ]);
                }
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Penitipan berhasil diperbarui',
            'data' => $penitipan
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Gagal: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getProdukTitipan(Request $request)
    {
        $user = $request->user();
        $idPenitip = optional($user->penitip)->id_penitip;
        $status_produk = $request->input('status_produk');

        $produk = Produk::with([
            'kategori',
            'fotoProduk',
            'detailPenitipan.penitipan.penitip.user',
            'detailPenitipan.penitipan.qc.user',
            'detailPenitipan.penitipan.hunter.user',
        ]);

        if ($idPenitip) {
            $produk->whereHas('detailPenitipan.penitipan', function ($query) use ($idPenitip) {
                $query->where('id_penitip', $idPenitip);
            });
        }

        if ($status_produk === 'Sedang Dijual') {
            $produk->whereNull('status_akhir_produk');
        } elseif ($status_produk === 'Tidak Laku') {
            $produk->where('status_akhir_produk', 'Tidak Laku')
                ->whereHas('detailPenitipan.penitipan', function ($query) {
                    $query->where('tenggat_penitipan', '<', now());
                });
        } elseif ($status_produk === 'Akan Diambil') {
            $produk->whereIn('status_akhir_produk', ['Akan Diambil', 'Diambil']);
        } elseif (!is_null($status_produk)) {
            $produk->where('status_akhir_produk', $status_produk);
        }

        $data = $produk->get();

        return response()->json([
            'message' => 'Berhasil mendapatkan data penitipan',
            'data' => $data
        ], 200);
    }

    public function getPegawaiQC(Request $request)
    {
        $pegawai = Pegawai::with('user', 'jabatan')->where('id_jabatan', 3)->get();

        if (!$pegawai) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
                'errors'  => ['id' => 'Pegawai tidak ditemukan'],
            ], 404);
        }

        return response()->json([
            'message' => 'Data Pegawai',
            'data' => $pegawai
        ], 200);
    }

    public function getPegawaiHunter(Request $request)
    {
        $pegawai = Pegawai::with('user', 'jabatan')->where('id_jabatan', 1)->get();

        if (!$pegawai) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
                'errors'  => ['id' => 'Pegawai tidak ditemukan'],
            ], 404);
        }

        return response()->json([
            'message' => 'Data Pegawai',
            'data' => $pegawai
        ], 200);
    }


    public function konfirmasiPerpanjangan(string $id)
    {
        $penitipan = Penitipan::find($id);
        if (!$penitipan) {
            return response()->json([
                'message' => 'Penitipan tidak ditemukan',
            ], 404);
        }

        if ($penitipan->status_perpanjangan == 1) {
            return response()->json([
                'message' => 'Penitipan hanya dapat di perpanjang 1 kali',
            ], 404);
        }

        $penitipan->status_perpanjangan = 1;
        $tenggat = Carbon::parse($penitipan->tenggat_penitipan);
        $penitipan->tenggat_penitipan = $tenggat->addDays(30);
        $penitipan->tenggat_pengambilan = $tenggat->copy()->addDays(7);
        $penitipan->save();

        return response()->json([
            'message' => 'Penitipan berhasil di perpanjang',
            'data' => $penitipan
        ], 200);
    }

    public function konfirmasiPengambilan(string $id)
    {
        $produk = Produk::find($id);

        if (!$produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        if ($produk->status_akhir_produk == 'Diambil') {
            return response()->json([
                'message' => 'Produk sudah diambil',
            ], 404);
        }

        if ($produk->status_akhir_produk == 'Akan Diambil') {
            return response()->json([
                'message' => 'Anda sudah mengajukan konfirmasi pengambilan',
            ], 404);
        }

        $produk->status_akhir_produk = "Akan Diambil";
        $produk->save();

        return response()->json([
            'message' => 'Produk berhasil dikonfirmasi pengambilan',
            'data' => $produk
        ], 200);
    }

    public function konfirmasiDonasi(string $id)
    {
        $produk = Produk::find($id);

        if (!$produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        if ($produk->status_akhir_produk == 'Produk untuk donasi') {
            return response()->json([
                'message' => 'Produk sudah dikonfirmasi untuk donasi',
            ], 404);
        }

        $produk->status_akhir_produk = "Produk untuk donasi";
        $produk->save();

        return response()->json([
            'message' => 'Produk berhasil dikonfirmasi untuk donasi',
            'data' => $produk
        ], 200);
    }

    public function pengambilanProdukTitipan(string $id)
    {
        $produk = Produk::find($id);

        if (!$produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        if ($produk->status_akhir_produk == 'Diambil') {
            return response()->json([
                'message' => 'Produk sudah diambil',
            ], 404);
        }

        $produk->status_akhir_produk = "Diambil";
        $produk->detailPenitipan()->update([
            'tanggal_pengambilan' => now(),
        ]);
        $produk->save();


        return response()->json([
            'message' => 'Produk berhasil dikonfirmasi pengambilan',
            'data' => $produk
        ], 200);
    }
}
