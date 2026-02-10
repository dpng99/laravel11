<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\absen_pm;
use App\Models\ba_pleno;
use App\Models\ba_praeval;
use App\Models\DataLke1;
use App\Models\Dipa;
use App\Models\Iku;
use App\Models\lhe_2023;
use App\Models\LheAkip;
use App\Models\lke_subkomponens;
use Dflydev\DotAccessData\Data;
use App\Models\lke_buktidukung;
use App\Models\lke_komponen;
use App\Models\Lkjip;
use App\Models\memo_datakinerja;
use App\Models\memo_lkjip;
use App\Models\MonevRenaksi;
use App\Models\nodis_eval_sakip;
use App\Models\nodis_p_sakip;
use App\Models\notulensi_pm;
use App\Models\Pk;
use App\Models\PokinRanwal;
use App\Models\Renaksi;
use App\Models\Renja;
use App\Models\Renstra;
use App\Models\reward_punish;
use App\Models\Rkakl;
use App\Models\sampel_rekom;
use App\Models\sample_skp;
use App\Models\sk_pk;
use App\Models\sk_pm;
use App\Models\tar_pm;
use App\Models\TlLheAkip;
use App\Models\tar_lkjip;
use App\Models\ss_perencanaan;
use App\Models\ss_laporanweb;
use App\Models\ss_laporanapp;
use App\Models\nodis_datakinerja;
use App\Models\RapatStaffEka;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
class EvaluasiControllerNew extends Controller
{
    // ==========================================
    // 1. KONFIGURASI MAPPING
    // ==========================================

    private function getMapping()
    {
        return [
            1 => Renstra::class, 
            2 => Renja::class, 
            3 => Renaksi::class, 
            4 => Rkakl::class,
            5 => Dipa::class, 
            6 => Pk::class, 
            7 => Pk::class, 
            8 => Iku::class, 
            9 => Iku::class,
            10 => Lkjip::class, 
            11 => Lkjip::class, 
            12 => LheAkip::class,
            13 => RapatStaffEka::class, 
            14 => RapatStaffEka::class,
            15 => LheAkip::class, 
            16 => LheAkip::class, 
            17 => TlLheAkip::class,
            18 => MonevRenaksi::class, 
            19 => MonevRenaksi::class, 
            20 => PokinRanwal::class,
            21 => Renstra::class, 
            22 => Lkjip::class, 
            23 => sample_skp::class,
            24 => sk_pm::class, 
            25 => sk_pk::class, 
            26 => absen_pm::class,
            27 => notulensi_pm::class, 
            28 => nodis_p_sakip::class,
            29 => nodis_eval_sakip::class,
            30 => memo_datakinerja::class, 
            31 => nodis_datakinerja::class, 
            32 => reward_punish::class,
            33 => sampel_rekom::class, 
            34 => ss_perencanaan::class, 
            35 => ss_laporanweb::class,
            36 => ss_laporanapp::class, 
            37 => tar_lkjip::class, 
            38 => tar_lkjip::class,
            39 => memo_lkjip::class, 
            40 => memo_lkjip::class, 
            41 => tar_pm::class,
            42 => ba_praeval::class, 
            43 => ba_pleno::class, 
            44 => LheAkip::class, 
        ];
    }


private function getTriwulanMapping()
    {
        return [
            10 => 'TW 1', 11 => 'TW 2', 12 => 'TW 4',
            13 => 'TW 1', 14 => 'TW 2',
            18 => 'TW 1', 19 => 'TW 2',
            37 => 'TW 1', 38 => 'TW 2',
            39 => 'TW 1', 40 => 'TW 2',
        ];
    }



    // ==========================================
    // 2. INDEX (DENGAN ELOQUENT RELATIONSHIP)
    // ==========================================

    public function index()
    {
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }

        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');

        $lkeHierarki = lke_komponen::with(['subKomponens.kriterias.buktiDukungs'])
                        ->orderBy('id', 'asc')
                        ->get();

        $modelMapping = $this->getMapping();
        $triwulanMapping = $this->getTriwulanMapping();
        
        // Hapus variabel $manualCodes karena sudah tidak dipakai

        $lkeDataFlat = collect([]);

        foreach ($lkeHierarki as $komponen) {
            foreach ($komponen->subKomponens as $sub) {
                foreach ($sub->kriterias as $kriteria) {
                    $buktiList = [];
                    foreach ($kriteria->buktiDukungs as $buktiRef) {
                        $kode = $buktiRef->id;
                        $namaDokumen = $buktiRef->dokumen;
                        $status = 'Tidak Ada';
                        $fileLink = null;
                        // 1. CEK BUKTI DUKUNG (Prioritas Utama)
                        $buktiLke = DB::table('bukti_dukung')
                            ->where('id_satker', $idSatker)
                            ->where('id_kriteria', $kriteria->kode)
                            //->where('kode_bukti', $kode)
                            ->first();
                        if ($buktiLke && !empty($buktiLke->link_bukti_dukung)) {
                            $status = 'Ada';
                            $fileLink = asset("uploads/repository/{$idSatker}/{$buktiLke->link_bukti_dukung}");
                        } 
                        else {
                            // 2. CEK TABEL SUMBER (System Availability)
                            // Sekarang SEMUA kode dicek, asalkan terdaftar di mapping
                            if (isset($modelMapping[$kode])) {
                                // Fungsi checkSystemAvailability kita sudah "Foolproof"
                                // Dia akan mengecek kolom secara dinamis, jadi aman untuk tabel SK, Reward, dll.
                                $check = $this->checkSystemAvailability($kode, $idSatker, $tahun, $modelMapping, $triwulanMapping);
                                
                                if ($check) {
                                    $status = 'Tersedia di Sistem (Belum Verif)';
                                }else {
                                    $status = 'Tidak Ada';
                                }
                            }
                        }

                        $buktiList[] = [
                            'kode_bukti' => $kode,
                            'nama_dokumen' => $namaDokumen,
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

        $lkeGrouped = $lkeDataFlat->groupBy('id_komponen')->map(function ($subItems) {
            return $subItems->groupBy('id_sub_komponen');
        });

        // Query tambahan untuk tab lain (biarkan seperti adanya)
        $lheAkipFiles = LheAkip::where('id_satker', $idSatker)->where('id_periode', $tahun)->orderByDesc('id')->get();
        $tlLheAkipFiles = TlLheAkip::where('id_satker', $idSatker)->where('id_periode', $tahun)->orderByDesc('id')->get();
        $monevRenaksiFiles = MonevRenaksi::where('id_periode', $tahun)->where('id_satker', $idSatker)->orderByDesc('id_perubahan')->get();

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
    // 3. UPLOAD (SIMPAN KE BUKTI_DUKUNG + TABEL SUMBER)
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
        $file = $request->file('file');
        $kode = (int)$request->kode_bukti;

        // 1. Proses Fisik File
        $dokRef = lke_buktidukung::find($kode);
        $cleanName = $dokRef ? preg_replace('/[^A-Za-z0-9]/', '_', $dokRef->dokumen) : 'Dokumen';
        $cleanName = substr($cleanName, 0, 40);
        $extension = $file->getClientOriginalExtension();
        $filename = "{$cleanName}_{$idSatker}_{$tahun}_" . time() . ".{$extension}";

        $destinationPath = public_path("uploads/repository/{$idSatker}");
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }
        $file->move($destinationPath, $filename);

        // 2. Simpan ke Tabel Utama (bukti_dukung)
        DB::table('bukti_dukung')->updateOrInsert(
            [
                'id_satker'   => $idSatker,
                'id_kriteria' => $request->id_kriteria,
                //'kode_bukti'  => $kode
            ],
            [
                'id_komponen'       => $request->id_komponen,
                'id_sub_komponen'   => $request->id_sub_komponen,
                'link_bukti_dukung' => $filename,
                'tgl_pengisian'     => now()->format('d/m/Y H:i A'),
            ]
        );

        // 3. Simpan ke Tabel Sumber (Renstra, Renja, dll) - INI FITUR BARUNYA
        // Cek apakah kode ini memiliki tabel sumber di getMapping()
        $mapping = $this->getMapping();

        // Jika ada di mapping dan bukan dokumen manual murni
        if (isset($mapping[$kode])) {
            $this->saveToSourceTable($kode, $filename, $idSatker, $tahun);
        }

        return back()->with('success', 'File berhasil diupload dan disimpan ke modul terkait.');
    }

  // ==========================================
    // 4. VERIFIKASI (PENGECEKAN KE SISTEM)
    // ==========================================

    public function verifikasi(Request $request)
    {
        $request->validate([
            'id_kriteria' => 'required',
            'kode_bukti' => 'required'
        ]);

        $idSatker = session('id_satker');
        $tahun = session('tahun_terpilih');
        $kode = (int)$request->kode_bukti;

        $mapping = $this->getMapping();
        $triwulanMapping = $this->getTriwulanMapping();

        if (!isset($mapping[$kode])) {
             return $this->sendResponse($request, false, 'Model tidak ditemukan.');
        }

        // Cek apakah data ada di tabel sumber
        $dokumenSumber = $this->checkSystemAvailability($kode, $idSatker, $tahun, $mapping, $triwulanMapping);

        if ($dokumenSumber) {
            // Ambil nama file dengan berbagai kemungkinan nama kolom
            $filename = $dokumenSumber->id_filename 
                        ?? $dokumenSumber->file 
                        ?? $dokumenSumber->nama_file 
                        ?? $dokumenSumber->link_bukti_dukung 
                        ?? null;

            if ($filename) {
                // Simpan ke bukti_dukung (Linking)
                DB::table('bukti_dukung')->updateOrInsert(
                    [
                        'id_satker'   => $idSatker,
                        'id_kriteria' => $request->id_kriteria,
                        'kode_bukti'  => $kode
                    ],
                    [
                        'id_komponen'       => $request->id_komponen,
                        'id_sub_komponen'   => $request->id_sub_komponen,
                        'link_bukti_dukung' => $filename,
                        'tgl_pengisian'     => now()->format('d/m/Y H:i A'),
                    ]
                );

                return $this->sendResponse($request, true, 'Verifikasi Berhasil! File dari sistem telah ditautkan.');
            }
        }

        return $this->sendResponse($request, false, 'File belum tersedia di sistem.');
    }

   // ==========================================
    // 5. HELPERS & LOGIC PENYIMPANAN
    // ==========================================

    /**
     * Logic Cerdas untuk menyimpan data ke tabel sumber (Renstra, LKJiP, dll)
     */
private function saveToSourceTable($kode, $filename, $idSatker, $tahun)
    {
        $mapping = $this->getMapping();
        $triwulanMapping = $this->getTriwulanMapping();
        
        $modelClass = $mapping[$kode];
        if (is_string($modelClass)) return;

        $model = new $modelClass();
        $table = $model->getTable();

        // A. DATA UMUM (Wajib untuk semua dokumen)
        $model->id_satker = $idSatker;
        $model->id_periode = $tahun;

        // B. HANDLING TANGGAL & NAMA FILE (Dinamis cek kolom)
        if (Schema::hasColumn($table, 'id_tglupload')) {
            $model->id_tglupload = now();
        } elseif (Schema::hasColumn($table, 'tgl_upload')) {
            $model->tgl_upload = now();
        }

        if (Schema::hasColumn($table, 'id_filename')) {
            $model->id_filename = $filename;
        } elseif (Schema::hasColumn($table, 'file')) {
            $model->file = $filename;
        } elseif (Schema::hasColumn($table, 'link_bukti_dukung')) {
            $model->link_bukti_dukung = $filename;
        } elseif (Schema::hasColumn($table, 'nama_file')) {
            $model->nama_file = $filename;
        }

        // C. KHUSUS DOKUMEN TRIWULAN
        // Jika dokumen Tahunan, blok ini DILEWATI (id_triwulan dibiarkan null/default)
        if (isset($triwulanMapping[$kode])) {
            $valTw = $triwulanMapping[$kode];
            
            if (Schema::hasColumn($table, 'id_triwulan')) {
                $model->id_triwulan = $valTw;
            } elseif (Schema::hasColumn($table, 'triwulan')) {
                $model->triwulan = str_replace('TW ', '', $valTw);
            }
        }

        // D. KHUSUS DOKUMEN PERUBAHAN (VERSIONING)
        // Berlaku untuk Tahunan maupun Triwulan yang punya revisi
        $isPerubahan = in_array($kode, [21, 7, 9, 19]); // Daftar Kode Dokumen "Perubahan"
        
        if (Schema::hasColumn($table, 'id_perubahan')) {
            if ($isPerubahan) {
                // Cari versi terakhir + 1
                $lastVer = $modelClass::where('id_satker', $idSatker)
                            ->where('id_periode', $tahun)
                            ->max('id_perubahan');
                $model->id_perubahan = ($lastVer > 0) ? $lastVer + 1 : 1; 
            } else {
                $model->id_perubahan = 0; // Dokumen Murni
            }
        }

        // E. KOLOM TAMBAHAN (Opsional/Default)
        if (Schema::hasColumn($table, 'id_pagu')) $model->id_pagu = 0;
        if (Schema::hasColumn($table, 'id_gakyankum')) $model->id_gakyankum = 0;
        if (Schema::hasColumn($table, 'id_dukman')) $model->id_dukman = 0;

        try {
            $model->save();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gagal simpan ke tabel sumber ($kode): " . $e->getMessage());
        }
    }

 private function checkSystemAvailability($kode, $idSatker, $tahun, $modelMapping, $triwulanMapping)
    {
        $modelClass = $modelMapping[$kode];
        // Skip jika mapping tidak valid

        $query = $modelClass::where('id_satker', $idSatker);

        // 1. Filter Tahun (Berlaku untuk Tahunan & Triwulan)
        if (in_array($kode, [7, 12, 15, 17])) {
            $query->where('id_periode', '2024'); // Hardcode untuk dokumen tertentu
        } else {
            $query->where('id_periode', $tahun);
        }

        // 2. Filter Triwulan (KHUSUS DOKUMEN TRIWULAN)
        // Jika kode ada di mapping triwulan, kita filter.
        // Jika TIDAK ADA (Dokumen Tahunan), blok ini otomatis DILEWATI.
        if (isset($triwulanMapping[$kode])) {
            $valTw = $triwulanMapping[$kode];           
            $valAngka = str_replace('TW ', '', $valTw); 

            $query->where(function($q) use ($valTw, $valAngka) {
                $tableName = $q->getModel()->getTable();
                if (Schema::hasColumn($tableName, 'id_triwulan')) {
                    $q->where('id_triwulan', $valTw);
                } elseif (Schema::hasColumn($tableName, 'triwulan')) {
                    $q->where('triwulan', $valAngka);
                }
            });
        }
        // [NOTE]: Dokumen Tahunan (Renstra, Renja, dll) langsung lanjut ke sini tanpa filter triwulan.

        // 3. Filter Versi (PENTING untuk Dokumen Tahunan: Murni vs Perubahan)
        // Contoh: Membedakan "Renstra Awal" (1) dengan "Renstra Perubahan" (21)
        
        // Grup Dokumen Murni (id_perubahan = 0)
        if (in_array($kode, [1, 6, 8, 18])) { 
            $tableName = $query->getModel()->getTable();
            if (Schema::hasColumn($tableName, 'id_perubahan')) {
                $query->where('id_perubahan', 0);
            }
        } 
        // Grup Dokumen Perubahan (id_perubahan > 0)
        elseif (in_array($kode, [21, 7, 9, 19])) { 
            $tableName = $query->getModel()->getTable();
            if (Schema::hasColumn($tableName, 'id_perubahan')) {
    return $query->orderBy('id_perubahan', 'desc')->first();
            } else {
                // Jika tidak punya versi perubahan, ambil yang terakhir diinput (atau default)
                return $query->latest()->first(); 
                // pastikan model punya timestamps (created_at), jika tidak pakai ->orderBy('id', 'desc')
            }
        }

        // Ambil data terbaru
        return $query->orderBy('id_perubahan', 'desc')->first();
    }

    private function sendResponse($request, $success, $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => $success, 'message' => $message], $success ? 200 : 404);
        }
        return Redirect::back()->with($success ? 'success' : 'error', $message);
    }


    public function uploadTlLheAkip(Request $request)
    {
        $request->validate([
            'tl_lhe_akip_file' => 'required|mimes:pdf|max:4096',
        ]);

        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');

        // Cek versi terbaru
        $latestFile = TlLheAkip::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        $id_perubahan = $latestFile ? $latestFile->id_perubahan + 1 : 0;

        if ($request->hasFile('tl_lhe_akip_file')) {
            $file = $request->file('tl_lhe_akip_file');
            $filename = 'tl_lhe_akip_' . $tahun . '_' . $id_perubahan . '.pdf';
            $file->move(base_path('uploads/repository/' . $idSatker), $filename);

            TlLheAkip::create([
                'id_periode' => $tahun,
                'id_satker' => $idSatker,
                'id_perubahan' => $id_perubahan,
                'id_filename' => $filename,
                'id_tglupload' => now()->format('d/m/Y h:i A'),

            ]);

            return redirect()->route('evaluasi')->with(['success-tllhe' => 'File TL LHE AKIP berhasil diunggah.', 'active_tab' => 'tl-lhe-akip']);
        }

        return back()->with('error', 'Gagal mengunggah file.');
    }

    public function uploadMonevRenaksi(Request $request)
    {
        $request->validate([
            'id_triwulan' => 'required|in:TW 1,TW 2,TW 3,TW 4',
            'monev_file' => 'required|mimes:pdf|max:4096',
        ]);

        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');
        $id_triwulan = $request->input('id_triwulan');

        // Ambil versi terakhir untuk triwulan tersebut
        $latestmonev = MonevRenaksi::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->where('id_triwulan', $id_triwulan)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        $id_perubahan = $latestmonev ? $latestmonev->id_perubahan + 1 : 0;

        if ($request->hasFile('monev_file')) {
            $file = $request->file('monev_file');

            // Pastikan folder ada
            $destination = base_path('uploads/repository/' . $idSatker);
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }

            $filename = 'renaksieval_' . $tahun . '_' . $id_perubahan . '_' . $id_triwulan . '.pdf';
            $file->move($destination, $filename);

            // Simpan data ke database
            MonevRenaksi::create([
                'id_periode' => $tahun,
                'id_satker' => $idSatker,
                'id_perubahan' => $id_perubahan,
                'id_filename' => $filename,
                'id_tglupload' => now()->format('d/m/Y h:i A'),
                'id_triwulan' => $id_triwulan,
            ]);

            return redirect()->route('evaluasi')
                ->with([
                    'success-monev' => 'File Monev Renaksi berhasil diunggah.',
                    'active_tab' => 'monev-renaksi'
                ]);
        }

        return back()->with('error', 'Gagal mengunggah file.');
    }
  }

