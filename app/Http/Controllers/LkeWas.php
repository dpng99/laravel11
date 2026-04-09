<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage; // Wajib: Google Drive
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Redirect;
use App\Models\{
    absen_pm, ba_pleno, ba_praeval, DataLke1, Dipa, Iku, lhe_2023, LheAkip, 
    lke_subkomponens, lke_buktidukung, lke_komponen, Lkjip, memo_datakinerja, 
    memo_lkjip, MonevRenaksi, nodis_eval_sakip, nodis_p_sakip, notulensi_pm, 
    Pk, PokinRanwal, Renaksi, Renja, Renstra, reward_punish, Rkakl, sampel_rekom, 
    sample_skp, sk_pk, sk_pm, tar_pm, TlLheAkip, tar_lkjip, ss_perencanaan, 
    ss_laporanweb, ss_laporanapp, nodis_datakinerja, RapatStaffEka
};

class LkeWas extends Controller
{
     private function getMapping()
    {
        return [
            1 => Renstra::class, 2 => Renja::class, 3 => Renaksi::class, 4 => Rkakl::class,
            5 => Dipa::class, 6 => Pk::class, 7 => Pk::class, 8 => Iku::class, 9 => Iku::class,
            10 => Lkjip::class, 11 => Lkjip::class, 12 => LheAkip::class,
            13 => RapatStaffEka::class, 14 => RapatStaffEka::class,
            15 => LheAkip::class, 16 => LheAkip::class, 17 => TlLheAkip::class,
            18 => MonevRenaksi::class, 19 => MonevRenaksi::class, 20 => PokinRanwal::class,
            21 => Renstra::class, 22 => Lkjip::class, 23 => sample_skp::class,
            24 => sk_pm::class, 25 => sk_pk::class, 26 => absen_pm::class,
            27 => notulensi_pm::class, 28 => nodis_p_sakip::class, 29 => nodis_eval_sakip::class,
            30 => memo_datakinerja::class, 31 => nodis_datakinerja::class, 32 => reward_punish::class,
            33 => sampel_rekom::class, 34 => ss_perencanaan::class, 35 => ss_laporanweb::class,
            36 => ss_laporanapp::class, 37 => tar_lkjip::class, 38 => tar_lkjip::class,
            39 => memo_lkjip::class, 40 => memo_lkjip::class, 41 => tar_pm::class,
            42 => ba_praeval::class, 43 => ba_pleno::class, 44 => LheAkip::class, 
        ];
    }

    private function getTriwulanMapping()
    {
        return [
            10 => 'TW 1', 11 => 'TW 2', 12 => 'TW 4', 13 => 'TW 1', 14 => 'TW 2',
            18 => 'TW 1', 19 => 'TW 2', 37 => 'TW 1', 38 => 'TW 2', 39 => 'TW 1', 40 => 'TW 2',
        ];
    }
    public function index()
    {  if(!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }
        $tahun = session('tahun_terpilih');
        // $id_satker = preg_replace('/^was/i', '', session('id_satker'));
        $id_satker = session('id_satker');
        
        $id_kejati = DB::table('sinori_login')->where('id_satker', $id_satker)->first();
        $level = session('id_sakip_level');

        if (!$id_kejati) {
            return back()->with('error', 'Data Kejati tidak ditemukan');
        }

        $list_kejari = DB::table('sinori_login')
            ->where('id_kejati', $id_kejati->id_kejati)
            ->where('id_hidesatker', 0)
            ->orderBy('satkernama')
            ->get();
            
        if (in_array($id_satker, [999999, 'admin', 'Pengawasan', 'Panev', 'menpanrb'])) {
            // Ambil semua satker, urutkan berdasarkan id_kejati
            $list_kejari = DB::table('sinori_login')
                ->whereNotIn('id_satker', [888881, 888882, 'admin', 999999, 'Pengawasan', 'Panev', 'menpanrb']) // dikecualikan
                ->where('id_satker', 'not like', 'was%')
                ->where('id_satker', 'not like', '00budi')
                ->where('id_kejati', 'not like', '87') // dikecualikan
                ->orderBy('id_kejati', 'asc')
                ->orderBy('id_kejari', 'asc')
                ->get();
        } else {
            // Ambil data satkernama dan id_satker sesuai id_kejati
            $list_kejari = DB::table('sinori_login')
                ->where('id_kejati', $id_kejati->id_kejati)
                ->where('id_satker', 'not like', 'was%') // dikecualikan
                // ->orderBy('id_satker', 'asc')
                ->get();
        }
        return Inertia::render('Lkewas', compact('tahun', 'list_kejari'));
    }

    public function listBuktiDukung(Request $request)
    {
        if (!session()->has('tahun_terpilih')) return redirect()->route('pilih.tahun');

        $tahun = session('tahun_terpilih');
        $idSatker = $request->id_satker;

        // Ambil nama satker
        $nama_satker = DB::table('sinori_login')->where('id_satker', $idSatker)->value('satkernama');
        $satkernama = str_replace('_', ' ', $nama_satker);

        // ==========================================
        // 🚀 ALGORITMA MEMBACA FILE DARI SISTEM (CLONING EVALUASI CONTROLLER)
        // ==========================================
        // Pastikan Anda sudah mengimport model \App\Models\lke_komponen di atas
        $lkeHierarki = \App\Models\lke_komponen::with(['subKomponens.kriterias.buktiDukungs'])
                        ->orderBy('id', 'asc')->get();

        $modelMapping = $this->getMapping();
        $triwulanMapping = $this->getTriwulanMapping();

        // 1. Tarik Data Dictionary
        $allBuktiDukung = DB::table('bukti_dukung')->where('id_satker', $idSatker)->get();
        $sysAvailabilityCache = [];
        $lkeDataFlat = collect([]);

        // 2. Looping Pengecekan Akurat
        foreach ($lkeHierarki as $komponen) {
            foreach ($komponen->subKomponens as $sub) {
                foreach ($sub->kriterias as $kriteria) {
                    $buktiList = [];
                    foreach ($kriteria->buktiDukungs as $buktiRef) {
                        $kode = $buktiRef->id;
                        $status = 'Tidak Ada';
                        $fileLink = null;

                        $buktiLke = $allBuktiDukung->where('kode_bukti', $kode)->first();
                        
                        if (!$buktiLke) {
                            $buktiLke = $allBuktiDukung->where('id_kriteria', $kriteria->kode)
                                                       ->whereIn('kode_bukti', [null, 0, ''])
                                                       ->first();
                        }

                        if ($buktiLke && !empty($buktiLke->link_bukti_dukung)) {
                            $status = 'Ada';
                            $fileLink = url("/file/view/{$idSatker}/" . urlencode($buktiLke->link_bukti_dukung));
                        } else {
                            if (isset($modelMapping[$kode])) {
                                if (!array_key_exists($kode, $sysAvailabilityCache)) {
                                    $sysAvailabilityCache[$kode] = $this->checkSystemAvailability($kode, $idSatker, $tahun, $modelMapping, $triwulanMapping);
                                }
                                
                                if ($sysAvailabilityCache[$kode]) {
                                    $status = 'Tersedia di Sistem (Belum Verif)';
                                }
                            }
                        }

                        $buktiList[] = [
                            'kode_bukti' => $kode,
                            'nama_dokumen' => $buktiRef->dokumen,
                            'status' => $status,
                            'file_link' => $fileLink,
                        ];
                    }

                    $item = new \stdClass();
                    $item->id_komponen = $komponen->id;
                    $item->nama_komponen = $komponen->nama; 
                    $item->id_sub_komponen = $sub->kode; 
                    $item->nama_subkomponen = $sub->nama; 
                    $item->id_kriteria = $kriteria->id;
                    $item->kode_kriteria = $kriteria->kode;
                    $item->nama_kriteria = $kriteria->nama;
                    $item->bukti_list = $buktiList;

                    $lkeDataFlat->push($item);
                }
            }
        }

        // 3. Grouping agar Frontend mudah membaca
        $lkeGrouped = $lkeDataFlat->groupBy('id_komponen')->map(fn($subItems) => $subItems->groupBy('id_sub_komponen'));

        // 4. Ambil File Ekstra
        $lheAkipFiles = \App\Models\LheAkip::select('id', 'id_filename', 'id_periode', 'id_perubahan', 'id_tglupload')
            ->where('id_satker', $idSatker)->where('id_periode', $tahun)->orderByDesc('id')->get();
            
        $tlLheAkipFiles = \App\Models\TlLheAkip::select('id', 'id_filename', 'id_periode', 'id_perubahan', 'id_tglupload')
            ->where('id_satker', $idSatker)->where('id_periode', $tahun)->orderByDesc('id')->get();
            
        $monevRenaksiFiles = \App\Models\MonevRenaksi::select('id', 'id_filename', 'id_periode', 'id_perubahan', 'id_triwulan', 'id_tglupload')
            ->where('id_satker', $idSatker)->where('id_periode', $tahun)->orderByDesc('id_perubahan')->get();

        return Inertia::render('EvalWas/EvalWas', [
            'tahun' => $tahun,
            'idSatker' => $idSatker,
            'satkernama' => $satkernama,
            'lkeGrouped' => $lkeGrouped, // <--- Data sakti kita kirim ke Frontend
            'lheAkipFiles' => $lheAkipFiles,
            'tlLheAkipFiles' => $tlLheAkipFiles,
            'monevRenaksiFiles' => $monevRenaksiFiles
        ]);
    }
    private function checkSystemAvailability($kode, $idSatker, $tahun, $modelMapping, $triwulanMapping)
    {
        $modelClass = $modelMapping[$kode];
        $query = $modelClass::where('id_satker', $idSatker);

        if (in_array($kode, [7, 12, 15, 17])) $query->where('id_periode', '2024'); 
        else $query->where('id_periode', $tahun);

        if (isset($triwulanMapping[$kode])) {
            $valTw = $triwulanMapping[$kode];           
            $valAngka = str_replace('TW ', '', $valTw); 
            $query->where(function($q) use ($valTw, $valAngka) {
                $tableName = $q->getModel()->getTable();
                if (Schema::hasColumn($tableName, 'id_triwulan')) $q->where('id_triwulan', $valTw);
                elseif (Schema::hasColumn($tableName, 'triwulan')) $q->where('triwulan', $valAngka);
            });
        }

        $tableName = $query->getModel()->getTable();
        if (in_array($kode, [1, 6, 8, 18])) { 
            if (Schema::hasColumn($tableName, 'id_perubahan')) $query->where('id_perubahan', 0);
        } elseif (!in_array($kode, [21, 7, 9, 19]) && Schema::hasColumn($tableName, 'id_perubahan')) {
            $query->orderBy('id_perubahan', 'desc');
        }

        // Return first data only
        return Schema::hasColumn($tableName, 'id_perubahan') ? $query->first() : $query->latest()->first();
    }
}
