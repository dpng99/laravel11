<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Indikator;
use App\Models\Bidang;
use App\Models\Saspro;
use session;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class KeloladataController extends Controller
{
    public function index()
    {
        // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }
        // Ambil tahun yang dipilih dari session
        $tahun = session('tahun_terpilih');
        // $saspros = Saspro::select('id','link', 'saspro_nama', 'saspro_penjelasan')->get();
        // $saspros = Saspro::orderBy('link', 'asc')->paginate(10);
        // return view('keloladata', compact('saspros'));

        // Ambil data dari tabel sinori_sakip_bidang
        $bidangs = Bidang::orderBy('bidang_lokasi')->paginate(10);
        $bidangall = Bidang::select('id', 'bidang_nama', 'rumpun')->where('hide',0)->get();

        $indikators = Indikator::with('bidangById', 'saspro')->orderBy('tahun', 'desc')->orderBy('link', 'asc')->paginate(10);
        $saspros = Saspro::with('bidang')->orderBy('tahun', 'desc')->orderBy('link', 'asc')->paginate(10); // atau ->get() jika tidak pakai pagination
        $saspro1 = Saspro::with('bidang')->orderBy('tahun', 'desc')->orderBy('link', 'asc')->get();

        // Kirim data ke view
        // return view('keloladata', compact('bidangs'));
        return Inertia::render('KelolaData', ['tahun' => $tahun, 'bidangs' => $bidangs, 'saspros' => $saspros, 'bidangall' => $bidangall, 'indikators' => $indikators, 'saspro1' => $saspro1]);
    }

    // public function Indikator(Request $request)
    // {
    //     // Validasi data
    //     $validatedData = $request->validate([
    //         'id_bidang' => 'required|string',
    //         'tipe' => 'required|in:lag,leg',
    //         'link' => 'required|numeric',
    //         'lingkup' => 'required|numeric',
    //         'indikator_nama' => 'required|string',
    //         'indikator_pembilang' => 'required|string',
    //         'indikator_penyebut' => 'required|string',
    //         'indikator_penjelasan' => 'required|string',
    //         'matrix' => 'required|string',
    //     ]);

    //     // Simpan data ke dalam database (misal ke tabel indikator)
    //     Indikator::create($validatedData);

    //     // Redirect atau return response sesuai kebutuhan
    //     return redirect()->route('keloladata')->with('success', 'Data Indikator berhasil disimpan');
    // }

    public function storeIndikator(Request $request)
    {
        $request->validate([
            'bidang' => 'required|exists:sinori_sakip_bidang,id',
            // 'link' => 'required',
            'lingkup' => 'required|numeric',
            'id_saspro' => 'required|exists:sinori_sakip_saspro,id',
            'indikator_nama' => 'required|string|max:255',
            'indikator_pembilang' => 'required|string|max:255',
            'indikator_penyebut' => 'required|string|max:255',
            'indikator_penjelasan' => 'required|string',
            'sub_indikator' => 'nullable|string',
            // 'indikator_penghitungan' => 'nullable|string',
            'tahun' => 'nullable|string',
            'tren' => 'nullable|string',
        ]);

        Indikator::create([
            // 'id_bidang' => $request->bidang,
            'link' => $request->bidang,
            'lingkup' => $request->lingkup,
            'id_saspro' => $request->id_saspro,
            'id_saspro' => $request->id_saspro,
            'indikator_nama' => $request->indikator_nama,
            'indikator_pembilang' => $request->indikator_pembilang,
            'indikator_penyebut' => $request->indikator_penyebut,
            'indikator_penjelasan' => $request->indikator_penjelasan,
            'sub_indikator' => $request->sub_indikator,
            'tahun' => $request->tahun,
            'indikator_penghitungan' => $request->indikator_penghitungan,
            'tren' => $request->tren,
        ]);
        // dd($request->all());
        return Redirect::back()->with('success', 'Data Indikator berhasil disimpan.');
    }

    public function updateIndikator(Request $request, $id)
    {
        $indikator = Indikator::findOrFail($id);
        $indikator->link = $request->bidang;
        $indikator->lingkup = $request->lingkup;
        $indikator->id_saspro = $request->id_saspro;
        $indikator->indikator_nama = $request->indikator_nama;
        $indikator->indikator_pembilang = $request->indikator_pembilang;
        $indikator->indikator_penyebut = $request->indikator_penyebut;
        $indikator->indikator_penjelasan = $request->indikator_penjelasan;
        $indikator->sub_indikator = $request->sub_indikator;
        $indikator->tahun = $request->tahun1;
        $indikator->indikator_penghitungan = $request->indikator_penghitungan;
        $indikator->tren = $request->tren;

        $indikator->save();

        return Redirect::back()->with('success', 'Data Indikator berhasil diperbarui.');
    }

    public function deleteIndikator($id)
    {
        $indikator = Indikator::find($id);

        if ($indikator) {
            $indikator->delete();
            return redirect()->back()->with('success', 'Indikator berhasil dihapus.');
        }

        return Redirect::back()->with('error', 'Indikator tidak ditemukan.');
    }


    public function Bidang(Request $request)
    {
        $validatedData = $request->validate([
            'bidang_nama' => 'required|string|max:255',
            'bidang_level' => 'required|integer',
            'bidang_lokasi' => 'required|integer',
            'rumpun' => 'required|integer',
            'hide' => 'required|integer|in:0,1',
        ]);
        // dd($validatedData);
        // Simpan data menggunakan Eloquent
        Bidang::create($validatedData);


        return redirect()->route('keloladata')
            ->with('success', 'Data bidang berhasil disimpan!');
    }

    public function edit($id)
    { // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }
        // Ambil tahun yang dipilih dari session
        $tahun = session('tahun_terpilih');

        $bidang = Bidang::findOrFail($id);
        $bidangs = Bidang::all(); // Tetap kirimkan semua data untuk tabel

        return Inertia::render('KelolaData', ['tahun' => $tahun, 'bidang' => $bidangs]);
    }

    public function destroy($id)
    {
        $bidang = Bidang::findOrFail($id);
        $bidang->delete();

        return redirect()->route('KelolaData')->with('success', 'Data bidang berhasil dihapus.');
    }

    public function storeOrUpdateBidang(Request $request)
    {
        // Validasi input
        $request->validate([
            'id' => 'nullable|integer',
            'bidang_nama' => 'required|string|max:255',
            'bidang_level' => 'required|integer',
            'bidang_lokasi' => 'required|integer',
            'rumpun' => 'required|integer',
            'hide' => 'required|boolean',
        ]);

        // Cek apakah data sudah ada
        $bidang = Bidang::find($request->input('id'));

        if ($bidang) {
            // Update data jika ditemukan
            $bidang->update([
                'bidang_nama' => $request->input('bidang_nama'),
                'bidang_level' => $request->input('bidang_level'),
                'bidang_lokasi' => $request->input('bidang_lokasi'),
                'rumpun' => $request->input('rumpun'),
                'hide' => $request->input('hide'),
            ]);

            $message = 'Data berhasil diperbarui!';
        } else {
            // Buat data baru jika belum ada
            Bidang::create([
                'bidang_nama' => $request->input('bidang_nama'),
                'bidang_level' => $request->input('bidang_level'),
                'bidang_lokasi' => $request->input('bidang_lokasi'),
                'rumpun' => $request->input('rumpun'),
                'hide' => $request->input('hide'),
            ]);

            $message = 'Data berhasil disimpan!';
        }

        // Redirect dengan pesan sukses
        return Redirect::route('keloladata')->with('success', $message);
    }

    public function saspro(Request $request)
    {
        // Validasi input
        $request->validate([
            'link' => 'required|integer',
            'saspro_nama' => 'required|string|max:255',
            'penjelasan_saspro' => 'required|string',
            'tahun' => 'required|string',
            'hide' => 'required|integer|in:0,1',

        ]);

        // Simpan data ke database
        Saspro::create([
            'link' => $request->input('link'),
            'saspro_nama' => $request->input('saspro_nama'),
            'saspro_penjelasan' => $request->input('penjelasan_saspro'),
            'lingkup' => '0',
            'tahun' => $request->input('tahun'),
            'hide' => $request->input('hide'),
        ]);

        // Redirect dengan pesan sukses
        return redirect()->route('keloladata')->with('success', 'Data Saspro berhasil disimpan!');
    }

    // Simpan Data Saspro
    public function sasproStore(Request $request)
    {
        $request->validate([
            'link' => 'required|string|max:255',
            'saspro_nama' => 'required|string|max:255',
            'penjelasan_saspro' => 'required|string',
            'tahun' => 'required|string',
            'hide' => 'required|integer|in:0,1',
        ]);

        Saspro::create([
            'link' => $request->input('link'),
            'saspro_nama' => $request->input('saspro_nama'),
            'saspro_penjelasan' => $request->input('penjelasan_saspro'),
            'lingkup' => '0',
            'tahun' => $request->input('tahun'),
            'hide' => $request->input('hide'),
        ]);

        return Redirect::back()->with('success', 'Data Saspro berhasil disimpan!');
    }

    // Update Saspro
    public function sasproUpdate(Request $request, $id)
    {
        $request->validate([
            'link' => 'required|string',
            'saspro_nama' => 'required|string',
            'penjelasan_saspro' => 'required|string',
            'tahun' => 'required|string',
            'hide' => 'required|integer|in:0,1',
        ]);

        $saspro = Saspro::findOrFail($id);
        $saspro->update([
            'link' => $request->link,
            'saspro_nama' => $request->saspro_nama,
            'saspro_penjelasan' => $request->penjelasan_saspro,
            'tahun' => $request->tahun,
            'hide' => $request->hide,
        ]);

        return Redirect::back()->with('success', 'Data Saspro berhasil diperbarui!');
    }

    // public function storeSaspro(Request $request)
    // {
    //     $request->validate([
    //         'link' => 'required|string|max:255',
    //         'saspro_nama' => 'required|string|max:255',
    //         'penjelasan_saspro' => 'required|string',
    //     ]);

    //     Saspro::create([
    //         'link' => $request->input('link'),
    //         'saspro_nama' => $request->input('saspro_nama'),
    //         'saspro_penjelasan' => $request->input('penjelasan_saspro'),
    //         'lingkup' => '0',
    //     ]);

    //     return redirect()->route('keloladata')->with('success', 'Data Saspro berhasil disimpan!');
    // }

    // Hapus Saspro
    public function destroySaspro($id)
    {
        // Cari data berdasarkan ID
        $saspro = Saspro::find($id);

        // Periksa apakah data ditemukan
        if (!$saspro) {
            return Redirect::back()->with('error', 'Data Saspro tidak ditemukan.');
        }

        // Hapus data
        $saspro->delete();

        return Redirect::back()->with('success', 'Data Saspro berhasil dihapus.');
    }
}
