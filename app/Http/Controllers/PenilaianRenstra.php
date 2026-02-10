<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\saskeg_new;
use App\Models\saspro_indikator_new;
use App\Models\sastra_new;
use App\Models\saspro_new;
use App\Models\saspro_indikator_penilaian_new;
use App\Models\ViewRekapSakip;
use Inertia\Inertia;
class PenilaianRenstra extends Controller
{public function index(Request $request)
    {
        // 1. Inisialisasi Query dari Model View
        $query = ViewRekapSakip::query();

        // 2. Logika Pencarian (Search)
        // Mencari string di kolom: Penilaian, Indikator, Saspro, atau Sastra
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_penilaian', 'like', "%{$search}%")
                  ->orWhere('nama_indikator', 'like', "%{$search}%")
                  ->orWhere('nama_saspro', 'like', "%{$search}%")
                  ->orWhere('nama_sastra', 'like', "%{$search}%");
            });
        }

        // 3. Sorting & Pagination
        // Data diurutkan berdasarkan hierarki: Sastra -> Saspro -> Indikator
        $data = $query->orderBy('kode_sastra', 'asc')
                      ->orderBy('kode_saspro', 'asc')
                      ->orderBy('kode_indikator', 'asc')
                      ->paginate(10) // Tampilkan 10 baris per halaman
                      ->withQueryString(); // Penting: agar search tidak hilang saat ganti halaman

        // 4. Kirim ke View React
        return Inertia::render('Rekap/Index', [
            'rekapData' => $data
        ]);
    }
}
