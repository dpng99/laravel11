<?php
//up
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Lkjip;
use App\Models\RapatStaffEka;
use App\Models\Indikator;
use App\Models\TargetPK;
use App\Models\Pengukuran;
use App\Models\Bidang;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
class PelaporanController extends Controller
{
    public function index(Request $request)
    {
        // Cek Session Tahun
        if (!session()->has('tahun_terpilih')) {
            return Redirect::route('pilih.tahun');
        }

        $level = session('id_sakip_level');
        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');
        $id_bidang = $request->get('id_bidang');
        
        // Ambil Data File LKJiP
        $lkjipFiles = Lkjip::where('id_periode', $tahun)
            ->where('id_satker', $idSatker)
            ->orderBy('id_perubahan', 'desc')
            ->get();

        // Ambil Data File Rapat Staff
        $rapatStaffEkaFiles = RapatStaffEka::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy('id_tglupload', 'desc')
            ->get();

        // Ambil Data Bidang (Sesuai Level)
        $bidangs = in_array($level, [2, 3])
            ? Bidang::where('bidang_lokasi', $level)
                ->where('bidang_level', '!=', null)
                ->orderBy('bidang_level', 'asc')
                ->get()
            : [];

        // Logic Indikator & Pengukuran (Jika filter bidang dipilih)
        $indikators = [];
        $data = [];

        if ($id_bidang) {
            $indikators = Indikator::where('id_bidang', $id_bidang)->get();

            foreach ($indikators as $indikator) {
                $subIndikators = explode(',', $indikator->sub_indikator);
                $pengukuranData = Pengukuran::where('indikator_id', $indikator->id)
                    ->where('id_satker', $idSatker)
                    ->where('tahun', $tahun)
                    ->get();

                $data[$indikator->id] = [
                    'nama' => $indikator->indikator_nama,
                    'sub' => []
                ];

                foreach ($subIndikators as $sub) {
                    $sub = trim($sub);
                    $data[$indikator->id]['sub'][$sub] = $pengukuranData
                        ->where('sub_indikator', $sub)
                        ->where('id_satker', $idSatker)
                        ->keyBy('bulan');
                }
            }
        }

        return Inertia::render('Kelola/Pelaporan', [
            'tahun' => $tahun, 
            'bidangs' => $bidangs, 
            'lkjipFiles' => $lkjipFiles,  
            'rapatStaffEkaFiles' => $rapatStaffEkaFiles,
        ]);
    }

 // =========================================================================
    // 🔽 BAGIAN API DATA (Indikator & Pengukuran) 🔽
    // =========================================================================

    public function getIndikatorByBidang($id_bidang)
    {
        $indikator = Indikator::where('id_bidang', $id_bidang)
            ->select('id', 'indikator_nama', 'sub_indikator')
            ->get();
        return response()->json($indikator);
    }

    public function getSubIndikator($rumpun)
    {
        $tahun = session('tahun_terpilih');
        $level = session('id_sakip_level');

        $indikators = Indikator::where('link', $rumpun)
            ->where('tahun', 'LIKE', "%$tahun%")
            ->get();

        $filtered = $indikators->filter(function ($indikator) use ($level) {
            // Filter switch case sesuai kebutuhan Anda (dipertahankan dari kode asli)
            switch ($indikator->lingkup ?? 0) {
                case 0: return in_array($level, [1, 2, 3, 4]);
                case 1: return $level == 1;
                case 2: return $level == 2;
                case 3: return $level == 3;
                case 4: return $level == 4;
                case 5: return in_array($level, [2, 3]);
                case 6: return in_array($level, [3, 4]);
                case 7: return in_array($level, [2, 3, 4]);
                default: return false;
            }
        })->values();

        return response()->json($filtered);
    }

    public function getSubIndikator2($rumpun, Request $request)
    {
        $id_satker = session('id_satker');
        $tw = $request->query('triwulan', 1);
        $tahun = session('tahun_terpilih');
        $bulan_awal = ($tw - 1) * 3 + 1;
        $bulan_akhir = $bulan_awal + 2;
        $level = session('id_sakip_level');

        // Query Indikator (Cleaned up)
        $indikators = Indikator::where('link', $rumpun)
            ->where('tahun', 'LIKE', "%$tahun%")
            ->where(function ($query) use ($level) {
                if ($level == 1) $query->whereIn('lingkup', [0, 1]);
                elseif ($level == 2) $query->whereIn('lingkup', [0, 2, 5, 7]);
                elseif ($level == 3) $query->whereIn('lingkup', [0, 3, 5, 6, 7]);
                elseif ($level == 4) $query->whereIn('lingkup', [0, 4, 6, 7]);
            })
            ->get();

        $data = [];

        foreach ($indikators as $indikator) {
            $persentase = 0; // Default 0 agar tidak error variable undefined

            // Label Handling
            $rawLabels = strtolower($indikator->indikator_penghitungan ?? '');
            $labels = !empty($rawLabels) ? array_map('trim', explode(',', $rawLabels)) : ['ditangani', 'diselesaikan'];

            if (count($labels) == 1) {
                // Single Label Logic
                $persentase = DB::table('pengukuran')
                    ->where('id_satker', $id_satker)
                    ->where('tahun', $tahun)
                    ->where('indikator_id', $indikator->id)
                    ->where('bulan', $bulan_akhir) // Ambil bulan terakhir TW
                    ->orderBy('id', 'desc')
                    ->value('capaian') ?? 0;

            } elseif (count($labels) > 1) {
                // Multi Label Logic (Kalkulasi Rata-rata Sub Indikator)
                $rows = DB::table('pengukuran')
                    ->where('id_satker', $id_satker)
                    ->where('tahun', $tahun)
                    ->where('indikator_id', $indikator->id)
                    ->whereBetween('bulan', [1, $bulan_akhir]) // Kumulatif
                    ->get(['sub_indikator', 'perhitungan']);

                $persentaseSub = [];

                foreach ($rows->groupBy('sub_indikator') as $dataRow) {
                    $pembilang = 0; 
                    $penyebut = 0;
                    foreach ($dataRow as $row) {
                        if (!empty($row->perhitungan) && str_contains($row->perhitungan, ';')) {
                            [$a, $b] = explode(';', $row->perhitungan);
                            $penyebut += (float) $a;
                            $pembilang += (float) $b;
                        }
                    }
                    if ($penyebut > 0) {
                        $persentaseSub[] = round(($pembilang / $penyebut) * 100, 2);
                    }
                }
                
                // Hitung Rata-rata dari semua sub indikator
                $persentase = count($persentaseSub) > 0 
                    ? round(array_sum($persentaseSub) / count($persentaseSub), 2) 
                    : 0;
            }

            // Hitung Capaian PK vs Target
            $target_pk = DB::table('target')
                ->where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $indikator->id)
                ->value('target_tahun') ?? 0;

            $capaian_pk = $target_pk > 0 
                ? round(($persentase / $target_pk) * 100, 2) 
                : 0;

            // Ambil Data Keterangan (Faktor & Langkah)
            $first = DB::table('pengukuran')
                ->where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $indikator->id)
                ->where('bulan', $bulan_akhir)
                ->orderBy('bulan', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $data[] = [
                'indikator_id' => $indikator->id,
                'indikator_nama' => $indikator->indikator_nama,
                'indikator_penghitungan' => implode(', ', $labels),
                'persentase' => $persentase,
                'target_pk' => $target_pk,
                'capaian_pk' => $capaian_pk,
                'faktor' => $first->faktor ?? '',
                'langkah_optimalisasi' => $first->langkah_optimalisasi ?? '',
            ];
        }

        return response()->json($data);
    }

    public function simpanKeterangan(Request $request)
    {
        $request->validate([
            'data'     => 'required|array',
            'triwulan' => 'required'
        ]);

        $id_satker = session('id_satker');
        $tahun = session('tahun_terpilih');
        $bulan_akhir = ($request->triwulan - 1) * 3 + 3;

        DB::beginTransaction();
        try {
            foreach ($request->data as $item) {
                // Cari data Pengukuran di bulan akhir triwulan
                $pengukuran = Pengukuran::where('id_satker', $id_satker)
                    ->where('tahun', $tahun)
                    ->where('indikator_id', $item['indikator_id'])
                    ->where('bulan', $bulan_akhir)
                    ->first();

                if ($pengukuran) {
                    $pengukuran->faktor = $item['faktor'] ?? null;
                    $pengukuran->langkah_optimalisasi = $item['langkah_optimalisasi'] ?? null;
                    $pengukuran->save();
                }
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Keterangan berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

 public function uploadLkjip(Request $request)
{
    // 1. Validasi
    $request->validate([
        'lkjip_file' => 'required|mimes:pdf|max:10240', // Max 10MB (Saran: naikkan dari 4MB ke 10MB)
        'id_triwulan' => 'required|in:TW 1,TW 2,TW 3,TW 4',
    ]);

    $tahun = session('tahun_terpilih');
    $idSatker = session('id_satker');
    $id_triwulan = $request->input('id_triwulan');

    // 2. Cek Versi Terakhir (Versioning)
    $latestLkjip = Lkjip::where('id_satker', $idSatker)
        ->where('id_periode', $tahun)
        ->where('id_triwulan', $id_triwulan)
        ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') 
        ->first();

    $id_perubahan = $latestLkjip ? intval($latestLkjip->id_perubahan) + 1 : 0;

    // 3. Siapkan Nama File & Folder
    $file = $request->file('lkjip_file');
    $safeTriwulan = str_replace(' ', '_', $id_triwulan); // Ubah "TW 1" jadi "TW_1"
    
    // Nama file: lkjip_2024_0_TW_1.pdf
    $fileName = 'lkjip_' . $tahun . '_' . $id_perubahan . '_' . $safeTriwulan . '.pdf';

    // Folder Tujuan
    $folderPath = 'uploads/repository/' . $idSatker;

    // 5. Eksekusi Simpan
    try {
        // --- PERBAIKAN DI SINI ---
        // Langsung gunakan $file tanpa 'new File()'
        Storage::disk('google')->putFileAs(
            $folderPath, 
            $file, 
            $fileName
        );

        // Simpan Data ke Database
        Lkjip::create([
            'id_satker'    => $idSatker,
            'id_periode'   => $tahun,
            'id_triwulan'  => $id_triwulan,
            'id_perubahan' => $id_perubahan,
            'id_filename'  => $fileName, 
            'id_tglupload' => now()->format('d/m/Y h:i A'), 
        ]);

        return Redirect::route('pelaporan')->with([
            'success-lkjip' => 'Berhasil upload ke Google Drive!',
            'active_tab' => 'lkjip'
        ]);

    } catch (\Exception $e) {
        return Redirect::back()->withErrors([
            'lkjip_file' => 'Gagal Upload: ' . $e->getMessage()
        ])->withInput();
    }
}

    // public function deleteLkjip($id)
    // {
    //     $idSatker = session('id_satker'); 
    //     $file = Lkjip::findOrFail($id);

    //     // Hapus file dari storage
    //     $filePath = base_path('uploads/repository/' . $idSatker . '/'. $file->filename);
    //     if (file_exists($filePath)) {
    //         unlink($filePath);
    //     }

    //     // Hapus dari database
    //     $file->delete();

    //     return redirect()->back()->with('success-lkjip', 'File berhasil dihapus.');
    // }

   public function uploadRapatStaffEka(Request $request)
{
    // 1. Ambil Session & Validasi
    $tahun = session('tahun_terpilih');
    $idSatker = session('id_satker');

    // Cek Session Safety (Opsional tapi disarankan)
    if (!$tahun || !$idSatker) {
        return Redirect::back()->withErrors(['msg' => 'Sesi habis, silakan reload halaman.']);
    }

    $request->validate([
        'id_triwulan' => 'required|in:TW 1,TW 2,TW 3,TW 4',
        'rapat_file'  => 'required|mimes:pdf|max:10240', // Max 10MB (Saran: samakan limitnya)
    ]);

    $triwulan = $request->input('id_triwulan');

    // 2. Logic Versioning (Id Perubahan)
    $latestRapat = RapatStaffEka::where('id_satker', $idSatker)
        ->where('id_periode', $tahun)
        ->where('id_triwulan', $triwulan)
        ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
        ->first();

    $id_perubahan = $latestRapat ? intval($latestRapat->id_perubahan) + 1 : 0;

    // 3. Siapkan File & Folder
    $file = $request->file('rapat_file');

    // Sanitasi Nama File: Ubah "TW 1" jadi "TW_1" agar URL Google Drive aman
    $safeTriwulan = str_replace(' ', '_', $triwulan);
    
    // Format nama: rastaff_2024_0_TW_1.pdf
    $filename = 'rastaff_' . $tahun . '_' . $id_perubahan . '_' . $safeTriwulan . '.pdf';

    // Folder Tujuan di Google Drive
    // Library akan otomatis membuat folder ini jika belum ada
    $folderPath = 'uploads/repository/' . $idSatker;

    // 4. Eksekusi Upload (Try-Catch)
    try {
        // --- UPLOAD KE GOOGLE DRIVE ---
        Storage::disk('google')->putFileAs(
            $folderPath, 
            $file, 
            $filename
        );

        // --- SIMPAN KE DATABASE ---
        RapatStaffEka::create([
            'id_periode'   => $tahun,
            'id_satker'    => $idSatker,
            'id_triwulan'  => $triwulan,
            'id_perubahan' => $id_perubahan,
            'id_filename'  => $filename,
            // Pastikan kolom database tipe VARCHAR jika pakai format ini.
            // Jika tipe DATETIME, ganti jadi: now()
            'id_tglupload' => now()->format('d/m/Y h:i A'), 
        ]);

        return Redirect::route('pelaporan')->with([
            'success-rastaff' => 'File Rapat Staff EKA berhasil diunggah ke Google Drive.',
            'active_tab'      => 'rapat-staff-eka'
        ]);

    } catch (\Exception $e) {
        // Tangkap error koneksi / token
        return Redirect::back()->withErrors([
            'rapat_file' => 'Gagal Upload ke Google Drive: ' . $e->getMessage()
        ])->withInput();
    }
}
    // ... (Method uploadRapatStaffEka() Anda ada di sini)


    /**
     * Memperbarui file dokumen yang ada (dipanggil dari modal edit).
     * Tipe: 'lkjip' atau 'rapat-staff-eka'
     */
   public function updateFile(Request $request, $type, $id)
{
    $id_satker = session('id_satker');
    $tahun = session('tahun_terpilih');

    // 1. Validasi
    $validator = Validator::make($request->all(), [
        'file' => 'nullable|file|mimes:pdf|max:10240', // Saran: Naikkan ke 10MB
        'id_triwulan' => 'required|in:TW 1,TW 2,TW 3,TW 4',
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('active_tab', $type);
    }

    // 2. Mapping Type ke Model
    $modelMap = [
        'lkjip' => [
            'model' => \App\Models\Lkjip::class,
            'prefix' => 'lkjip'
        ],
        'rapat-staff-eka' => [
            'model' => \App\Models\RapatStaffEka::class,
            'prefix' => 'rastaff'
        ],
    ];

    if (!isset($modelMap[$type])) {
        return back()->with('error', 'Tipe dokumen tidak valid.')->with('active_tab', $type);
    }

    $modelClass = $modelMap[$type]['model'];
    $prefix = $modelMap[$type]['prefix'];

    try {
        // 3. Cari Data Lama
        $fileRecord = $modelClass::where('id', $id)
            ->where('id_satker', $id_satker)
            ->first();

        if (!$fileRecord) {
            return back()->with('error', 'File tidak ditemukan atau akses ditolak.');
        }

        // Folder di Google Drive
        $folderPath = 'uploads/repository/' . $id_satker;
        $triwulanBaru = $request->input('id_triwulan');

        // Update Triwulan di Object Model (belum disimpan ke DB)
        $fileRecord->id_triwulan = $triwulanBaru;

        // 4. Cek apakah ada file baru yang diupload?
        if ($request->hasFile('file')) {
            
            // A. Hapus File Lama di Google Drive (Jika ada)
            if ($fileRecord->id_filename) {
                $pathLama = $folderPath . '/' . $fileRecord->id_filename;
                // Cek keberadaan file di Drive dulu untuk menghindari error
                if (Storage::disk('google')->exists($pathLama)) {
                    Storage::disk('google')->delete($pathLama);
                }
            }

            // B. Siapkan Nama File Baru
            $newPerubahan = intval($fileRecord->id_perubahan) + 1;
            
            // Sanitasi Triwulan (TW 1 -> TW_1)
            $safeTriwulan = str_replace(' ', '_', $triwulanBaru);
            
            // Nama file: prefix_tahun_versi_triwulan.pdf
            $newFileName = $prefix . '_' . $tahun . '_' . $newPerubahan . '_' . $safeTriwulan . '.pdf';

            // C. Upload File Baru ke Google Drive
            Storage::disk('google')->putFileAs(
                $folderPath, 
                $request->file('file'), 
                $newFileName
            );

            // D. Update Metadata di Model
            $fileRecord->id_filename = $newFileName;
            $fileRecord->id_perubahan = $newPerubahan;
            // Gunakan now() agar format sesuai database (Y-m-d H:i:s)
            // Jika kolom di DB adalah VARCHAR, gunakan ->format('d/m/Y h:i A')
            $fileRecord->id_tglupload = now()->format('d/m/Y h:i A'); 
        }

        // 5. Cek Perubahan (Dirty Check)
        // Jika tidak ada file baru DAN triwulan tidak berubah, jangan simpan.
        if (!$fileRecord->isDirty()) {
            return back()->with('error', 'Tidak ada perubahan data.')
                         ->with('active_tab', $type);
        }

        // 6. Simpan ke Database
        $fileRecord->save();

        return back()->with('success-update', 'Dokumen berhasil diperbarui.')
                     ->with('active_tab', $type);

    } catch (\Exception $e) {
        return back()->with('error', 'Gagal memperbarui file: ' . $e->getMessage())
                     ->with('active_tab', $type);
    }
}

    /**
     * Menghapus file dokumen.
     * Tipe: 'lkjip' atau 'rapat-staff-eka'
     */
   public function deleteFile($type, $id)
{
    $id_satker = session('id_satker');

    // 1. Mapping Tipe ke Model
    $modelMap = [
        'lkjip' => \App\Models\Lkjip::class,
        'rapat-staff-eka' => \App\Models\RapatStaffEka::class,
    ];

    if (!isset($modelMap[$type])) {
        return back()->with('error', 'Tipe dokumen tidak valid.')->with('active_tab', $type);
    }

    $modelClass = $modelMap[$type];

    try {
        // 2. Temukan record di Database
        $fileRecord = $modelClass::where('id', $id)
            ->where('id_satker', $id_satker)
            ->first();

        if (!$fileRecord) {
            return back()->with('error', 'File tidak ditemukan atau Anda tidak berwenang.');
        }

        // 3. Tentukan Path di Google Drive
        // Format path harus sama persis dengan saat upload
        $googleDrivePath = 'uploads/repository/' . $id_satker . '/' . $fileRecord->id_filename;

        // 4. Hapus File Fisik di Google Drive
        // Cek dulu apakah file ada di cloud agar tidak error 404
        if (Storage::disk('google')->exists($googleDrivePath)) {
            Storage::disk('google')->delete($googleDrivePath);
        } else {
            // Opsional: Log warning jika file di DB ada tapi di Drive hilang
            // \Log::warning("File hantu ditemukan: " . $googleDrivePath);
        }

        // 5. Hapus Record dari Database
        // Dilakukan SETELAH hapus file fisik berhasil (atau file fisik memang tidak ada)
        $fileRecord->delete();

        return back()->with('success-delete', 'Dokumen berhasil dihapus selamanya.')
                     ->with('active_tab', $type);

    } catch (\Exception $e) {
        return back()->with('error', 'Gagal menghapus file: ' . $e->getMessage())
                     ->with('active_tab', $type);
    }
}

}
