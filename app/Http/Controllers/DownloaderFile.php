<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Renstra;
use App\Models\Pk;
use App\Models\Iku;
use App\Models\Rkakl;
use App\Models\Dipa;
use App\Models\Renja;
use App\Models\Renaksi;
use App\Models\Lkjip;
use App\Models\lhe_2023;
use App\Models\TlLheAkip;
use ZipArchive;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class DownloaderFile extends Controller
{
    public function index()
    {
        // Cek apakah user adalah Admin (Level 1 atau 99, sesuaikan dengan logic role Anda)
        // Contoh: Level 1 = Admin Pusat
        if (Auth::user()->id_sakip_level != 1 && Auth::user()->id_sakip_level != 99) {
            abort(403, 'Unauthorized action.');
        }

        // Ambil daftar Kejati untuk dropdown
        // Logika: Kejati biasanya adalah user yang id_satker == id_kejati
        // Atau ambil distinct id_kejati dan cari nama satkernya
        $kejatiList = User::query()
            ->select('id_satker', 'satkernama', 'id_kejati')
            ->whereColumn('id_satker', 'id_kejati') // Mengambil induk Kejati
            ->orderBy('satkernama', 'asc')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id_kejati, // Value untuk dropdown
                    'label' => $item->id_satker . ' - ' . $item->satkernama // Label tampilan
                ];
            });

        return Inertia::render('Admin/DownloadZip', [
            'kejatiList' => $kejatiList
        ]);
    }
    public function downloadKejati(Request $request, $id_kejati)
    {
        // 1. Validasi & Cari Daftar Satker berdasarkan ID Kejati
        // Asumsi: Table users memiliki kolom 'id_kejati' dan 'id_satker'
        $satkers = User::where('id_kejati', $id_kejati)->pluck('id_satker');

        if ($satkers->isEmpty()) {
            return response()->json(['message' => 'Tidak ada satuan kerja ditemukan untuk Kejati ini.'], 404);
        }

        // 2. Daftar Model Dokumen yang akan diambil
        $dokumenMap = [
            'Renstra' => Renstra::class,
            'PK'      => Pk::class,
            'IKU'     => Iku::class,
            'Rkakl'   => Rkakl::class,
            'Dipa'    => Dipa::class,
            'Renja'   => Renja::class,
            'Renaksi' => Renaksi::class,
            'LKJiP'   => Lkjip::class,
            'LHE'     => lhe_2023::class,
            'TL_LHE'  => TlLheAkip::class,
        ];

        // 3. Siapkan File ZIP
        $zip = new ZipArchive;
        $fileName = 'Dokumen_Kejati_' . $id_kejati . '_' . date('Ymd_His') . '.zip';
        
        // Simpan sementara di folder public/downloads
        $path = public_path('downloads');
        if(!File::isDirectory($path)){
            File::makeDirectory($path, 0777, true, true);
        }
        $zipFilePath = $path . '/' . $fileName;

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            
            $fileCount = 0;

            // 4. Loop setiap jenis dokumen
            foreach ($dokumenMap as $folderName => $modelClass) {
                
                // Ambil data file milik satker-satker tersebut
                // Filter tahun jika dikirim via request (opsional)
                $query = $modelClass::whereIn('id_satker', $satkers);
                if ($request->has('tahun')) {
                    // Cek nama kolom tahun/periode
                    $dummy = new $modelClass();
                    $col = Schema::hasColumn($dummy->getTable(), 'tahun') ? 'tahun' : 'id_periode';
                    $query->where($col, $request->tahun);
                }
                
                $files = $query->get();

                foreach ($files as $file) {
                    // Nama file fisik di server (biasanya kolom id_filename atau file)
                    $realFilename = $file->id_filename ?? $file->file ?? null;
                    $satkerCode = $file->id_satker;

                    if ($realFilename) {
                        // Lokasi file fisik
                        $filePath = public_path("uploads/repository/{$satkerCode}/{$realFilename}");

                        if (file_exists($filePath)) {
                            // Struktur Folder dalam ZIP:
                            // KodeSatker / JenisDokumen / NamaFile
                            $zipInnerPath = "{$satkerCode}/{$folderName}/{$realFilename}";
                            
                            $zip->addFile($filePath, $zipInnerPath);
                            $fileCount++;
                        }
                    }
                }
            }

            $zip->close();

            if ($fileCount === 0) {
                return response()->json(['message' => 'File ZIP berhasil dibuat namun kosong (tidak ada dokumen fisik ditemukan).'], 404);
            }

            // 5. Download dan Hapus File ZIP temp setelah dikirim
            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        
        } else {
            return response()->json(['message' => 'Gagal membuat file ZIP'], 500);
        }
    }
}
