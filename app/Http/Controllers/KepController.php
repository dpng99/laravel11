<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kep;
 
class kepController extends Controller
{
    public function index()
    {
        // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }
        // Ambil tahun yang dipilih dari session
        $tahun = session('tahun_terpilih');

        // Ambil id_satker dari session
        $idSatker = session('id_satker');

        // Cari kep berdasarkan id_satker
        // Cari kep berdasarkan id_satker dan id_tahun
        $kep = Kep::where('id_satker', $idSatker)
                ->where('id_tahun', $tahun)
                ->first();
        // Kirim variabel $kep ke view
        // return view('kelola.kep', compact('kep'));
        return view('kelola.kep', ['kep' => $kep, 'tahun' => $tahun, 'idSatker' => $idSatker]);
    }

    public function store(Request $request)
    {
        $tahun = session('tahun_terpilih');
        // Validasi input dari form
        $request->validate([
            'nomor_surat' => 'required|string',
            'tanggal_surat' => 'required', // Validasi format tanggal
            'file' => 'required|mimes:pdf|max:2048', // Hanya file PDF
        ]);

        $idSatker = session('id_satker'); // Ambil id_satker dari session

        // Cek apakah satker sudah mengunggah keputusan sebelumnya
        $existing = Kep::where('id_satker', $idSatker)
        ->where('id_tahun', $tahun)
        ->first();
        if ($existing) {
            return redirect()->back()->with('error', 'Satker sudah mengunggah keputusan.');
        }

        // Simpan file ke storage
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $idSatker . '_' . $tahun . '.pdf'; // Unikkan nama file
            // $file->move(public_path('uploads/KEP'), $fileName); // Simpan di folder 'keputusan' di public
            $file->move(base_path('uploads/KEP'), $fileName);
            // Simpan data ke database
            Kep::create([
                'id_satker' => $idSatker,
                'id_filesurat' => $fileName,
                'id_nomorsurat' => $request->input('nomor_surat'),
                'id_tglsurat' => $request->input('tanggal_surat'),
                'id_tahun' => $tahun 
            ]);

            return redirect()->back()->with('success', 'Keputusan berhasil diunggah.');
        }

        return redirect()->back()->with('error', 'Gagal mengunggah file.');
    }
}
