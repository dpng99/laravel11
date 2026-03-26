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
//    public function index(Request $request)
//     {
//         if (!session()->has('tahun_terpilih')) return redirect()->route('pilih.tahun');

//         $tahun = session('tahun_terpilih');
//         $idSatker = session('id_satker');
//         $id_periode = ($tahun == "2024") ? "P1" : "P2";

//         // 🔥 OPTIMASI: Ambil HANYA kolom yang dibutuhkan untuk menghemat RAM
//         $selectCols = ['id', 'id_filename', 'id_perubahan', 'id_tglupload'];

//         // Helper untuk memanggil data dengan cepat
//         $fetchLatest = fn($model, $periodeCol, $periodeVal) => 
//             $model::select($selectCols)
//                 ->where('id_satker', $idSatker)
//                 ->where($periodeCol, $periodeVal)
//                 ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
//                 ->get();

//         return Inertia::render('Kelola/Perencanaan', [
//             'tahun' => $tahun,
//             'renstra' => $fetchLatest(Renstra::class, 'id_periode', $id_periode),
//             'iku'     => $fetchLatest(Iku::class, 'id_periode', $tahun),
//             'renja'   => $fetchLatest(Renja::class, 'id_periode', $tahun),
//             'rkakl'   => $fetchLatest(Rkakl::class, 'id_periode', $tahun),
//             'renaksi' => $fetchLatest(Renaksi::class, 'id_periode', $tahun),
//             'pk'      => $fetchLatest(Pk::class, 'id_periode', $tahun),
//             // DIPA butuh kolom ekstra
//             'dipa'    => Dipa::select(['id', 'id_filename', 'id_perubahan', 'id_tglupload', 'id_pagu', 'id_gakyankum', 'id_dukman'])
//                                 ->where('id_satker', $idSatker)
//                                 ->where('id_periode', $tahun)
//                                 ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
//                                 ->get(),
//         ]);
//     }
public function index(Request $request)
    {
        if (!session()->has('tahun_terpilih')) return redirect()->route('pilih.tahun');

        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');
        $id_periode = ($tahun == "2024") ? "P1" : "P2";

        // 🔥 OPTIMASI: Ambil HANYA kolom yang dibutuhkan untuk menghemat RAM
        $selectCols = ['id', 'id_filename', 'id_perubahan', 'id_tglupload'];

        // Helper untuk memanggil data file dengan cepat
        $fetchLatest = fn($model, $periodeCol, $periodeVal) => 
            $model::select($selectCols)
                ->where('id_satker', $idSatker)
                ->where($periodeCol, $periodeVal)
                ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
                ->get();

        // --- TAMBAHAN UNTUK TAB PERJANJIAN KINERJA ---
        // (Pastikan Anda sudah melakukan 'use App\Models\Bidang;', dll di atas)
        $bidang = \App\Models\Bidang::all(); 
        $indikator = \App\Models\Indikator::all(); // Ambil indikator (Bisa di-filter tahunnya jika perlu)
        
        // Ambil target yang sudah diisi satker ini (Di-keyBy berdasarkan ID Indikator agar mudah dibaca oleh React)
        // Sesuaikan 'TargetPK' dengan nama model target Anda.
        $target = \App\Models\TargetPK::where('id_satker', $idSatker)
                                    ->where('target_tahun', $tahun)
                                    ->get()
                                    ->keyBy('indikator_id'); 
        // ---------------------------------------------

        return Inertia::render('Kelola/Perencanaan', [
            'tahun'   => $tahun,
            'renstra' => $fetchLatest(Renstra::class, 'id_periode', $id_periode),
            'iku'     => $fetchLatest(Iku::class, 'id_periode', $tahun),
            'renja'   => $fetchLatest(Renja::class, 'id_periode', $tahun),
            'rkakl'   => $fetchLatest(Rkakl::class, 'id_periode', $tahun),
            'renaksi' => $fetchLatest(Renaksi::class, 'id_periode', $tahun),
            'pk'      => $fetchLatest(Pk::class, 'id_periode', $tahun),
            
            // DIPA butuh kolom ekstra
            'dipa'    => Dipa::select(['id', 'id_filename', 'id_perubahan', 'id_tglupload', 'id_pagu', 'id_gakyankum', 'id_dukman'])
                                ->where('id_satker', $idSatker)
                                ->where('id_periode', $tahun)
                                ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
                                ->get(),

            // JANGAN LUPA KIRIM 3 DATA INI AGAR TAB PK BERFUNGSI:
            'bidang'    => $bidang,
            'indikator' => $indikator,
            'target'    => $target,
        ]);
    }
public function uploadFile(Request $request, $type)
    {
        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');

        // Mapping Model & Prefix
        $map = [
            'renstra' => ['model' => Renstra::class, 'prefix' => 'renstra', 'periode' => ($tahun == "2024" ? "P1" : "P2")],
            'iku'     => ['model' => Iku::class, 'prefix' => 'IKU', 'periode' => $tahun],
            'renja'   => ['model' => Renja::class, 'prefix' => 'renja', 'periode' => $tahun],
            'rkakl'   => ['model' => Rkakl::class, 'prefix' => 'rkakl', 'periode' => $tahun],
            'renaksi' => ['model' => Renaksi::class, 'prefix' => 'renaksi', 'periode' => $tahun],
            'pk'      => ['model' => Pk::class, 'prefix' => 'pk', 'periode' => $tahun],
            'dipa'    => ['model' => Dipa::class, 'prefix' => 'dipa', 'periode' => $tahun],
        ];

        if (!isset($map[$type])) return back()->with('error', 'Tipe dokumen tidak valid.');

        $config = $map[$type];
        $modelClass = $config['model'];

        // Validasi
        $rules = ["{$type}_file" => 'required|mimes:pdf|max:10240'];
        if ($type === 'dipa') {
            $rules['id_pagu'] = 'required|numeric';
            $rules['id_gakyankum'] = 'required|numeric';
            $rules['id_dukman'] = 'required|numeric';
        }
        $request->validate($rules);

        // Cek Versi Perubahan
        $latest = $modelClass::select('id_perubahan')
            ->where('id_satker', $idSatker)->where('id_periode', $config['periode'])
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')->first();
        
        $id_perubahan = $latest ? intval($latest->id_perubahan) + 1 : 0;

        // Nama & Upload Google Drive
        $file = $request->file("{$type}_file");
        $fileName = "{$config['prefix']}_{$tahun}_{$id_perubahan}.pdf";
        $folderPath = "uploads/repository/{$idSatker}";

        try {
            Storage::disk('google')->putFileAs($folderPath, $file, $fileName);

            $dataToSave = [
                'id_satker' => $idSatker,
                'id_periode' => $config['periode'],
                'id_perubahan' => $id_perubahan,
                'id_filename' => $fileName,
                'id_tglupload' => now()->format('d/m/Y h:i A'),
            ];

            if ($type === 'dipa') {
                $dataToSave['id_pagu'] = $request->id_pagu;
                $dataToSave['id_gakyankum'] = $request->id_gakyankum;
                $dataToSave['id_dukman'] = $request->id_dukman;
            }

            $modelClass::create($dataToSave);

            return redirect()->route('perencanaan')->with(['success-'.$type => "File {$type} berhasil diupload.", 'active_tab' => $type]);
        } catch (\Exception $e) {
            return back()->withErrors(["{$type}_file" => 'Gagal Upload ke Google Drive: ' . $e->getMessage()]);
        }
    }


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
}// <-- Penutup Class Controller