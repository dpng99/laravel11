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
        // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return Redirect::route('pilih.tahun');
        }
        $level = session('id_sakip_level');
        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');
        $id_bidang = $request->get('id_bidang');
        $indikators = [];
        $data = [];

        // Ambil data dari database
        $lkjipFiles = Lkjip::where('id_periode', $tahun)
            ->where('id_satker', $idSatker)
            ->orderBy('id_perubahan', 'desc')
            ->get();

        $rapatStaffEkaFiles = RapatStaffEka::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy('id_tglupload', 'desc')
            ->get();

        $bidangs = in_array($level, [2, 3])
            ? Bidang::where('bidang_lokasi', $level)
            ->where('bidang_level', '!=', null)
            ->orderBy('bidang_level', 'asc')
            ->get()
            : [];

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
                        ->keyBy('bulan'); // <--- agar mudah akses berdasarkan bulan
                }
            }
        }

        // $rapatStaffEkaFiles = RapatStaffEka::orderBy('id_tglupload', 'desc')->get();

        return Inertia::render('Kelola/Pelaporan', ['tahun' => $tahun, 'bidangs' => $bidangs, 'lkjipFiles' => $lkjipFiles,  'rapatStaffEkaFiles' => $rapatStaffEkaFiles,]);
    }

    public function getIndikatorByBidang($id_bidang)
    {
        $indikator = Indikator::where('id_bidang', $id_bidang)
            ->select('id', 'indikator_nama', 'sub_indikator')
            ->get();

        return response()->json($indikator);
    }

    // public function getSubIndikatorByRumpun($rumpun)
    // {
    //     $indikator = Indikator::where('id_bidang', $rumpun)->get();

    //     return response()->json($indikator);
    // }

    // public function getSubIndikator($rumpun) //milik pengukuran
    // {
    //     $indikators = Indikator::where('link', $rumpun)->get();

    //     return response()->json($indikators);
    // }

    //Indikator untuk menu pengukuran
    public function getSubIndikator($rumpun)
    {
        $tahun = session('tahun_terpilih');
        $level = session('id_sakip_level');

        // Ambil semua indikator dengan filter rumpun dan tahun
        $indikators = Indikator::where('link', $rumpun)
            ->where('tahun', 'LIKE', "%$tahun%")
            ->get();

        // Filter berdasarkan lingkup & level
        $filtered = $indikators->filter(function ($indikator) use ($level) {
            switch ($indikator->lingkup ?? 0) {
                case 0:
                    return in_array($level, [1, 2, 3, 4]);
                case 1:
                    return $level == 1;
                case 2:
                    return $level == 2;
                case 3:
                    return $level == 3;
                case 4:
                    return $level == 4;
                case 5:
                    return in_array($level, [2, 3]);
                case 6:
                    return in_array($level, [3, 4]);
                case 7:
                    return in_array($level, [2, 3, 4]);
                default:
                    return false;
            }
        })->values(); // reset keys

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

    // === Ambil indikator sesuai rumpun, tahun, dan lingkup level ===
    $indikators = Indikator::where('link', $rumpun)
        ->where(function ($query) use ($tahun) {
            $query->where('tahun', 'LIKE', "%$tahun%");
        })
        ->where(function ($query) use ($level) {
            if ($level == 1) {
                $query->whereIn('lingkup', [0, 1]);
            } elseif ($level == 2) {
                $query->whereIn('lingkup', [0, 2, 5, 7]);
            } elseif ($level == 3) {
                $query->whereIn('lingkup', [0, 3, 5, 6, 7]);
            } elseif ($level == 4) {
                $query->whereIn('lingkup', [0, 4, 6, 7]);
            }
        })
        ->get();

    $data = [];

    foreach ($indikators as $indikator) {
        $persentase = 0;

        // === Tentukan label penghitungan (default: Ditangani, Diselesaikan) ===
        $labels = [];
        if (!empty($indikator->indikator_penghitungan)) {
            $labels = array_map('trim', explode(',', strtolower($indikator->indikator_penghitungan)));
        }
        if (empty($labels)) {
            $labels = ['ditangani', 'diselesaikan'];
        }

        if (count($labels) == 1) {
            // === MODE 1 LABEL ===
            // Ambil capaian dari bulan terakhir triwulan (misalnya 3,6,9,12)
            $lastMonth = $bulan_akhir;

            $persentase = DB::table('pengukuran')
                ->where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $indikator->id)
                ->where('bulan', $lastMonth)
                ->orderBy('id', 'desc')
                ->value('capaian') ?? 0;

        } elseif (count($labels) > 1) {
            // === MODE MULTI LABEL (pembilang/penyebut dari field perhitungan) ===
            $rows = DB::table('pengukuran')
                ->where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $indikator->id)
                ->whereBetween('bulan', [1, $bulan_akhir]) // kumulatif s.d akhir TW
                ->get(['sub_indikator', 'perhitungan']);

            $persentaseSub = [];

            foreach ($rows->groupBy('sub_indikator') as $subIndikator => $dataRow) {
                $pembilang = 0;
                $penyebut = 0;

                foreach ($dataRow as $row) {
                    if (!empty($row->perhitungan) && str_contains($row->perhitungan, ';')) {
                        [$a, $b] = explode(';', $row->perhitungan);

                        // Anggap format "penyebut;pembilang"
                        $penyebut += (float) $a;
                        $pembilang += (float) $b;
                    }
                }

                if ($penyebut > 0) {
                    $persentaseSub[] = round(($pembilang / $penyebut) * 100, 2);
                }
            }

            // Ambil rata-rata semua persentase sub indikator
            $persentase = count($persentaseSub) > 0
                ? round(array_sum($persentaseSub) / count($persentaseSub), 2)
                : 0;
        }

        // === Ambil Target PK ===
        $target_pk = DB::table('target')
            ->where('id_satker', $id_satker)
            ->where('tahun', $tahun)
            ->where('indikator_id', $indikator->id)
            ->value('target_tahun') ?? 0;

        // === Hitung capaian PK ===
        $capaian_pk = $target_pk > 0
            ? round(($persentase / $target_pk) * 100, 2)
            : 0;

        // === Ambil faktor & langkah (dari bulan terakhir triwulan) ===
        $first = DB::table('pengukuran')
    ->where('id_satker', $id_satker)
    ->where('tahun', $tahun)
    ->where('indikator_id', $indikator->id)
    ->where('bulan', $bulan_akhir)
    ->where(function ($q) {
        $q->whereNotNull('faktor')
          ->orWhereNotNull('langkah_optimalisasi');
    })
    ->orderBy('bulan', 'desc')
    ->orderBy('id', 'desc')
    ->first();

        $data[] = [
            'indikator_id' => $indikator->id,
            'indikator_nama' => $indikator->indikator_nama,
            'indikator_penghitungan' => $indikator->indikator_penghitungan ?: 'Ditangani, Diselesaikan',
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
         'data'     => 'required|array', // Pastikan 'data' adalah array
        'triwulan' => 'required'
        ]);
        $id_satker = session('id_satker'); 
        $tahun = session('tahun_terpilih');
        $bulan_akhir = ($request->triwulan - 1) * 3 + 3;
DB::beginTransaction();

    try {
        // 2. Looping data yang dikirim dari React
        foreach ($request->data as $item) {
            
            // Cari data Pengukuran yang sesuai
            $pengukuran = Pengukuran::where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $item['indikator_id']) // Pastikan Frontend kirim 'id' indikator
                ->where('bulan', $bulan_akhir)
                ->first();

            // Jika data ditemukan, update
            if ($pengukuran) {
                $pengukuran->faktor = $item['faktor'] ?? null;
                
                // Perhatikan: Frontend mengirim 'langkah_optimalisasi', sesuaikan kuncinya
                $pengukuran->langkah_optimalisasi = $item['langkah_optimalisasi'] ?? null;
                
                $pengukuran->save();
            } else {
                // Opsional: Jika data pengukuran belum ada (misal belum digenerate), 
                // Anda bisa memilih untuk membiarkannya atau membuat log error.
                // Untuk saat ini kita skip saja agar proses tidak berhenti.
            }
        }

        DB::commit();
        return response()->json(['status' => 'success', 'message' => 'Data berhasil disimpan']);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

   public function uploadLkjip(Request $request)
{
    // 1. Ambil Data Session
    $tahun = session('tahun_terpilih');
    $idSatker = session('id_satker');

    // 2. Validasi Input
    $request->validate([
        'lkjip_file' => 'required|mimes:pdf|max:4096', // Max 4MB
        'id_triwulan' => 'required|in:TW 1,TW 2,TW 3,TW 4',
    ]);

    $id_triwulan = $request->input('id_triwulan');

    // 3. Logic ID Perubahan (Versioning)
    $latestLkjip = Lkjip::where('id_satker', $idSatker)
        ->where('id_periode', $tahun)
        ->where('id_triwulan', $id_triwulan)
        // Casting ke UNSIGNED agar urutan angka benar (9, 10, 11...)
        ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc') 
        ->first();

    $id_perubahan = $latestLkjip ? intval($latestLkjip->id_perubahan) + 1 : 0;

    // 4. Siapkan File
    $file = $request->file('lkjip_file');
    
    // Bersihkan nama file (Ganti spasi 'TW 1' jadi 'TW_1')
    $safeTriwulan = str_replace(' ', '_', $id_triwulan);
    $fileName = 'lkjip_' . $tahun . '_' . $id_perubahan . '_' . $safeTriwulan . '.pdf';

    // Tentukan Folder Tujuan di Google Drive
    // Struktur: uploads/repository/[ID_SATKER]
    // Library Google Drive akan OTOMATIS membuat folder ini jika belum ada.
    $folderPath = 'uploads/repository/' . $idSatker;

    // 5. Eksekusi Simpan (Gunakan Try-Catch)
    try {
        // Upload ke Google Drive
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
            'id_tglupload' => now()->format('d/m/Y h:i A'), // Pastikan kolom DB tipe VARCHAR/TEXT
        ]);

        return Redirect::route('pelaporan')->with([
            'success-lkjip' => 'File LKJiP berhasil diupload ke Google Drive.', 
            'active_tab' => 'lkjip'
        ]);

    } catch (\Exception $e) {
        // Tangkap error jika gagal connect ke Google
        return Redirect::back()->withErrors([
            'lkjip_file' => 'Gagal Upload ke Google Drive: ' . $e->getMessage()
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
        $request->validate([
            'id_triwulan' => 'required|in:TW 1,TW 2,TW 3,TW 4',
            'rapat_file' => 'required|mimes:pdf|max:4096',
        ]);

        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');
        $triwulan = $request->input('id_triwulan');

        // Ambil versi terbaru dari database
        $latestRapat = RapatStaffEka::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->where('id_triwulan', $triwulan)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        $id_perubahan = $latestRapat ? $latestRapat->id_perubahan + 1 : 0;

        if ($request->hasFile('rapat_file')) {
            $file = $request->file('rapat_file');
            $filename = 'rastaff_' . $tahun . '_' . $id_perubahan . '_' . $triwulan . '.pdf';
            $file->move(base_path('uploads/repository/' . $idSatker), $filename);

            RapatStaffEka::create([
                'id_periode' => $tahun,
                'id_satker' => $idSatker,
                'id_perubahan' => $id_perubahan,
                'id_filename' => $filename,
                'id_tglupload' => now()->format('d/m/Y h:i A'),
                // Format dengan AM/PM
                'id_triwulan' => $triwulan,
            ]);

            return Redirect::route('pelaporan')->with(['success-rastaff' => 'File Rapat Staff EKA berhasil diunggah.', 'active_tab' => 'rapat-staff-eka']);
        }

        return back()->with('error', 'Gagal mengunggah file.');
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

        // Validasi: file boleh kosong, tapi triwulan wajib ada
        $validator = Validator::make($request->all(), [
            'file' => 'nullable|file|mimes:pdf|max:5120', // 5MB max
            'id_triwulan' => 'required|in:TW 1,TW 2,TW 3,TW 4',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('active_tab', $type);
        }

        // Mapping 'type' dari route ke Model Class dan prefix file
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
            // 1. Temukan record file yang akan diupdate
            $fileRecord = $modelClass::where('id', $id)
                                    ->where('id_satker', $id_satker)
                                    ->first();

            if (!$fileRecord) {
                return back()->with('error', 'File tidak ditemukan atau Anda tidak berwenang.');
            }

            $basePath = base_path('uploads/repository/' . $id_satker . '/');
            $triwulan = $request->input('id_triwulan');

            // 2. Update Triwulan (jika berubah)
            $fileRecord->id_triwulan = $triwulan;

            // 3. Handle upload file baru (jika ada)
            if ($request->hasFile('file')) {
                // A. Hapus file lama dari storage
                $oldFileName = $fileRecord->id_filename;
                $oldFilePath = $basePath . $oldFileName;
                
                if (File::exists($oldFilePath)) {
                    File::delete($oldFilePath);
                }

                // B. Buat nama file baru (versi + 1) dan simpan
                $newPerubahan = $fileRecord->id_perubahan + 1;
                $newFileName = $prefix . '_' . $tahun . '_' . $newPerubahan . '_' . $triwulan . '.pdf';

                // Simpan file baru (menggunakan move() seperti di kode Anda)
                $request->file('file')->move($basePath, $newFileName);

                // C. Siapkan data update untuk DB
                $fileRecord->id_filename = $newFileName;
                $fileRecord->id_perubahan = $newPerubahan;
                $fileRecord->id_tglupload = now()->format('d/m/Y h:i A'); // Sesuai format Anda
            
            } 
            // 4. Cek jika tidak ada file baru DAN triwulan tidak berubah
            elseif (!$fileRecord->isDirty()) { // isDirty() mengecek apakah ada perubahan atribut model
                return back()->with('error', 'Tidak ada perubahan. File baru tidak diupload dan triwulan tidak diubah.')
                             ->with('active_tab', $type);
            }

            // 5. Eksekusi Update ke Database
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

        // Mapping 'type' dari route ke Model Class yang sesuai
        $modelMap = [
            'lkjip' => \App\Models\Lkjip::class,
            'rapat-staff-eka' => \App\Models\RapatStaffEka::class,
        ];

        if (!isset($modelMap[$type])) {
            return back()->with('error', 'Tipe dokumen tidak valid.')->with('active_tab', $type);
        }

        $modelClass = $modelMap[$type];

        try {
            // 1. Temukan record file
            $fileRecord = $modelClass::where('id', $id)
                                    ->where('id_satker', $id_satker)
                                    ->first();

            if (!$fileRecord) {
                return back()->with('error', 'File tidak ditemukan atau Anda tidak berwenang.');
            }

            // 2. Tentukan nama file dan path
            $basePath = base_path('uploads/repository/' . $id_satker . '/');
            $fileName = $fileRecord->id_filename;
            $filePath = $basePath . $fileName;

            // 3. Hapus file dari storage (Gunakan File Facade)
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            // 4. Hapus record dari database
            $fileRecord->delete();

            return back()->with('success-delete', 'Dokumen berhasil dihapus.')
                         ->with('active_tab', $type);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus file: ' . $e->getMessage())
                         ->with('active_tab', $type);
        }
    }

}
