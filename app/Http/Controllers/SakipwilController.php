<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Bidang;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
class SakipwilController extends Controller
{
   public function index(Request $request)
    {
        // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }

        $id_satker = session('id_satker');
        $tahun = session('tahun_terpilih');
        $level = session('id_sakip_level');
        $perPage = $this->perPage($request);
        $search = trim((string) $request->input('search'));

        $bidangs = Bidang::where('bidang_lokasi', $level)
            ->where('bidang_level', '!=', null)
            ->orderBy('bidang_level', 'asc')
            ->get();

        $id = DB::table('sinori_login')->where('id_satker', $id_satker)->first();

        // 1. Ambil Data User/Satker
        $baseSatkerQuery = DB::table('sinori_login')->where('id_satker', 'not like', 'was%');
        if (in_array($id_satker, [999999, 'admin', 'Pengawasan', 'Panev', 'menpanrb'])) {
            $baseSatkerQuery->whereNotIn('id_satker', [888881, 888882, 'admin', 999999, 'Pengawasan', 'Panev', 'menpanrb'])
                  ->where('id_satker', 'not like', '00budi')
                  ->where('id_kejati', 'not like', '87');
        } else {
            $baseSatkerQuery->where('id_kejati', $id->id_kejati);
        }

        if ($search !== '') {
            $baseSatkerQuery->where(function ($query) use ($search) {
                $query->where('satkernama', 'like', "%{$search}%")
                    ->orWhere('id_satker', 'like', "%{$search}%");
            });
        }

        $orderedSatkerQuery = fn () => (clone $baseSatkerQuery)
            ->orderBy('id_kejati', 'asc')
            ->orderBy('id_kejari', 'asc');

        $allSatkerIds = $orderedSatkerQuery()->pluck('id_satker')->toArray();
        $data = $orderedSatkerQuery()
            ->paginate($perPage)
            ->withQueryString();

        $satkerIds = collect($data->items())->pluck('id_satker')->toArray();
        $satkernamaList = collect($data->items())->pluck('satkernama')->map(fn($name) => str_replace('_', ' ', $name));

        $id_periode = ($tahun == "2024") ? "P1" : "P2";

        // ========================================================================
        // 🔥 HELPER CANGGIH: Mengambil data + Grouping + Pluck hanya dengan 1 Query
        // ========================================================================
        $fetchDocs = function ($table, $periodCol, $periodVal, $orderBy = 'id_perubahan', $replaceTw = false) use ($satkerIds, $allSatkerIds) {
            $q = DB::table($table)
                ->whereIn('id_satker', $allSatkerIds)
                ->where($periodCol, $periodVal);
                
            if ($replaceTw) {
                $q->orderBy(DB::raw('CAST(REPLACE(id_triwulan, "TW ", "") AS UNSIGNED)'), 'desc');
            }
            
            // Ambil data dari DB
            $records = $q->orderBy(DB::raw("CAST($orderBy AS UNSIGNED)"), 'desc')->get();
            
            // Grouping untuk object full (Props detail)
            $recordsBySatker = $records->groupBy('id_satker');
            $grouped = $records->whereIn('id_satker', $satkerIds)->groupBy('id_satker');
            
            // Mapping untuk list file cepat (Sorted List Props)
            $sortedList = collect($allSatkerIds)->mapWithKeys(function ($satkerId) use ($recordsBySatker) {
                return [$satkerId => isset($recordsBySatker[$satkerId]) ? $recordsBySatker[$satkerId]->first()->id_filename : null];
            });

            return [$grouped, $sortedList];
        };

        // 2. Eksekusi Pengambilan Data Dokumen menggunakan Helper
        // Keputusan (Unik tanpa id_perubahan)
        $kepRecords = DB::table('sinori_sakip_keputusan')
            ->whereIn('id_satker', $allSatkerIds)
            ->where('id_tahun', $tahun)
            ->get()->groupBy('id_satker');
        $sortedKepList = collect($allSatkerIds)->map(fn($id) => isset($kepRecords[$id]) ? $kepRecords[$id]->first()->id_filesurat : null);

        // Dokumen Standar (1 Baris = 1 Tabel Query, Otomatis dapat list_grouped & list_sorted)
        [$renstra, $sortedRenstraList] = $fetchDocs('sinori_sakip_renstra', 'id_periode', $id_periode);
        [$renja, $sortedRenjaList]     = $fetchDocs('sinori_sakip_renja', 'id_periode', $tahun);
        [$iku, $sortedIkuList]         = $fetchDocs('sinori_sakip_iku', 'id_periode', $tahun);
        [$rkakl, $sortedRkaklList]     = $fetchDocs('sinori_sakip_rkakl', 'id_periode', $tahun);
        [$dipa, $sortedDipaList]       = $fetchDocs('sinori_sakip_dipa', 'id_periode', $tahun);
        [$renaksi, $sortedRenaksiList] = $fetchDocs('sinori_sakip_renaksi', 'id_periode', $tahun);
        [$pk, $sortedPkList]           = $fetchDocs('pk', 'id_periode', $tahun);
        [$lhe, $sortedLheList]         = $fetchDocs('lhe', 'id_periode', $tahun);
        [$tl_lhe_akip, $sortedTlLheAkipList] = $fetchDocs('tl_lhe_akip', 'id_periode', $tahun);
        
        // Dokumen dengan Triwulan
        [$rastaff, $sortedRastaffList]     = $fetchDocs('sinori_sakip_rastaff', 'id_periode', $tahun, 'id_perubahan', true);
        [$monev_renaksi, $sortedMonevRenaksiList] = $fetchDocs('sinori_sakip_renaksieval', 'id_periode', $tahun, 'id_perubahan', true);

        // ========================================================================
        // 🔥 OPTIMASI LKJiP: Dari 4 Query menjadi HANYA 1 Query
        // ========================================================================
        $lkjipRecords = DB::table('sinori_sakip_lakip')
            ->whereIn('id_satker', $allSatkerIds)
            ->where('id_periode', $tahun)
            ->orderByRaw('CAST(id_perubahan AS UNSIGNED) DESC')
            ->get()
            ->groupBy('id_triwulan'); // Kelompokkan berdasarkan TW dulu

        $getLkjipTw = function($tw) use ($lkjipRecords, $allSatkerIds) {
            $twData = $lkjipRecords->get($tw, collect())->groupBy('id_satker'); // Baru kelompokkan satker
            return collect($allSatkerIds)->mapWithKeys(function($id) use ($twData) {
                return [$id => isset($twData[$id]) ? $twData[$id]->first()->id_filename : null];
            });
        };

        $sortedLkjipTW1 = $getLkjipTw('TW 1');
        $sortedLkjipTW2 = $getLkjipTw('TW 2');
        $sortedLkjipTW3 = $getLkjipTw('TW 3');
        $sortedLkjipTW4 = $getLkjipTw('TW 4');

        return Inertia::render('Sakipwil', [
            'data' => $data,
            'tahun' => $tahun,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
            'perPageOptions' => [10, 50],
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
        $results = DB::table('sinori_login') 
            ->where('satkernama', 'like', "%{$query}%")
            ->orWhere('id_satker', 'like', "%{$query}%")
            ->get();

        return response()->json($results);
    }

    /**
     * 🔽 FUNGSI VIEW FILE GOOGLE DRIVE 🔽
     */
    public function viewFile($satker, $filename)
    {
        $path = 'uploads/repository/' . $satker . '/' . $filename;
        $disk = Storage::disk('google');

        if ($disk->exists($path)) {
            $fileContent = $disk->get($path);
            $mimeType = $disk->mimeType($path);

            return response($fileContent, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        }

        abort(404, 'File tidak ditemukan di Google Drive.');
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, [10, 50], true) ? $perPage : 10;
    }
}
