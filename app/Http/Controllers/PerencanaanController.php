<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Perencanaan; // Ubah ke nama model baru
use App\Models\Renstra;
use App\Models\Iku;
use App\Models\Renja;
use App\Models\Rkakl;
use App\Models\Dipa;
use App\Models\Renaksi;
use App\Models\Bidang;
use App\Models\SinoriSakipPidum;
use App\Models\SinoriSakipIndikator;
use App\Models\TargetPK;
use App\Models\Pk;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
class PerencanaanController extends Controller
{
    public function index()
    {
        // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
           return Redirect::route('pilih.tahun');
        }
$level = session('id_sakip_level');
$satkernama = session('satkernama');
        // Ambil tahun yang dipilih dari session
        $tahun = session('tahun_terpilih');

        // Ambil nilai id_satker dari session
        $id_satker = session('id_satker');
        if ($tahun == "2024") {
            $id_periode = "P1";
        } else {
            $id_periode = "P2";
        }
        // Ambil data Renstra, IKU, Renja
        $renstra = Renstra::getData($id_satker, $id_periode);
        $iku = Iku::getData($id_satker, $tahun);
        $renja = Renja::getData($id_satker, $tahun);
        $rkakl = Rkakl::getData($id_satker, $tahun);
        $dipa = Dipa::getData($id_satker, $tahun);
        $renaksi = Renaksi::getData($id_satker, $tahun);
        $indikator = SinoriSakipIndikator::getData();
        $bidang = Bidang::where('id', $level)->where('bidang_nama', 'LIKE', '%' . $satkernama . '%')->get();
        // dd($bidang);
        // Panggil method untuk mendapatkan data target indikator 

        // $indicatorIds = [22, 23, 24, 25]; // Inisialisasi ID indikator di controller
        // $indikator_pidum = SinoriSakipPidum::getData($id_satker, $tahun, $indicatorIds);

        // Ambil data target berdasarkan indikator_id, id_satker, dan tahun
        $target = TargetPK::where('id_satker', $id_satker)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('indikator_id'); // Agar mudah diakses di Blade
        // dd($bidang);
        // return view('perencanaan.input_indikator', compact('indikator', 'pidumTargets'));
        // );
// Ambil data PK untuk satker & tahun
        $pk = Pk::where('id_satker', $id_satker)
                ->where('id_periode', $tahun)
                ->orderBy('id_perubahan', 'asc')
                ->get();

        // dd ($indikator_pidum);
        // Kembalikan view beserta data yang telah difilter
        return Inertia::render('Kelola/Perencanaan', ['renstra' => $renstra, 'iku' => $iku, 'renja' => $renja, 'tahun' => $tahun, 'rkakl' => $rkakl, 'dipa' => $dipa, 'renaksi' => $renaksi, 'indikator' => $indikator, 'target' => $target, 'bidang' => $bidang, 'pk' => $pk]);
    }


public function uploadRenstra(Request $request)
{
    // 1. Ambil Session
    $tahun = session('tahun_terpilih');
    $idSatker = session('id_satker');

    // Cek Session Safety
    if (!$tahun || !$idSatker) {
        return Redirect::back()->withErrors(['msg' => 'Sesi habis, silakan reload halaman.']);
    }

    // 2. Validasi File
    $request->validate([
        'renstra_file' => 'required|mimes:pdf|max:10240', // Saya naikkan ke 10MB agar aman
    ]);

    // 3. Logic Penentuan Periode
    // Inisialisasi variabel dulu biar tidak "Undefined variable"
    $id_periode = null; 

    if ($tahun == "2024") {
        $id_periode = "P1";
    } elseif ($tahun >= "2025" && $tahun <= "2029") {
        $id_periode = "P2";
    } else {
        // Handle jika tahun diluar range (Opsional)
        // return back()->with('error', 'Tahun terpilih tidak memiliki periode Renstra.');
        $id_periode = "Lainnya"; // Atau default value
    }

    // 4. Logic Versioning (ID Perubahan)
    $latestRenstra = Renstra::where('id_satker', $idSatker)
        ->where('id_periode', $id_periode)
        ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
        ->first();

    $id_perubahan = $latestRenstra ? intval($latestRenstra->id_perubahan) + 1 : 0;

    // 5. Siapkan Nama File & Path
    $file = $request->file('renstra_file');
    
    // Nama file: renstra_2025_0.pdf
    $fileName = 'renstra_' . $tahun . '_'. $id_perubahan .'.pdf'; 
    
    // Folder di Google Drive: uploads/repository/[ID_SATKER]
    $folderPath = 'uploads/repository/' . $idSatker;

    // 6. Eksekusi Upload (Gunakan Try-Catch)
    try {
        // --- UPLOAD KE GOOGLE DRIVE ---
        // Library otomatis bikin folder jika belum ada
        Storage::disk('google')->putFileAs(
            $folderPath, 
            $file, 
            $fileName
        );

        // --- SIMPAN KE DATABASE ---
        Renstra::create([
            'id_satker'    => $idSatker,
            'id_periode'   => $id_periode,
            'id_perubahan' => $id_perubahan,
            'id_filename'  => $fileName,
            // Gunakan format string jika kolom DB adalah VARCHAR
            'id_tglupload' => now()->format('d/m/Y h:i A'), 
        ]);

        return Redirect::back()->with([
            'success-renstra' => 'File Renstra berhasil disimpan ke Google Drive!',
            'active_tab'      => 'renstra'
        ]);

    } catch (\Exception $e) {
        // Tangkap error koneksi / token
        return Redirect::back()->withErrors([
            'renstra_file' => 'Gagal Upload ke Google Drive: ' . $e->getMessage()
        ])->withInput();
    }
}

    // Fungsi untuk menangani upload file Iku
    public function uploadIku(Request $request)
{
    // 1. Ambil Session
    $tahun = session('tahun_terpilih');
    $idSatker = session('id_satker');

    // Cek Session (Agar tidak error jika user diam terlalu lama)
    if (!$tahun || !$idSatker) {
        return Redirect::back()->withErrors(['msg' => 'Sesi habis, silakan reload halaman.']);
    }

    // 2. Validasi
    $request->validate([
        'iku_file' => 'required|mimes:pdf|max:10240', // Saya naikkan jadi 10MB biar aman
    ]);

    // 3. Logic Versioning (ID Perubahan)
    $latestIku = Iku::where('id_satker', $idSatker)
        ->where('id_periode', $tahun)
        ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
        ->first();

    $id_perubahan = $latestIku ? intval($latestIku->id_perubahan) + 1 : 0;

    // 4. Siapkan File & Path
    $file = $request->file('iku_file');
    
    // Nama File: IKU_2024_0.pdf
    $fileName = 'IKU_' . $tahun . '_' . $id_perubahan .'.pdf';
    
    // Folder di Google Drive: uploads/repository/[ID_SATKER]
    $folderPath = 'uploads/repository/'. $idSatker;

    // 5. Eksekusi Upload (Try-Catch)
    try {
        // --- UPLOAD KE GOOGLE DRIVE ---
        Storage::disk('google')->putFileAs(
            $folderPath, 
            $file, 
            $fileName
        );

        // --- SIMPAN KE DATABASE ---
        Iku::create([
            'id_satker'    => $idSatker,
            'id_periode'   => $tahun,
            'id_perubahan' => $id_perubahan,
            'id_filename'  => $fileName,
            'id_tglupload' => now()->format('d/m/Y h:i A'),
        ]);

        return Redirect::route('perencanaan')->with([
            'success-iku' => 'File IKU berhasil diupload ke Google Drive.',
            'active_tab'  => 'iku'
        ]);

    } catch (\Exception $e) {
        // Tangkap error token/koneksi
        return Redirect::back()->withErrors([
            'iku_file' => 'Gagal Upload ke Google Drive: ' . $e->getMessage()
        ])->withInput();
    }
}

    // Fungsi untuk menangani upload file Renja
 // ==========================================
// 1. UPLOAD RENJA (Google Drive Version)
// ==========================================
public function uploadRenja(Request $request)
{
    $tahun = session('tahun_terpilih');
    $idSatker = session('id_satker');

    if (!$tahun || !$idSatker) {
        return Redirect::back()->withErrors(['msg' => 'Sesi habis, silakan reload.']);
    }

    $request->validate([
        'renja_file' => 'required|mimes:pdf|max:10240', // Max 10MB
    ]);

    $latestRenja = Renja::where('id_satker', $idSatker)
        ->where('id_periode', $tahun)
        ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
        ->first();

    $id_perubahan = $latestRenja ? intval($latestRenja->id_perubahan) + 1 : 0;
    
    $file = $request->file('renja_file');
    $fileName = 'renja_' . $tahun . '_' . $id_perubahan .'.pdf';
    $folderPath = 'uploads/repository/' . $idSatker;

    try {
        Storage::disk('google')->putFileAs($folderPath, $file, $fileName);

        Renja::create([
            'id_satker'    => $idSatker,
            'id_periode'   => $tahun,
            'id_perubahan' => $id_perubahan,
            'id_filename'  => $fileName,
            'id_tglupload' => now()->format('d/m/Y h:i A'),
        ]);

        return Redirect::route('perencanaan')->with([
            'success-renja' => 'File Renja berhasil diupload ke Google Drive.',
            'active_tab'    => 'renja'
        ]);

    } catch (\Exception $e) {
        return Redirect::back()->withErrors(['renja_file' => 'Gagal Upload: ' . $e->getMessage()]);
    }
}

// ==========================================
// 2. UPLOAD RKAKL (Google Drive Version)
// ==========================================
public function uploadRkakl(Request $request)
{
    $tahun = session('tahun_terpilih');
    $idSatker = session('id_satker');

    if (!$tahun || !$idSatker) {
        return Redirect::back()->withErrors(['msg' => 'Sesi habis.']);
    }

    $request->validate([
        'rkakl_file' => 'required|mimes:pdf|max:10240',
    ]);

    $latestRkakl = Rkakl::where('id_satker', $idSatker)
        ->where('id_periode', $tahun)
        ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
        ->first();

    $id_perubahan = $latestRkakl ? intval($latestRkakl->id_perubahan) + 1 : 0;

    $file = $request->file('rkakl_file');
    $fileName = 'rkakl_' . $tahun . '_'. $id_perubahan . '.pdf';
    $folderPath = 'uploads/repository/' . $idSatker;

    try {
        Storage::disk('google')->putFileAs($folderPath, $file, $fileName);

        Rkakl::create([
            'id_satker'    => $idSatker,
            'id_periode'   => $tahun,
            'id_perubahan' => $id_perubahan,
            'id_filename'  => $fileName,
            'id_tglupload' => now()->format('d/m/Y h:i A'),
        ]);

        return Redirect::route('perencanaan')->with([
            'success-rkakl' => 'File RKAKL berhasil diupload ke Google Drive.',
            'active_tab'    => 'rkakl'
        ]);

    } catch (\Exception $e) {
        return Redirect::back()->withErrors(['rkakl_file' => 'Gagal Upload: ' . $e->getMessage()]);
    }
}

// ==========================================
// 3. UPLOAD DIPA (Google Drive Version)
// ==========================================
public function uploadDipa(Request $request)
{
    $tahun = session('tahun_terpilih');
    $idSatker = session('id_satker');

    if (!$tahun || !$idSatker) {
        return Redirect::back()->withErrors(['msg' => 'Sesi habis.']);
    }

    // Validasi input tambahan (Pagu, dll)
    $request->validate([
        'dipa_file'    => 'required|mimes:pdf|max:10240',
        'id_pagu'      => 'required|numeric',
        'id_gakyankum' => 'required|numeric',
        'id_dukman'    => 'required|numeric',
    ]);

    $latestDipa = Dipa::where('id_satker', $idSatker)
        ->where('id_periode', $tahun)
        ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
        ->first();

    $id_perubahan = $latestDipa ? intval($latestDipa->id_perubahan) + 1 : 0;

    $file = $request->file('dipa_file');
    $fileName = 'dipa_' . $tahun . '_'. $id_perubahan . '.pdf';
    $folderPath = 'uploads/repository/' . $idSatker;

    try {
        // Upload ke Google Drive
        Storage::disk('google')->putFileAs($folderPath, $file, $fileName);

        // Simpan DB dengan data Pagu
        Dipa::create([
            'id_satker'    => $idSatker,
            'id_periode'   => $tahun,
            'id_perubahan' => $id_perubahan,
            'id_filename'  => $fileName,
            'id_pagu'      => $request->input('id_pagu'),
            'id_gakyankum' => $request->input('id_gakyankum'),
            'id_dukman'    => $request->input('id_dukman'),
            'id_tglupload' => now()->format('d/m/Y h:i A'),
        ]);

        return Redirect::route('perencanaan')->with([
            'success-dipa' => 'File DIPA berhasil diupload ke Google Drive.',
            'active_tab'   => 'dipa'
        ]);

    } catch (\Exception $e) {
        return Redirect::back()->withErrors(['dipa_file' => 'Gagal Upload: ' . $e->getMessage()])->withInput();
    }
}

// ==========================================
// 4. UPLOAD RENAKSI (Google Drive Version)
// ==========================================
public function uploadRenaksi(Request $request)
{
    $tahun = session('tahun_terpilih');
    $idSatker = session('id_satker');

    if (!$tahun || !$idSatker) {
        return Redirect::back()->withErrors(['msg' => 'Sesi habis.']);
    }

    $request->validate([
        'renaksi_file' => 'required|mimes:pdf|max:10240',
    ]);

    $latestRenaksi = Renaksi::where('id_satker', $idSatker)
        ->where('id_periode', $tahun)
        ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
        ->first();

    $id_perubahan = $latestRenaksi ? intval($latestRenaksi->id_perubahan) + 1 : 0;

    $file = $request->file('renaksi_file');
    $fileName = 'renaksi_' . $tahun . '_'. $id_perubahan . '.pdf';
    $folderPath = 'uploads/repository/' . $idSatker;

    try {
        Storage::disk('google')->putFileAs($folderPath, $file, $fileName);

        Renaksi::create([
            'id_satker'    => $idSatker,
            'id_periode'   => $tahun,
            'id_perubahan' => $id_perubahan,
            'id_filename'  => $fileName,
            'id_tglupload' => now()->format('d/m/Y h:i A'),
        ]);

        return Redirect::route('perencanaan')->with([
            'success-renaksi' => 'File Renaksi berhasil diupload ke Google Drive.',
            'active_tab'      => 'renaksi'
        ]);

    } catch (\Exception $e) {
        return Redirect::back()->withErrors(['renaksi_file' => 'Gagal Upload: ' . $e->getMessage()]);
    }
}
    public function storetarget(Request $request)
    {
        $request->validate([
            'indikator_id' => 'required|exists:sinori_sakip_indikator,id',
            'target_tahun' => 'required|numeric',
            'target_triwulan_1' => 'numeric',
            'target_triwulan_2' => 'numeric',
            'target_triwulan_3' => 'numeric',
            'target_triwulan_4' => 'numeric',
        ]);
 
        // Ambil session id_satker dan tahun
        $id_satker = session('id_satker');
        $tahun = session('tahun_terpilih');
        // Cek apakah data sudah ada
        $existingTarget = TargetPK::where('indikator_id', $request->indikator_id)
            ->where('id_satker', $id_satker)
            ->where('tahun', $tahun)
            ->first();
          
        if ($existingTarget) {
            // Jika sudah ada, update data
            $existingTarget->update([
                'target_tahun' => $request->target_tahun,
                'target_triwulan_1' => $request->target_triwulan_1,
                'target_triwulan_2' => $request->target_triwulan_2,
                'target_triwulan_3' => $request->target_triwulan_3,
                'target_triwulan_4' => $request->target_triwulan_4,
            ]);

            // return Redirect::back()->with('success', 'Target berhasil diperbarui!');
           return Redirect::route('perencanaan')->with('success-pk', 'Target berhasil diperbarui!')->with('active_tab', 'perjanjian-kinerja');
        }

        // Jika belum ada, buat data baru
        TargetPK::create([
            'indikator_id' => $request->indikator_id,
            'id_satker' => $id_satker,
            'tahun' => $tahun,
            'target_tahun' => $request->target_tahun,
            'target_triwulan_1' => $request->target_triwulan_1,
            'target_triwulan_2' => $request->target_triwulan_2,
            'target_triwulan_3' => $request->target_triwulan_3,
            'target_triwulan_4' => $request->target_triwulan_4,
        ]);

        // return Redirect::back()->with('success', 'Target berhasil disimpan!');
       return Redirect::route('perencanaan')->with('success-pk', 'Target berhasil disimpan!')->with('active_tab', 'perjanjian-kinerja');
    }
    
    // Fungsi untuk menangani upload file PK
public function uploadPK(Request $request)
{
    $tahun = session('tahun_terpilih');
    $request->validate([
        'pk_file' => 'required|mimes:pdf|max:5120', // Maksimal 5MB
    ]);

    $idSatker = session('id_satker'); // Ambil id_satker dari session

    // Cek id_perubahan terakhir
    $latestPK = Pk::where('id_satker', $idSatker)
        ->where('id_periode', $tahun)
        ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
        ->first();

    // Tentukan id_perubahan baru
    $id_perubahan = $latestPK ? $latestPK->id_perubahan + 1 : 0;

    // Upload file ke folder public/uploads/repository/{id_satker}
    $file = $request->file('pk_file');
    $fileName = 'pk_' . $tahun . '_' . $id_perubahan . '.pdf';
    $file->move(base_path('uploads/repository/' . $idSatker), $fileName);

    // Simpan ke database
    Pk::create([
        'id_satker'    => $idSatker,
        'id_periode'   => $tahun,
        'id_perubahan' => $id_perubahan,
        'id_filename'  => $fileName,
        'id_tglupload' => now()->format('d/m/Y h:i A'),
    ]);

    return Redirect::route('perencanaan')->with('success-pk-file','File PK berhasil diupload!')->with('active_tab', 'perjanjian-kinerja');
}
// ... (Method uploadPK Anda yang ada)

 // 🔽 [AWAL] KODE BARU UNTUK UPDATE FILE (Google Drive) 🔽
public function updateFile(Request $request, $type, $id)
{
    $id_satker = session('id_satker');
    $tahun = session('tahun_terpilih');

    // 1. Validasi
    $validator = Validator::make($request->all(), [
        'file' => 'nullable|file|mimes:pdf|max:10240', // Max 10MB
        'id_pagu' => 'nullable|numeric',
        'id_gakyankum' => 'nullable|numeric',
        'id_dukman' => 'nullable|numeric',
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('activeTab', $type);
    }

    // 2. Mapping Route Type ke Model Class
    $modelMap = [
        'renstra' => \App\Models\Renstra::class,
        'iku'     => \App\Models\Iku::class,
        'renja'   => \App\Models\Renja::class,
        'rkakl'   => \App\Models\Rkakl::class,
        'dipa'    => \App\Models\Dipa::class,
        'renaksi' => \App\Models\Renaksi::class,
        'pk'      => \App\Models\Pk::class,
    ];

    if (!isset($modelMap[$type])) {
        return back()->with('error', 'Tipe dokumen tidak valid.')->with('activeTab', $type);
    }

    $modelClass = $modelMap[$type];

    try {
        // 3. Temukan Record
        $fileRecord = $modelClass::where('id', $id)
            ->where('id_satker', $id_satker)
            ->first();

        if (!$fileRecord) {
            return back()->with('error', 'File tidak ditemukan atau akses ditolak.');
        }

        // Folder Google Drive
        $folderPath = 'uploads/repository/' . $id_satker;

        // 4. Khusus Update Data DIPA (Metadata)
        if ($type == 'dipa') {
            if ($request->filled('id_pagu')) $fileRecord->id_pagu = $request->input('id_pagu');
            if ($request->filled('id_gakyankum')) $fileRecord->id_gakyankum = $request->input('id_gakyankum');
            if ($request->filled('id_dukman')) $fileRecord->id_dukman = $request->input('id_dukman');
        }

        // 5. Handle Upload File Baru (Jika ada)
        if ($request->hasFile('file')) {
            
            // A. Hapus File Lama di Google Drive
            if ($fileRecord->id_filename) {
                $pathLama = $folderPath . '/' . $fileRecord->id_filename;
                if (Storage::disk('google')->exists($pathLama)) {
                    Storage::disk('google')->delete($pathLama);
                }
            }

            // B. Generate Nama File Baru
            $newPerubahan = intval($fileRecord->id_perubahan) + 1;
            
            // Mapping Prefix Nama File
            $prefixMap = [
                'renstra' => 'renstra', 'iku' => 'IKU', 'renja' => 'renja',
                'rkakl' => 'rkakl', 'dipa' => 'dipa', 'renaksi' => 'renaksi', 'pk' => 'pk',
            ];
            
            $prefix = $prefixMap[$type] ?? $type;
            
            // Format: renstra_2024_1.pdf
            $newFileName = $prefix . '_' . $tahun . '_' . $newPerubahan . '.pdf';

            // C. Upload ke Google Drive
            Storage::disk('google')->putFileAs($folderPath, $request->file('file'), $newFileName);

            // D. Update Metadata DB
            $fileRecord->id_filename = $newFileName;
            $fileRecord->id_perubahan = $newPerubahan;
            $fileRecord->id_tglupload = now()->format('d/m/Y h:i A');
        } 
        
        // 6. Cek Dirty (Apakah ada perubahan sama sekali?)
        elseif (!$fileRecord->isDirty()) {
            return back()->with('error', 'Tidak ada perubahan data.')
                         ->with('activeTab', $type);
        }

        // 7. Simpan DB
        $fileRecord->save();

        return back()->with('success-update', 'Dokumen berhasil diperbarui.')
                     ->with('activeTab', $type);

    } catch (\Exception $e) {
        return back()->with('error', 'Gagal update: ' . $e->getMessage())
                     ->with('activeTab', $type);
    }
}
// 🔼 [AKHIR] KODE UPDATE FILE 🔼


// 🔽 [AWAL] KODE BARU UNTUK DELETE FILE (Google Drive) 🔽
public function deleteFile($type, $id)
{
    $id_satker = session('id_satker');

    // 1. Mapping Type
    $modelMap = [
        'renstra' => \App\Models\Renstra::class,
        'iku'     => \App\Models\Iku::class,
        'renja'   => \App\Models\Renja::class,
        'rkakl'   => \App\Models\Rkakl::class,
        'dipa'    => \App\Models\Dipa::class,
        'renaksi' => \App\Models\Renaksi::class,
        'pk'      => \App\Models\Pk::class,
    ];

    if (!isset($modelMap[$type])) {
        return back()->with('error', 'Tipe dokumen tidak valid.')->with('activeTab', $type);
    }

    $modelClass = $modelMap[$type];

    try {
        // 2. Temukan Record
        $fileRecord = $modelClass::where('id', $id)
            ->where('id_satker', $id_satker)
            ->first();

        if (!$fileRecord) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        // 3. Hapus File Fisik di Google Drive
        $pathFile = 'uploads/repository/' . $id_satker . '/' . $fileRecord->id_filename;

        if (Storage::disk('google')->exists($pathFile)) {
            Storage::disk('google')->delete($pathFile);
        }

        // 4. Hapus Record DB
        $fileRecord->delete();

        return back()->with('success-delete', 'Dokumen berhasil dihapus permanen.')
                     ->with('activeTab', $type);

    } catch (\Exception $e) {
        return back()->with('error', 'Gagal hapus: ' . $e->getMessage())
                     ->with('activeTab', $type);
    }
}
    // 🔼 [AKHIR] KODE BARU UNTUK DELETE FILE 🔼

} // <-- Penutup Class Controller