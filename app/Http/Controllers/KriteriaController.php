<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\lke_komponen;
use App\Models\lke_subkomponens;
use App\Models\lke_buktidukung;
use Illuminate\Support\Facades\DB;
class KriteriaController extends Controller
{
        public function create()
    {   
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }
        // Ambil tahun yang dipilih dari session
        $tahun = session('tahun_terpilih');
        $komponen = lke_komponen::all();
        return view('kelola.components.input_kriteria', compact('komponen', 'tahun'));
    }
    public function store(Request $request)
    {if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }
        // Ambil tahun yang dipilih dari session
        $tahun = session('tahun_terpilih');
        $request->validate([
            'komponen_id' => 'required|exists:lke_komponen,id',
            'subkomponen_id' => 'required|exists:lke_subkomponen,id',
            'range_nilai' => 'required|string|max:255',
            'bentuk_bukti' => 'required|string|max:255',
            'bobot' => 'required|numeric',
            'kriteria' => 'required|string',
        ]);

        DB::table('lke_kriteria')->insert([
            'komponen_id' => $request->komponen_id,
            'subkomponen_id' => $request->subkomponen_id,
            'range_nilai' => $request->range_nilai,
            'bentuk_bukti' => $request->bentuk_bukti,
            'bobot' => $request->bobot,
            'kriteria' => $request->kriteria,
        ]);

        return redirect()->route('kriteria.create')->with('success', 'Kriteria berhasil ditambahkan.');
    }
    public function getSubkomponen($id)
    {if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }
        // Ambil tahun yang dipilih dari session
        $tahun = session('tahun_terpilih');
        $subkomponen = lke_subkomponens::where('id_komponen', $id)->get();
        return response()->json($subkomponen);
    }
}
