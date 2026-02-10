<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Bidang;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
class SakipwilController extends Controller
{
    public function index()
    {
        // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }

        // Ambil nilai dari session
        $id_satker = session('id_satker');
        $tahun = session('tahun_terpilih');
        $level = session('id_sakip_level');

        $bidangs = Bidang::where('bidang_lokasi', $level)
            ->where('bidang_level', '!=', null)
            ->orderBy('bidang_level', 'asc')
            ->get();

        // Ambil data pengguna
        $id = DB::table('sinori_login')->where('id_satker', $id_satker)->first();

        // Ambil data satkernama dan id_satker sesuai id_kejati
        // $data = DB::table('sinori_login')
        //     ->where('id_kejati', $id->id_kejati)
        //     ->get();


        // Cek apakah kode satker adalah 999999
        if (in_array($id_satker, [999999, 'admin', 'Pengawasan', 'Panev','menpanrb'])) {
            // Ambil semua satker, urutkan berdasarkan id_kejati
            $data = DB::table('sinori_login')
                ->whereNotIn('id_satker', [888881, 888882, 'admin', 999999, 'Pengawasan', 'Panev','menpanrb'])
                ->where('id_satker', 'not like', 'was%')
                ->where('id_satker', 'not like', '00budi')
                ->where('id_kejati', 'not like', '87') // dikecualikan
                ->orderBy('id_kejati', 'asc')
                ->orderBy('id_kejari', 'asc')
                ->get();
        } else {
            // Ambil data satkernama dan id_satker sesuai id_kejati
            $data = DB::table('sinori_login')
                ->where('id_kejati', $id->id_kejati)
                ->where('id_satker', 'not like', 'was%') // dikecualikan
                // ->orderBy('id_satker', 'asc')
                ->get();
        }

        // Ganti underscore dengan spasi dan ambil id_satker
        $satkernamaList = $data->pluck('satkernama')->map(function ($satkernama) {
            return str_replace('_', ' ', $satkernama);
        });

        // Ambil keputusan berdasarkan satker dan tahun
        $kepList = DB::table('sinori_sakip_keputusan')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_tahun', $tahun)
            ->pluck('id_filesurat', 'id_satker');
        // dd($kepList);
        // Menyelaraskan urutan kepList dengan satker
        $sortedKepList = $data->pluck('id_satker')->map(function ($id) use ($kepList) {
            return $kepList[$id] ?? null;
        });
        // Memeriksa tahun dan menentukan id_periode
        if ($tahun == "2024") {
            $id_periode = "P1";
        } else {
            $id_periode = "P2";
        }

        $renstraList = DB::table('sinori_sakip_renstra')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_periode', $id_periode)
            ->pluck('id_filename', 'id_satker');

        $sortedRenstraList = $data->pluck('id_satker')->map(function ($id) use ($renstraList) {
            return $renstraList[$id] ?? null;
        });

        $renjaList = DB::table('sinori_sakip_renja')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_periode', $tahun)
            ->pluck('id_filename', 'id_satker');

        $sortedRenjaList = $data->pluck('id_satker')->map(function ($id) use ($renjaList) {
            return $renjaList[$id] ?? null;
        });

        $ikuList = DB::table('sinori_sakip_iku')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_periode', $tahun)
            ->pluck('id_filename', 'id_satker');

        $sortedIkuList = $data->pluck('id_satker')->map(function ($id) use ($ikuList) {
            return $ikuList[$id] ?? null;
        });

        $rkaklList = DB::table('sinori_sakip_rkakl')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_periode', $tahun)
            ->pluck('id_filename', 'id_satker');

        $sortedRkaklList = $data->pluck('id_satker')->map(function ($id) use ($rkaklList) {
            return $rkaklList[$id] ?? null;
        });

        $dipaList = DB::table('sinori_sakip_dipa')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_periode', $tahun)
            ->pluck('id_filename', 'id_satker');

        $sortedDipaList = $data->pluck('id_satker')->map(function ($id) use ($dipaList) {
            return $dipaList[$id] ?? null;
        });

        $renaksiList = DB::table('sinori_sakip_renaksi')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_periode', $tahun)
            ->pluck('id_filename', 'id_satker');

        $sortedRenaksiList = $data->pluck('id_satker')->map(function ($id) use ($renaksiList) {
            return $renaksiList[$id] ?? null;
        });

        $pklist = DB::table('pk')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_periode', $tahun)
            ->pluck('id_filename', 'id_satker');

        $sortedPkList = $data->pluck('id_satker')->map(function ($id) use ($pklist) {
            return $pklist[$id] ?? null;
        });

        // Ambil LKJIP per satker + per triwulan (terakhir)
       
$satkerIds = $data->pluck('id_satker');

// TW 1
$lkjipTW1 = DB::table('sinori_sakip_lakip')
    ->whereIn('id_satker', $satkerIds)
    ->where('id_periode', $tahun)
    ->where('id_triwulan', 'TW 1')
    ->orderByDesc('id_perubahan') // ambil perubahan terakhir
    ->get()
    ->unique('id_satker') // pastikan tiap satker hanya 1 record
    ->pluck('id_filename', 'id_satker');

$sortedLkjipTW1 = $satkerIds->mapWithKeys(function($id) use ($lkjipTW1) {
    return [$id => $lkjipTW1[$id] ?? null];
});

// TW 2
$lkjipTW2 = DB::table('sinori_sakip_lakip')
    ->whereIn('id_satker', $satkerIds)
    ->where('id_periode', $tahun)
    ->where('id_triwulan', 'TW 2')
    ->orderByDesc('id_perubahan')
    ->get()
    ->unique('id_satker')
    ->pluck('id_filename', 'id_satker');

$sortedLkjipTW2 = $satkerIds->mapWithKeys(function($id) use ($lkjipTW2) {
    return [$id => $lkjipTW2[$id] ?? null];
});

// TW 3
$lkjipTW3 = DB::table('sinori_sakip_lakip')
    ->whereIn('id_satker', $satkerIds)
    ->where('id_periode', $tahun)
    ->where('id_triwulan', 'TW 3')
    ->orderByDesc('id_perubahan')
    ->get()
    ->unique('id_satker')
    ->pluck('id_filename', 'id_satker');

$sortedLkjipTW3 = $satkerIds->mapWithKeys(function($id) use ($lkjipTW3) {
    return [$id => $lkjipTW3[$id] ?? null];
});

// TW 4
$lkjipTW4 = DB::table('sinori_sakip_lakip')
    ->whereIn('id_satker', $satkerIds)
    ->where('id_periode', $tahun)
    ->where('id_triwulan', 'TW 4')
    ->orderByDesc('id_perubahan')
    ->get()
    ->unique('id_satker')
    ->pluck('id_filename', 'id_satker');

$sortedLkjipTW4 = $satkerIds->mapWithKeys(function($id) use ($lkjipTW4) {
    return [$id => $lkjipTW4[$id] ?? null];
});

        $rastaffList = DB::table('sinori_sakip_rastaff')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_periode', $tahun)
            ->pluck('id_filename', 'id_satker');

        $sortedRastaffList = $data->pluck('id_satker')->map(function ($id) use ($rastaffList) {
            return $rastaffList[$id] ?? null;
        });

        // LHE AKIP, TL LHE AKIP, Monev Renaksi
        $lhe_akip = DB::table('lhe')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_periode', $tahun)
            ->pluck('id_filename', 'id_satker');

        $sortedLheList = $data->pluck('id_satker')->map(function ($id) use ($lhe_akip) {
            return $lhe_akip[$id] ?? null;
        });

        $tl_lhe_akip = DB::table('tl_lhe_akip')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_periode', $tahun)
            ->pluck('id_filename', 'id_satker');

        $sortedTlLheAkipList = $data->pluck('id_satker')->map(function ($id) use ($tl_lhe_akip) {
            return $tl_lhe_akip[$id] ?? null;
        });

        $monev_renaksi = DB::table('sinori_sakip_renaksieval')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_periode', $tahun)
            ->pluck('id_filename', 'id_satker');

        $sortedMonevRenaksiList = $data->pluck('id_satker')->map(function ($id) use ($monev_renaksi) {
            return $monev_renaksi[$id] ?? null;
        });

        // Mengambil id_filename berdasarkan id_satker
        $renstra = DB::table('sinori_sakip_renstra')
            ->select('id_satker', 'id_perubahan', 'id_filename', 'id_periode') // Pilih kolom yang dibutuhkan
            ->whereIn('id_satker', $data->pluck('id_satker')) // Ambil berdasarkan id_satker dari data sebelumnya
            ->where('id_periode', $id_periode)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') // Urutkan berdasarkan id_perubahan terakhir
            ->get()
            ->groupBy('id_satker'); // Kelompokkan berdasarkan id_satker

        $iku = DB::table('sinori_sakip_iku')
            ->select('id_satker', 'id_perubahan', 'id_filename') // Pilih kolom yang dibutuhkan
            ->whereIn('id_satker', $data->pluck('id_satker')) // Ambil berdasarkan id_satker dari data sebelumnya
            ->where('id_periode', $tahun) // Ambil data berdasarkan periode
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') // Urutkan berdasarkan id_perubahan secara menurun
            ->get()
            ->groupBy('id_satker'); // Kelompokkan berdasarkan id_satker

        // dd($iku);
        $renja = DB::table('sinori_sakip_renja')
            ->select('id_satker', 'id_perubahan', 'id_filename') // Pilih kolom yang dibutuhkan
            ->whereIn('id_satker', $data->pluck('id_satker')) // Ambil berdasarkan id_satker dari data sebelumnya
            ->where('id_periode', $tahun) // Ambil data berdasarkan periode
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') // Urutkan berdasarkan id_perubahan secara menurun
            ->get()
            ->groupBy('id_satker'); // Kelompokkan berdasarkan id_satker

        $renaksi = DB::table('sinori_sakip_renaksi')
            ->select('id_satker', 'id_perubahan', 'id_filename') // Pilih kolom yang dibutuhkan
            ->whereIn('id_satker', $data->pluck('id_satker')) // Ambil berdasarkan id_satker dari data sebelumnya
            ->where('id_periode', $tahun) // Ambil data berdasarkan periode
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') // Urutkan berdasarkan id_perubahan secara menurun
            ->get()
            ->groupBy('id_satker'); // Kelompokkan berdasarkan id_satker

        $dipa = DB::table('sinori_sakip_dipa')
            ->select('id_satker', 'id_perubahan', 'id_filename') // Pilih kolom yang dibutuhkan
            ->whereIn('id_satker', $data->pluck('id_satker')) // Ambil berdasarkan id_satker dari data sebelumnya
            ->where('id_periode', $tahun) // Ambil data berdasarkan periode
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') // Urutkan berdasarkan id_perubahan secara menurun
            ->get()
            ->groupBy('id_satker');

        $rkakl = DB::table('sinori_sakip_rkakl')
            ->select('id_satker', 'id_perubahan', 'id_filename') // Pilih kolom yang dibutuhkan
            ->whereIn('id_satker', $data->pluck('id_satker')) // Ambil berdasarkan id_satker dari data sebelumnya
            ->where('id_periode', $tahun) // Ambil data berdasarkan periode
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') // Urutkan berdasarkan id_perubahan secara menurun
            ->get()
            ->groupBy('id_satker'); // Kelompokkan berdasarkan id_satker

        $pk = DB::table('pk')
            ->select('id_satker', 'id_perubahan', 'id_filename') // Pilih kolom yang dibutuhkan
            ->whereIn('id_satker', $data->pluck('id_satker')) // Ambil berdasarkan id_satker dari data sebelumnya
            ->where('id_periode', $tahun) // Ambil data berdasarkan periode
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') // Urutkan berdasarkan id_perubahan secara menurun
            ->get()
            ->groupBy('id_satker');

        // Ambil data lkjip dengan perubahan terakhir per satker + id_triwulan
    //     $lkjip = DB::table('sinori_sakip_lakip as l')
    //         ->join(DB::raw("
    //     (
    //         SELECT id_satker, id_triwulan, MAX(id_perubahan) as max_perubahan
    //         FROM sinori_sakip_lakip
    //         WHERE id_periode = {$tahun}
    //         GROUP BY id_satker, id_triwulan
    //     ) m
    // "), function ($join) {
    //             $join->on('l.id_satker', '=', 'm.id_satker')
    //                 ->on('l.id_triwulan', '=', 'm.id_triwulan')
    //                 ->on('l.id_perubahan', '=', 'm.max_perubahan');
    //         })
    //         ->whereIn('l.id_satker', $data->pluck('id_satker'))
    //         ->select('l.id_satker', 'l.id_triwulan', 'l.id_perubahan', 'l.id_filename')
    //         ->orderBy('l.id_satker')
    //         ->orderBy('l.id_triwulan')
    //         ->get();

    //     $lkjipPerTw = [];
    //     foreach ($lkjip as $item) {
    //         $lkjipPerTw[$item->id_triwulan][$item->id_satker] = $item;
    //     }


        $rastaff = DB::table('sinori_sakip_rastaff')
            ->select('id_satker', 'id_perubahan', 'id_filename', 'id_triwulan') // Pilih kolom yang dibutuhkan
            ->whereIn('id_satker', $data->pluck('id_satker')) // Ambil berdasarkan id_satker dari data sebelumnya
            ->where('id_periode', $tahun) // Ambil data berdasarkan periode
            ->orderBy(DB::raw('CAST(REPLACE(id_triwulan, "TW ", "") AS UNSIGNED)'), 'desc')
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') // Urutkan berdasarkan id_perubahan secara menurun
            ->get()
            ->groupBy('id_satker'); // Kelompokkan berdasarkan id_satker

        $lhe = DB::table('lhe')
            ->select('id_satker', 'id_perubahan', 'id_filename') // Pilih kolom yang dibutuhkan
            ->whereIn('id_satker', $data->pluck('id_satker')) // Ambil berdasarkan id_satker dari data sebelumnya
            ->where('id_periode', $tahun) // Ambil data berdasarkan periode
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') // Urutkan berdasarkan id_perubahan secara menurun
            ->get()
            ->groupBy('id_satker'); // Kelompokkan berdasarkan id_satker

        $tl_lhe_akip = DB::table('tl_lhe_akip')
            ->select('id_satker', 'id_perubahan', 'id_filename') // Pilih kolom yang dibutuhkan
            ->whereIn('id_satker', $data->pluck('id_satker')) // Ambil berdasarkan id_satker dari data sebelumnya
            ->where('id_periode', $tahun) // Ambil data berdasarkan periode
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') // Urutkan berdasarkan id_perubahan secara menurun
            ->get()
            ->groupBy('id_satker'); // Kelompokkan berdasarkan id_satker

        $monev_renaksi = DB::table('sinori_sakip_renaksieval')
            ->select('id_satker', 'id_perubahan', 'id_filename') // Pilih kolom yang dibutuhkan
            ->whereIn('id_satker', $data->pluck('id_satker')) // Ambil berdasarkan id_satker dari data sebelumnya
            ->where('id_periode', $tahun) // Ambil data berdasarkan periode
            ->orderBy(DB::raw('CAST(REPLACE(id_triwulan, "TW ", "") AS UNSIGNED)'), 'desc')
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') // Urutkan berdasarkan id_perubahan secara menurun
            ->get()
            ->groupBy('id_satker'); // Kelompokkan berdasarkan id_satker

        // Kembalikan view dengan data yang diperlukan
        return Inertia::render('Sakipwil', [
            'data' => $data,
            'tahun' => $tahun,
            'satkernamaList' => $satkernamaList,
            'sortedKepList' => $sortedKepList,
            'sortedRenstraList' => $sortedRenstraList,
            'sortedRenjaList' => $sortedRenjaList,
            'sortedIkuList' => $sortedIkuList,
            'sortedRkaklList' => $sortedRkaklList,
            'sortedDipaList' => $sortedDipaList,
            'sortedRenaksiList' => $sortedRenaksiList,
            'sortedPkList' => $sortedPkList,
            'sortedLkjipTW1' => $sortedLkjipTW1,
            'sortedLkjipTW2' => $sortedLkjipTW2,
            'sortedLkjipTW3' => $sortedLkjipTW3,
            'sortedLkjipTW4' => $sortedLkjipTW4,
            'sortedRastaffList' => $sortedRastaffList,
            'sortedLheList' => $sortedLheList,
            'sortedTlLheAkipList' => $sortedTlLheAkipList,
            'sortedMonevRenaksiList' => $sortedMonevRenaksiList,
            'renstra' => $renstra,
            'iku' => $iku,
            'renja' => $renja,
            'renaksi' => $renaksi,
            'bidangs' => $bidangs,
            'dipa' => $dipa,
            'rkakl' => $rkakl,
            'pk' => $pk,
            'rastaff' => $rastaff,
            'lhe' => $lhe,
            'tl_lhe_akip' => $tl_lhe_akip,
            'monev_renaksi' => $monev_renaksi,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('query');

        $results = \DB::table('id_satker') // sesuaikan nama tabel sumber data
            ->where('nama_satker', 'like', "%{$query}%")
            ->orWhere('id_satker', 'like', "%{$query}%")
            ->get();

        return response()->json($results);
    }
}
