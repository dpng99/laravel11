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

    // Fungsi untuk menangani upload file Renstra
    public function uploadRenstra(Request $request)
    {
        $tahun = session('tahun_terpilih');
        // Validasi file
        $request->validate([
            'renstra_file' => 'required|mimes:pdf|max:2048', // maksimal 2MB
        ]);

        if ($tahun == "2024") {
            $id_periode = "P1";
        } elseif ($tahun >= "2025" && $tahun <= "2029") {
            $id_periode = "P2";
        }

        $idSatker = session('id_satker'); // Ambil id_satker dari session

        // Cek id_perubahan yang sudah ada
        $latestRenstra = Renstra::where('id_satker', $idSatker)
            ->where('id_periode', $id_periode)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        // Tentukan id_perubahan
        $id_perubahan = $latestRenstra ? $latestRenstra->id_perubahan + 1 : 0;

        // Upload file ke folder public/uploads/repository/renstra
        $file = $request->file('renstra_file');
        $fileName = 'renstra_' . $tahun . '_'. $id_perubahan .'.pdf'; // Buat nama file
        $file->move(base_path('uploads/repository/'.$idSatker), $fileName); // Simpan di folder 'renstra' di public
        // $file->move(base_path('uploads/repository/renstra'), $fileName);
        // Format tanggal upload ke d/m/y H:i:s
        $id_tglupload = now()->format('d/m/Y h:i A');


        // dd($id_periode);
        // Simpan data ke database
        Renstra::create([
            'id_satker' => $idSatker,
            'id_periode' => $id_periode, // Sesuaikan dengan data periode 2019 - 2024
            'id_perubahan' => $id_perubahan, // Simpan id_perubahan yang baru
            'id_filename' => $fileName,
            'id_tglupload' => $id_tglupload, // Simpan tanggal upload dengan format yang diinginkan
        ]);

        //return Redirect::route('perencanaan')->with('success', 'File Renstra berhasil diupload.')->with('active_tab', 'renstra');
        return Redirect::back()->with('success-renstra', 'File Renstra berhasil disimpan!')->with('active_tab', 'renstra');
    }

    // Fungsi untuk menangani upload file Iku
    public function uploadIku(Request $request)
    {
        $tahun = session('tahun_terpilih');
        $request->validate([
            'iku_file' => 'required|mimes:pdf|max:2048', // Maksimal 2MB
        ]);

        $idSatker = session('id_satker'); // Ambil id_satker dari session

        // Cek id_perubahan yang sudah ada
        $latestIku = Iku::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        // Tentukan id_perubahan
        $id_perubahan = $latestIku ? $latestIku->id_perubahan + 1 : 0;

        // Upload file ke folder public/uploads/repository/iku
        $file = $request->file('iku_file');
        $fileName = 'IKU_' . $tahun . '_' . $id_perubahan .'.pdf';
        $file->move(base_path('uploads/repository/'. $idSatker), $fileName);

        // Simpan data ke database
        Iku::create([
            'id_satker' => $idSatker,
            'id_periode' => $tahun,
            'id_perubahan' => $id_perubahan,
            'id_filename' => $fileName,
            'id_tglupload' => now()->format('d/m/Y h:i A'),
        ]);

       return Redirect::route('perencanaan')->with('success-iku', 'File IKU berhasil diupload.')->with('active_tab', 'iku');
    }

    // Fungsi untuk menangani upload file Renja
    public function uploadRenja(Request $request)
    {
        $tahun = session('tahun_terpilih');
        $request->validate([
            'renja_file' => 'required|mimes:pdf|max:2048', // Maksimal 2MB
        ]);

        $idSatker = session('id_satker'); // Ambil id_satker dari session

        // Cek id_perubahan yang sudah ada
        $latestrenja = Renja::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        // Tentukan id_perubahan
        $id_perubahan = $latestrenja ? $latestrenja->id_perubahan + 1 : 0;

        // Upload file ke folder public/uploads/repository/renja
        $file = $request->file('renja_file');
        $fileName = 'renja_' . $tahun . '_' . $id_perubahan .'.pdf';
        $file->move(base_path('uploads/repository/' . $idSatker), $fileName);

        // Simpan data ke database
        Renja::create([
            'id_satker' => $idSatker,
            'id_periode' => $tahun,
            'id_perubahan' => $id_perubahan,
            'id_filename' => $fileName,
            'id_tglupload' => now()->format('d/m/Y h:i A'),
        ]);

       return Redirect::route('perencanaan')->with('success-renja', 'File renja berhasil diupload.')->with('active_tab', 'renja');
    }

    // Fungsi untuk menangani upload file Rkakl
    public function uploadRkakl(Request $request)
    {
        $tahun = session('tahun_terpilih');
        $request->validate([
            'rkakl_file' => 'required|mimes:pdf|max:4096', // Maksimal 2MB
        ]);

        $idSatker = session('id_satker'); // Ambil id_satker dari session

        // Cek id_perubahan yang sudah ada
        $latestrkakl = Rkakl::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        // Tentukan id_perubahan
        $id_perubahan = $latestrkakl ? $latestrkakl->id_perubahan + 1 : 0;

        // Upload file ke folder public/uploads/repository/rkakl
        $file = $request->file('rkakl_file');
        $fileName = 'rkakl_' . $tahun . '_'. $id_perubahan . '.pdf';
        $file->move(base_path('uploads/repository/'.$idSatker), $fileName);

        // Simpan data ke database
        Rkakl::create([
            'id_satker' => $idSatker,
            'id_periode' => $tahun,
            'id_perubahan' => $id_perubahan,
            'id_filename' => $fileName,
            'id_tglupload' => now()->format('d/m/Y h:i A'),
        ]);

       return Redirect::route('perencanaan')->with('success-rkakl', 'File rkakl berhasil diupload.')->with('active_tab', 'rkakl');
    }

    // Fungsi untuk menangani upload file Dipa
    public function uploadDipa(Request $request)
{
    $tahun = session('tahun_terpilih');
    $idSatker = session('id_satker'); // Ambil id_satker dari session

    // Validasi input
    $request->validate([
        'dipa_file' => 'required|mimes:pdf|max:2048', // Maksimum 2MB, hanya PDF
        'id_pagu' => 'required|numeric',
        'id_gakyankum' => 'required|numeric',
        'id_dukman' => 'required|numeric',
    ]);

    // Ambil data perubahan terakhir dari tabel
    $latestdipa = Dipa::where('id_satker', $idSatker)
        ->where('id_periode', $tahun)
        ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
        ->first();

    // Jika ada data sebelumnya, tambahkan +1 untuk id_perubahan, jika tidak mulai dari 0
    $id_perubahan = ($latestdipa && is_numeric($latestdipa->id_perubahan)) ? $latestdipa->id_perubahan + 1 : 0;

    // Upload file ke folder public/uploads/repository/dipa
    try {
        $file = $request->file('dipa_file');
        $fileName = 'dipa_' . $tahun . '_'. $id_perubahan . '.pdf';
        $destinationPath = base_path('uploads/repository/'.$idSatker);
        
        // Pindahkan file ke folder tujuan
        $file->move($destinationPath, $fileName);
    } catch (\Exception $e) {
        return Redirect::back()->with('error', 'Gagal mengunggah file: ' . $e->getMessage());
    }
 
    // Simpan data ke database
    Dipa::create([
        'id_satker' => $idSatker,
        'id_periode' => $tahun,
        'id_perubahan' => $id_perubahan,
        'id_filename' => $fileName,
        'id_pagu' => $request->input('id_pagu'),
        'id_gakyankum' => $request->input('id_gakyankum'),
        'id_dukman' => $request->input('id_dukman'),
        'id_tglupload' => now()->format('d/m/Y h:i A'),
    ]);

   return Redirect::route('perencanaan')
        ->with('success-dipa', 'File DIPA berhasil diupload.')
        ->with('active_tab', 'dipa');
}

    // Fungsi untuk menangani upload file Dipa
    public function uploadRenaksi(Request $request)
    {
        $tahun = session('tahun_terpilih');
        $request->validate([
            'renaksi_file' => 'required|mimes:pdf|max:2048', // Maksimal 2MB
        ]);

        $idSatker = session('id_satker'); // Ambil id_satker dari session

        // Cek id_perubahan yang sudah ada
        $latestrenaksi = Renaksi::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        // Tentukan id_perubahan
        $id_perubahan = $latestrenaksi ? $latestrenaksi->id_perubahan + 1 : 0;

        // Upload file ke folder public/uploads/repository/renaksi
        $file = $request->file('renaksi_file');
        $fileName = 'renaksi_' . $tahun . '_'. $id_perubahan . '.pdf';
        $file->move(base_path('uploads/repository/'.$idSatker), $fileName);

        // Simpan data ke database
        Renaksi::create([
            'id_satker' => $idSatker,
            'id_periode' => $tahun,
            'id_perubahan' => $id_perubahan,
            'id_filename' => $fileName,
            'id_tglupload' => now()->format('d/m/Y h:i A'),
        ]);

       return Redirect::route('perencanaan')->with('success-renaksi', 'File renaksi berhasil diupload.')->with('active_tab', 'renaksi');
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

    // 🔽 [AWAL] KODE BARU UNTUK UPDATE FILE 🔽
    /**
     * Memperbarui file dokumen yang ada (dipanggil dari modal edit).
     */
    public function updateFile(Request $request, $type, $id)
    {
        $id_satker = session('id_satker');
        $tahun = session('tahun_terpilih');

        $validator = Validator::make($request->all(), [
            'file' => 'nullable|file|mimes:pdf|max:5120', // 5MB max
            'id_pagu' => 'nullable|numeric',
            'id_gakyankum' => 'nullable|numeric',
            'id_dukman' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('activeTab', $type);
        }

        // Mapping 'type' dari route ke Model Class yang sesuai
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
            // 1. Temukan record file yang akan diupdate
            $fileRecord = $modelClass::where('id', $id)
                                    ->where('id_satker', $id_satker)
                                    ->first();

            if (!$fileRecord) {
                return back()->with('error', 'File tidak ditemukan atau Anda tidak berwenang.');
            }

            $basePath = base_path('uploads/repository/' . $id_satker . '/');

            // 2. Handle data DIPA (jika ini adalah update DIPA)
            if ($type == 'dipa') {
                if ($request->filled('id_pagu')) {
                    $fileRecord->id_pagu = $request->input('id_pagu');
                }
                if ($request->filled('id_gakyankum')) {
                    $fileRecord->id_gakyankum = $request->input('id_gakyankum');
                }
                if ($request->filled('id_dukman')) {
                    $fileRecord->id_dukman = $request->input('id_dukman');
                }
            }

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

                // Tentukan prefix nama file baru
                $prefixMap = [
                    'renstra' => 'renstra', 'iku' => 'IKU', 'renja' => 'renja',
                    'rkakl' => 'rkakl', 'dipa' => 'dipa', 'renaksi' => 'renaksi', 'pk' => 'pk',
                ];
                $prefix = $prefixMap[$type];
                $newFileName = $prefix . '_' . $tahun . '_' . $newPerubahan . '.pdf';

                // Simpan file baru
                $request->file('file')->move($basePath, $newFileName);

                // C. Siapkan data update untuk DB
                $fileRecord->id_filename = $newFileName;
                $fileRecord->id_perubahan = $newPerubahan;
                $fileRecord->id_tglupload = now()->format('d/m/Y h:i A');
            
            } 
            // 4. Cek jika tidak ada file baru DAN tidak ada update data DIPA
            elseif (!$fileRecord->isDirty()) { // isDirty() mengecek apakah ada perubahan atribut model
                return back()->with('error', 'Tidak ada perubahan. File baru tidak diupload dan/atau data DIPA tidak diubah.')
                             ->with('activeTab', $type);
            }

            // 5. Eksekusi Update ke Database
            $fileRecord->save();

            return back()->with('success-update', 'Dokumen berhasil diperbarui.')
                         ->with('activeTab', $type);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui file: ' . $e->getMessage())
                         ->with('activeTab', $type);
        }
    }
    // 🔼 [AKHIR] KODE BARU UNTUK UPDATE FILE 🔼


    // 🔽 [AWAL] KODE BARU UNTUK DELETE FILE 🔽
    /**
     * Menghapus file dokumen.
     */
    public function deleteFile($type, $id)
    {
        $id_satker = session('id_satker');

        // Mapping 'type' dari route ke Model Class yang sesuai
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
                         ->with('activeTab', $type);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus file: ' . $e->getMessage())
                         ->with('activeTab', $type);
        }
    }
    // 🔼 [AKHIR] KODE BARU UNTUK DELETE FILE 🔼

} // <-- Penutup Class Controller