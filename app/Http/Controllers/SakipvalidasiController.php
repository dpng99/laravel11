<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SakipvalidasiController extends Controller
{
    public function index()
    {
        // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }

        // Ambil tahun yang dipilih dari session
        $tahun = session('tahun_terpilih');
        return view('sakipvalidasi', ['tahun' => $tahun]);
    }
}
