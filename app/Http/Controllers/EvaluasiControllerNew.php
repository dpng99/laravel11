<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // as Google Drive
use Illuminate\Support\Facades\Schema;

// Pastikan semua Model di-import seperti aslinya
use App\Models\{
    absen_pm, ba_pleno, ba_praeval, DataLke1, Dipa, Iku, lhe_2023, LheAkip, 
    lke_subkomponens, lke_buktidukung, lke_komponen, Lkjip, memo_datakinerja, 
    memo_lkjip, MonevRenaksi, nodis_eval_sakip, nodis_p_sakip, notulensi_pm, 
    Pk, PokinRanwal, Renaksi, Renja, Renstra, reward_punish, Rkakl, sampel_rekom, 
    sample_skp, sk_pk, sk_pm, tar_pm, TlLheAkip, tar_lkjip, ss_perencanaan, 
    ss_laporanweb, ss_laporanapp, nodis_datakinerja, RapatStaffEka
};

class EvaluasiControllerNew extends Controller
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
    // ==========================================
    // 📝 KAMUS PREFIX NAMA FILE (Full Sesuai Pedoman)
    // ==========================================
    private function getFilePrefixMapping()
    {
        return [
            1  => 'renstra',                     // [cite: 1]
            2  => 'renja',                       // [cite: 1]
            3  => 'renaksi',                     // [cite: 1]
            4  => 'rkakl',                       // [cite: 1]
            5  => 'dipa',                        // [cite: 1]
            6  => 'pk',                          // [cite: 1]
            7  => 'pk',                          // [cite: 1]
            8  => 'IKU',                         // [cite: 1]
            9  => 'IKU',                         // [cite: 1]
            10 => 'lkjip',                       // [cite: 1]
            11 => 'lkjip',                       // [cite: 1]
            12 => 'lkjip',                       // [cite: 1]
            13 => 'rastaff',                     // [cite: 2]
            14 => 'rastaff',                     // [cite: 2]
            15 => 'lhe',                         // [cite: 2]
            16 => 'lhe',                         // [cite: 2]
            17 => 'tl_lhe_akip',                 // [cite: 2]
            18 => 'monev',                       // [cite: 2]
            19 => 'monev',                       // [cite: 2]
            20 => 'pokin_ranwal',                // [cite: 2]
            21 => 'renstra_lembaga',             // [cite: 2]
            22 => 'lkjip',                       // [cite: 2]
            23 => 'sampel_skp',                  // [cite: 2]
            24 => 'tim_pm',                      // [cite: 3]
            25 => 'tim_evaluator',               // [cite: 3]
            26 => 'absen_pm',                    // [cite: 3]
            27 => 'notulensi_bimtek',            // [cite: 3]
            28 => 'nodis_penyelenggaraan_akip',  // [cite: 3]
            29 => 'nodis_evaluasi_akip',         // [cite: 3]
            30 => 'memo_data_kinerja',           // [cite: 3] - Mengganti spasi menjadi _
            31 => 'nodis_data_kinerja',          // [cite: 4] - Mengganti spasi menjadi _
            32 => 'reward_punishment',           // [cite: 4]
            33 => 'sampel_rekom',                // [cite: 4]
            34 => 'ss_perencanaan',              // [cite: 4]
            35 => 'ss_laporan_web',              // [cite: 4]
            36 => 'ss_laporan_app',              // [cite: 4]
            37 => 'tar_lkjip',                   // [cite: 4]
            38 => 'tar_lkjip',                   // [cite: 4]
            39 => 'memo_lkjip',                  // [cite: 4]
            40 => 'memo_lkjip',                  // [cite: 5]
            41 => 'tar_pm',                      // [cite: 5]
            42 => 'ba_praevaluasi',              // [cite: 5]
            43 => 'ba_pleno',                    // [cite: 5]
            44 => 'lhe',                         // [cite: 5]
        ];
    }

    // ==========================================
    // 🚀 INDEX SUPER OPTIMIZED
    // ==========================================
    public function index()
    {
        if (!session()->has('tahun_terpilih')) return redirect()->route('pilih.tahun');

        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');

        $lkeHierarki = lke_komponen::with(['subKomponens.kriterias.buktiDukungs'])
                        ->orderBy('id', 'asc')->get();

        $modelMapping = $this->getMapping();
        $triwulanMapping = $this->getTriwulanMapping();
        
        // 🔥 OPTIMASI 1: Tarik SEMUA bukti_dukung sekaligus (Cuma 1 Query!)
        // Daripada query ratusan kali di dalam loop, kita panggil 1x dan jadikan Dictionary
        $allBuktiDukung = DB::table('bukti_dukung')
                            ->where('id_satker', $idSatker)
                            ->get();

        // 🔥 OPTIMASI 2: Cache ketersediaan sistem dalam memori sementara
        // Agar model yang sama (misal Renstra) tidak di-query berulang kali
        $sysAvailabilityCache = [];
        $lkeDataFlat = collect([]);

        foreach ($lkeHierarki as $komponen) {
            foreach ($komponen->subKomponens as $sub) {
                foreach ($sub->kriterias as $kriteria) {
                    $buktiList = [];
                    foreach ($kriteria->buktiDukungs as $buktiRef) {
                        $kode = $buktiRef->id;
                        $status = 'Tidak Ada';
                        $fileLink = null;

                        // Ambil dari Dictionary Memori (Jauh lebih cepat dari DB query)
                        // 1. Coba cari berdasarkan kode_bukti spesifik (Untuk Data Baru yang Akurat)
                        $buktiLke = $allBuktiDukung->where('kode_bukti', $kode)->first();
                        
                        // 2. JARING PENGAMAN: Jika tidak ketemu, cari data lama yang 'kode_bukti'-nya masih kosong
                        // Ini memastikan bukti dukung yang sudah di-upload di masa lalu tidak mendadak hilang
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
                                // eksekusi kalo beloman ada di sistem atau db
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

        $lkeGrouped = $lkeDataFlat->groupBy('id_komponen')->map(fn($subItems) => $subItems->groupBy('id_sub_komponen'));

        // 🔥 OPTIMASI 3: Hanya select kolom yang dipakai agar irit RAM
        $lheAkipFiles = LheAkip::select('id', 'id_filename', 'id_periode', 'id_perubahan', 'id_tglupload')
            ->where('id_satker', $idSatker)->where('id_periode', $tahun)->orderByDesc('id')->get();
            
        $tlLheAkipFiles = TlLheAkip::select('id', 'id_filename', 'id_periode', 'id_perubahan', 'id_tglupload')
            ->where('id_satker', $idSatker)->where('id_periode', $tahun)->orderByDesc('id')->get();
            
        $monevRenaksiFiles = MonevRenaksi::select('id', 'id_filename', 'id_periode', 'id_perubahan', 'id_triwulan', 'id_tglupload')
            ->where('id_satker', $idSatker)->where('id_periode', $tahun)->orderByDesc('id_perubahan')->get();

        return Inertia::render('Kelola/Evaluasi', [
            'tahun' => $tahun,
            'idSatker' => $idSatker,
            'lkeGrouped' => $lkeGrouped,
            'lheAkipFiles' => $lheAkipFiles,
            'tlLheAkipFiles' => $tlLheAkipFiles,
            'monevRenaksiFiles' => $monevRenaksiFiles
        ]);
    }

    // ==========================================
    // 💾 UPLOAD BUKTI DUKUNG (Google Drive)
    // ==========================================
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:pdf,jpg,png,doc,docx,xls,xlsx|max:10240',
            'id_kriteria' => 'required',
            'id_sub_komponen' => 'required',
            'kode_bukti' => 'required'
        ]);

        $idSatker = session('id_satker');
        $tahun = session('tahun_terpilih');
        $kode = (int)$request->kode_bukti;

        $dokRef = lke_buktidukung::find($kode);
        $cleanName = $dokRef ? preg_replace('/[^A-Za-z0-9]/', '_', $dokRef->dokumen) : 'Dokumen';
        $extension = $request->file('file')->getClientOriginalExtension();
        // Buat nama file berdasarkan pedoman
        $filename = $this->generateCustomFileName($kode, $idSatker, $tahun, $extension);
        $folderPath = "uploads/repository/{$idSatker}";

        try {
            Storage::disk('google')->putFileAs($folderPath, $request->file('file'), $filename);

            DB::table('bukti_dukung')->updateOrInsert(
                // 🔥 TAMBAHKAN 'kode_bukti' AGAR DOKUMEN DALAM 1 KRITERIA TIDAK SALING TIMPA
                ['id_satker' => $idSatker, 'id_kriteria' => $request->id_kriteria, 'kode_bukti' => $kode],
                ['id_komponen' => $request->id_komponen, 'id_sub_komponen' => $request->id_sub_komponen, 'link_bukti_dukung' => $filename, 'tgl_pengisian' => now()->format('d/m/Y H:i A')]
            );

            if (isset($this->getMapping()[$kode])) {
                $this->saveToSourceTable($kode, $filename, $idSatker, $tahun);
            }

            return back()->with('success', 'File berhasil diupload ke Google Drive.');
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal Upload: ' . $e->getMessage()]);
        }
    }

    // ==========================================
    // ✅ VERIFIKASI (TARIK DARI SISTEM)
    // ==========================================
    public function verifikasi(Request $request)
    {
        $request->validate(['id_kriteria' => 'required', 'kode_bukti' => 'required']);
        $idSatker = session('id_satker');
        $tahun = session('tahun_terpilih');
        $kode = (int)$request->kode_bukti;
        $mapping = $this->getMapping();

        if (!isset($mapping[$kode])) return $this->sendResponse($request, false, 'Model tidak ditemukan.');

        $dokumenSumber = $this->checkSystemAvailability($kode, $idSatker, $tahun, $mapping, $this->getTriwulanMapping());

        if ($dokumenSumber) {
            $filename = $dokumenSumber->id_filename ?? $dokumenSumber->file ?? $dokumenSumber->nama_file ?? $dokumenSumber->link_bukti_dukung ?? null;
            if ($filename) {
                DB::table('bukti_dukung')->updateOrInsert(
                    ['id_satker' => $idSatker, 'id_kriteria' => $request->id_kriteria, 'kode_bukti' => $kode],
                    ['id_komponen' => $request->id_komponen, 'id_sub_komponen' => $request->id_sub_komponen, 'link_bukti_dukung' => $filename, 'tgl_pengisian' => now()->format('d/m/Y H:i A')]
                );
                return $this->sendResponse($request, true, 'Verifikasi Berhasil!');
            }
        }
        return $this->sendResponse($request, false, 'File belum tersedia di sistem.');
    }

    // ==========================================
    // ⚙️ FUNGSI BANTUAN (HELPERS)
    // ==========================================
    private function saveToSourceTable($kode, $filename, $idSatker, $tahun)
    {
        $mapping = $this->getMapping();
        $triwulanMapping = $this->getTriwulanMapping();
        $modelClass = $mapping[$kode];

        $model = new $modelClass();
        $table = $model->getTable();

        $model->id_satker = $idSatker;
        $model->id_periode = $tahun;

        if (Schema::hasColumn($table, 'id_tglupload')) $model->id_tglupload = now();
        elseif (Schema::hasColumn($table, 'tgl_upload')) $model->tgl_upload = now();

        if (Schema::hasColumn($table, 'id_filename')) $model->id_filename = $filename;
        elseif (Schema::hasColumn($table, 'file')) $model->file = $filename;
        elseif (Schema::hasColumn($table, 'link_bukti_dukung')) $model->link_bukti_dukung = $filename;
        elseif (Schema::hasColumn($table, 'nama_file')) $model->nama_file = $filename;

        if (isset($triwulanMapping[$kode])) {
            $valTw = $triwulanMapping[$kode];
            if (Schema::hasColumn($table, 'id_triwulan')) $model->id_triwulan = $valTw;
            elseif (Schema::hasColumn($table, 'triwulan')) $model->triwulan = str_replace('TW ', '', $valTw);
        }

        if (Schema::hasColumn($table, 'id_perubahan')) {
            if (in_array($kode, [21, 7, 9, 19])) {
                $lastVer = $modelClass::where('id_satker', $idSatker)->where('id_periode', $tahun)->max('id_perubahan');
                $model->id_perubahan = ($lastVer > 0) ? $lastVer + 1 : 1; 
            } else {
                $model->id_perubahan = 0;
            }
        }

        if (Schema::hasColumn($table, 'id_pagu')) $model->id_pagu = 0;
        if (Schema::hasColumn($table, 'id_gakyankum')) $model->id_gakyankum = 0;
        if (Schema::hasColumn($table, 'id_dukman')) $model->id_dukman = 0;

        try { $model->save(); } catch (\Exception $e) { \Illuminate\Support\Facades\Log::error("Gagal simpan ke sumber ($kode): " . $e->getMessage()); }
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

    private function sendResponse($request, $success, $message)
    {
        return $request->wantsJson() ? response()->json(['success' => $success, 'message' => $message], $success ? 200 : 404) : Redirect::back()->with($success ? 'success' : 'error', $message);
    }

    // ==========================================
    // 📁 UPLOAD EXTRA (TL LHE & MONEV)
    // ==========================================
    public function uploadLheAkip(Request $request)
    {
        $request->validate(['lhe_akip_file' => 'required|mimes:pdf|max:10240']);
        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');

        $latest = LheAkip::select('id_perubahan')->where('id_satker', $idSatker)->where('id_periode', $tahun)->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')->first();
        $id_perubahan = $latest ? $latest->id_perubahan + 1 : 0;

        try {
            $filename = 'lhe_akip_' . $tahun . '_' . $id_perubahan . '.pdf';
            Storage::disk('google')->putFileAs("uploads/repository/{$idSatker}", $request->file('lhe_akip_file'), $filename);
            
            LheAkip::create(['id_periode' => $tahun, 'id_satker' => $idSatker, 'id_perubahan' => $id_perubahan, 'id_filename' => $filename, 'id_tglupload' => now()->format('d/m/Y h:i A')]);
            return redirect()->route('evaluasi')->with(['success-lhe' => 'Berhasil upload Google Drive', 'active_tab' => 'lhe-akip']);
        } catch (\Exception $e) { return back()->withErrors(['lhe_akip_file' => 'Gagal Upload: ' . $e->getMessage()]); }
    }
    public function uploadTlLheAkip(Request $request)
    {
        $request->validate(['tl_lhe_akip_file' => 'required|mimes:pdf|max:10240']);
        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');

        $latest = TlLheAkip::select('id_perubahan')->where('id_satker', $idSatker)->where('id_periode', $tahun)->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')->first();
        $id_perubahan = $latest ? $latest->id_perubahan + 1 : 0;

        try {
            $filename = 'tl_lhe_akip_' . $tahun . '_' . $id_perubahan . '.pdf';
            Storage::disk('google')->putFileAs("uploads/repository/{$idSatker}", $request->file('tl_lhe_akip_file'), $filename);
            
            TlLheAkip::create(['id_periode' => $tahun, 'id_satker' => $idSatker, 'id_perubahan' => $id_perubahan, 'id_filename' => $filename, 'id_tglupload' => now()->format('d/m/Y h:i A')]);
            return redirect()->route('evaluasi')->with(['success-tllhe' => 'Berhasil upload Google Drive', 'active_tab' => 'tl-lhe-akip']);
        } catch (\Exception $e) { return back()->withErrors(['tl_lhe_akip_file' => 'Gagal Upload: ' . $e->getMessage()]); }
    }

    public function uploadMonevRenaksi(Request $request)
    {
        $request->validate(['id_triwulan' => 'required|in:TW 1,TW 2,TW 3,TW 4', 'monev_file' => 'required|mimes:pdf|max:10240']);
        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');
        $id_triwulan = $request->input('id_triwulan');

        $latest = MonevRenaksi::select('id_perubahan')->where('id_satker', $idSatker)->where('id_periode', $tahun)->where('id_triwulan', $id_triwulan)->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')->first();
        $id_perubahan = $latest ? $latest->id_perubahan + 1 : 0;

        try {
            $filename = 'renaksieval_' . $tahun . '_' . $id_perubahan . '_' . str_replace(' ', '_', $id_triwulan) . '.pdf';
            Storage::disk('google')->putFileAs("uploads/repository/{$idSatker}", $request->file('monev_file'), $filename);
            
            MonevRenaksi::create(['id_periode' => $tahun, 'id_satker' => $idSatker, 'id_perubahan' => $id_perubahan, 'id_filename' => $filename, 'id_tglupload' => now()->format('d/m/Y h:i A'), 'id_triwulan' => $id_triwulan]);
            return redirect()->route('evaluasi')->with(['success-monev' => 'Berhasil upload Google Drive', 'active_tab' => 'monev-renaksi']);
        } catch (\Exception $e) { return back()->withErrors(['monev_file' => 'Gagal Upload: ' . $e->getMessage()]); }
    }
    // ==========================================
    // ⚙️ GENERATOR NAMA FILE CUSTOM (Sesuai Pedoman)
    // ==========================================
    private function generateCustomFileName($kode, $idSatker, $tahun, $extension)
    {
        // 1. Ambil Nama Depan (Prefix) dari Kamus
        $prefixes = $this->getFilePrefixMapping();
        $prefix = $prefixes[$kode] ?? 'dokumen_sakip';

        // 2. Ambil Triwulan & Ubah ke Angka Romawi (TW 1 -> TW I)
        $tw = '';
        $triwulans = $this->getTriwulanMapping();
        
        if (isset($triwulans[$kode])) {
            $romans = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV'];
            $angkaTw = trim(str_replace('TW', '', $triwulans[$kode])); // Ambil angkanya saja
            $romanTw = $romans[$angkaTw] ?? $angkaTw;
            
            $tw = '_TW ' . $romanTw; // Hasil: "_TW I" atau "_TW II"
        }

        // 3. Hitung Iterasi / Perubahan Terakhir secara otomatis
        $iterasi = 0;
        $mapping = $this->getMapping();

        if (isset($mapping[$kode])) {
            $modelClass = $mapping[$kode];
            $table = (new $modelClass)->getTable();

            // Cek apakah tabel punya kolom 'id_perubahan'
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'id_perubahan')) {
                // Cari angka iterasi tertinggi untuk satker & tahun ini
                $latest = $modelClass::where('id_satker', $idSatker)
                                     ->where('id_periode', $tahun)
                                     ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
                                     ->value('id_perubahan');
                
                $iterasi = $latest !== null ? $latest + 1 : 0;
            }
        }

        // 4. Rakit Nama Final (Contoh: ss_perencanaan_2025_0.pdf atau lkjip_2025_1_TW I.pdf)
        return "{$prefix}_{$tahun}_{$iterasi}{$tw}.{$extension}";
    }
}