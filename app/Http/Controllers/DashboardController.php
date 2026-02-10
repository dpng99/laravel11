<?php

// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use App\Models\Renstra;
use App\Models\Iku;
use App\Models\Renja;
use App\Models\Rkakl;
use App\Models\Dipa;
use App\Models\Renaksi;
use App\Models\Kep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
class DashboardController extends Controller
{
    public function index()
    {
        // Cek apakah tahun sudah dipilih
        // if (!session()->has('tahun_terpilih')) {
        //     return redirect()->route('pilih.tahun');
        // }

        // Set tahun_terpilih ke tahun sekarang jika belum ada di session
        $tahun = session('tahun_terpilih', date('Y'));
        session(['tahun_terpilih' => $tahun]);
        $idSatker = session('id_satker'); // Ambil id_satker dari session
        // $periode = 'P2'; // Periode yang dicek

        if ($tahun == "2024") {
            $periode = "P1";
        } elseif ($tahun >= "2025" && $tahun <= "2029") {
            $periode = "P2";
        }

        // Lanjutkan dengan logika untuk menampilkan data berdasarkan tahun
        // return view('dashboard', ['tahun' => $tahun]);

        $pengumuman = DB::table('sinori_sakip_inbox')->get();
        $jumlahAturan = DB::table('sinori_sakip_literasi')->count(); // Hitung jumlah aturan
        // Data untuk chart
        $id = DB::table('sinori_login')->where('id_satker', $idSatker)->first();
        $data = DB::table('sinori_login')
            ->where('id_kejati', $id->id_kejati)
            ->get();

        $renstraTerisi = Renstra::where('id_satker', $idSatker)->where('id_periode', $periode)->exists();
        $ikuTerisi = Iku::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $renjaTerisi = Renja::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $rkaklTerisi = Rkakl::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $dipaTerisi = Dipa::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $rencanaAksiTerisi = Renaksi::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $keputusanTimSakipTerisi = Kep::where('id_satker', $idSatker)->where('id_tahun', $tahun)->exists();

       $kepList = DB::table('sinori_sakip_keputusan')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_tahun', $tahun)
            ->pluck('id_filesurat', 'id_satker');
        // dd($kepList);
        // Menyelaraskan urutan kepList dengan satker
        $sortedKepList = $data->pluck('id_satker')->map(function ($id) use ($kepList) {
            return $kepList[$id] ?? null;
        });
        // dd($sortedkepList);
        // Memeriksa tahun dan menentukan id_periode
        if ($tahun == "2024") {
            $id_periode = "P1";
        } else {
            $id_periode = "P2";
        }
    // pastikan kolom bernama `keputusan`, sesuaikan jika beda

        // Kirim data ke view
        // return view('dashboard', compact('pengumuman', 'jumlahAturan', 'data', ['tahun' => $tahun]));
        return Inertia::render('Dashboard', [
            'pengumuman' => $pengumuman,
            'jumlahAturan' => $jumlahAturan,
            'data' => $data,
            'tahun' => $tahun,
            'renstraTerisi' => $renstraTerisi,
            'ikuTerisi' => $ikuTerisi,
            'renjaTerisi' => $renjaTerisi,
            'rkaklTerisi' => $rkaklTerisi,
            'dipaTerisi' => $dipaTerisi,
            'rencanaAksiTerisi' => $rencanaAksiTerisi,
            'keputusanTimSakipTerisi' => $keputusanTimSakipTerisi,
            'sortedKepList' => $sortedKepList,

        ]);
    }
}
