<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TahunController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KepController;
use App\Http\Controllers\SakipvalidasiController;
use App\Http\Controllers\SakipwilController;
use App\Http\Controllers\EvaluasiController;
use App\Http\Controllers\PelaporanController;
use App\Http\Controllers\PerencanaanController;
use App\Http\Controllers\PengukuranController;
use App\Http\Controllers\KepatuhanController;
use App\Http\Controllers\ChatsupportController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\AturanController;
use App\Http\Controllers\LiterasiController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\KeloladataController;
use App\Http\Controllers\UbahpasswordController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\DataLke;
use App\Http\Controllers\EvaluasiControllerNew;
use App\Http\Controllers\KriteriaController;
use App\Http\Controllers\Indikator2025Controller;
use App\Http\Controllers\LkeWas;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\DownloaderFile;
use App\Http\Controllers\GoogleDriveController;
use App\Service\GoogleService;
/*
|--------------------------------------------------------------------------
| Rute Publik (Tidak Perlu Login)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Redirect::route('login');
});

Route::controller(LoginController::class)->group(function () {
    Route::get('/login-auto', 'showLoginForm')->name('login');
    Route::post('/login-auto', 'login');
    Route::post('logout', 'logout')->name('logout');
});

/*
|--------------------------------------------------------------------------
| Rute Terproteksi (Wajib Login)
|--------------------------------------------------------------------------
| Semua rute ini akan menggunakan controller Blade Anda (return view())
*/
Route::middleware(['auth'])->group(function () {

    // === Pemilihan Tahun ===
    Route::controller(TahunController::class)->group(function () {
        Route::get('/pilih-tahun', 'showTahunForm')->name('pilih.tahun');
        Route::post('/pilih-tahun', 'pilihTahun')->name('pilih_tahun');
        Route::post('/pilih2-tahun', 'setTahun')->name('set.tahun');
        Route::post('/set-bulan', 'setBulan')->name('set.bulan');
    });

    // === Dashboard & KEP ===
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::controller(KepController::class)->group(function () {
        Route::get('/kep', 'index')->name('kep');
        Route::post('kep', 'store')->name('kep.store');
    });

    // === Perencanaan ===
    Route::controller(PerencanaanController::class)->group(function () {
        Route::get('/perencanaan', 'index')->name('perencanaan');
        Route::get('/perencanaan/indikator', 'showIndikator')->name('perencanaan.indikator');
        Route::post('/perencanaan/store', 'store')->name('perencanaan.store');
        Route::post('/target/store', 'storetarget')->name('target.store');
        Route::get('/perencanaan/pk/export-csv', 'exportPkCsv')->name('perencanaan.pk.export-csv');
        Route::post('/perencanaan/pk/import-csv', 'importPkCsv')->name('perencanaan.pk.import-csv');
        Route::get('/perencanaan/pk/export-word', 'exportPkWord')->name('perencanaan.pk.export-word');
        Route::post('/perencanaan/update/{type}/{id}', 'updateFile')->name('perencanaan.update');
        Route::delete('/perencanaan/delete/{type}/{id}', 'deleteFile')->name('perencanaan.delete');
        Route::post('/perencanaan/upload/{type}', [PerencanaanController::class, 'uploadFile'])->name('perencanaan.upload');
    });

    // === Pengukuran ===
    Route::controller(PengukuranController::class)->group(function () {
        Route::get('/pengukuran', 'index')->name('pengukuran');
        Route::get('/pengukuran/form/{id}', 'form')->name('pengukuran.form');
        Route::get('/pengukuran/indikator/{id_bidang}', 'getIndikatorByBidang')->name('pengukuran.getIndikatorByBidang');
        Route::get('/pengukuran/indikator-nama', 'getIndikatorNama')->name('pengukuran.getIndikatorNama');
        Route::get('/get-indikator/{id_bidang}', 'getIndikatorByBidang');
        Route::get('/pengukuran/{id_bidang}/{sub_indikator}', 'getDataByBidangAndSubIndikator')->name('pengukuran.getDataByBidangAndSubIndikator');
        Route::get('/get-pengukuran/{indikator_id}', 'getPengukuran')->name('pengukuran.getPengukuran');
        Route::post('/simpan-pengukuran', 'store')->name('pengukuran.store');
        Route::post('/pengukuran/update-inline', 'updateInline')->name('pengukuran.updateInline');
        Route::post('/pengukuran/update-bulanan', 'updateBulanan')->name('pengukuran.updateBulanan');
        Route::get('/get-subindikator-by-id/{id}', 'getIndikatorNama');
    });

    // === Pelaporan ===
    Route::controller(PelaporanController::class)->group(function () {
        Route::get('/pelaporan', 'index')->name('pelaporan');
        Route::post('/upload/lkjip', 'uploadLkjip')->name('upload.lkjip');
        Route::delete('/delete/lkjip/{id}', 'deleteLkjip')->name('delete.lkjip');
        Route::post('/upload/rapat-staff-eka', 'uploadRapatStaffEka')->name('upload.rapat_staff_eka');
        Route::get('/get-subindikator/{rumpun}', 'getSubIndikator');
        Route::get('/pelaporan/subindikator/{rumpun}', 'getSubIndikator2');
        Route::post('/pelaporan/simpan-keterangan', 'simpanKeterangan')->name('pelaporan.simpan_keterangan');
        Route::post('/pelaporan/update/{type}/{id}', 'updateFile')->name('pelaporan.update');
        Route::delete('/pelaporan/delete/{type}/{id}', 'deleteFile')->name('pelaporan.delete');
    });

    // === Evaluasi ===
    Route::controller(EvaluasiControllerNew::class)->group(function () {
        Route::get('/evaluasi', 'index')->name('evaluasi');
        Route::post('/upload/lhe-akip', 'uploadLheAkip')->name('upload.lhe_akip');
        Route::post('/upload/tl-lhe-akip', 'uploadTlLheAkip')->name('upload.tl_lhe_akip');
        Route::post('/upload/monev-renaksi', 'uploadMonevRenaksi')->name('upload.monev_renaksi');
        Route::post('/dokumen/verifikasi', 'verifikasi')->name('verifikasi.dokumen');
        Route::post('/dokumen/upload', 'upload')->name('upload.dokumen');
    });

    // === Monitoring ===
    Route::controller(MonitoringController::class)->group(function () {
        Route::get('/monitoring', 'index')->name('monitoring');
        Route::get('/monitoring/search-satker', 'searchSatker')->name('monitoring.searchSatker');
        Route::get('/monitoring/bidang/{idSatker}', 'getBidang')->name('monitoring.getBidang');
        Route::get('/monitoring/subindikator/{rumpun}/{id_satker}', 'getSubIndikator')->name('monitoring.subindikator');
        Route::get('/monitoring/subindikator2/{rumpun}', 'getSubIndikator2');
        Route::get('/monitoring/capaian-saspro-all', 'capaianSasproAll')->name('capaian.saspro.all');
        Route::get('/monitoring/capaian-saspro-per-kejati', 'capaianSasproPerKejati')->name('capaian.saspro.perkejati');
    });

    // === Data LKE (Dikomentari sesuai file asli) ===
    // Route::get('/evaluasi-akip', [DataLke::class, 'index'])->name('dataLke');
    // Route::get('/upload/bukti-dukung', [DataLke::class, 'showUploadForm'])->name('upload_buktidukung');
    // Route::post('/upload/bukti-dukung', [DataLke::class, 'upload'])->name('upload.store');
    // Route::get('/upload/files/{id}', [DataLke::class, 'getUploadedFiles'])->name('upload.files');
    // Route::get('/cekbdeval-lke/{kode}', [DataLke::class, 'cekBuktiDukung'])->name('cekbdeval_lke');

    // === Kelola Data ===
    Route::controller(KeloladataController::class)->group(function () {
        Route::get('/keloladata', 'index')->name('keloladata');
        // PERBAIKAN: Mengganti nama rute duplikat 'indikator.store'
        Route::post('/keloladata/indikator', 'indikator')->name('keloladata.indikator.store'); // Nama diubah
        Route::post('/keloladata/bidang', 'bidang')->name('bidang.store');
        Route::post('/keloladata/saspro', 'saspro')->name('saspro.store');
        Route::post('/keloladata/storeOrUpdateBidang', 'storeOrUpdateBidang')->name('bidang.storeOrUpdateBidang');
        Route::get('/keloladata/edit/{id}', 'edit')->name('bidang.edit');
        Route::delete('/keloladata/destroy/{id}', 'destroy')->name('bidang.destroy');
        Route::post('/keloladata/update/{id}', 'sasproUpdate')->name('saspro.update');
        Route::delete('/keloladata/delete/{id}', 'destroySaspro')->name('saspro.destroy');
        Route::post('/indikator/store', 'storeIndikator')->name('indikator.store'); // Nama ini dipertahankan
        Route::post('/indikator/delete/{id}', 'deleteIndikator')->name('indikator.delete');
        Route::post('/indikator/update/{id}', 'updateIndikator')->name('indikator.update');
    });
    // Halaman Admin Download ZIP
    Route::get('/admin/download-dokumen', [DownloaderFile::class, 'index'])
        ->name('admin.download.index');

    // Action Download ZIP (sudah ada sebelumnya)
    Route::get('/download/kejati/{id_kejati}', [DownloaderFile::class, 'downloadKejati'])
        ->name('download.kejati');
    // === Kriteria & LKE WAS ===
    Route::controller(KriteriaController::class)->group(function () {
        Route::get('/input-kriteria', 'create')->name('kriteria.create');
        Route::post('/input-kriteria', 'store')->name('kriteria.store');
        Route::get('/get-subkomponen/{id}', 'getSubkomponen');
    });

    Route::controller(LkeWas::class)->group(function () {
        Route::get('/was-lke', 'index')->name('lke_was');
        Route::get('/was-lke/{id_satker}', 'listBuktiDukung')->name('lke_was_list');
    });

    // === Fitur Lain (Bantuan & Pengaturan) ===
    Route::get('pengumuman', [PengumumanController::class, 'index'])->name('pengumuman');
    Route::post('pengumuman/store', [PengumumanController::class, 'store'])->name('pengumuman.store');
    Route::get('pengumuman/edit/{id}', [PengumumanController::class, 'edit'])->name('pengumuman.edit');
    Route::post('pengumuman/update/{id}', [PengumumanController::class, 'update'])->name('pengumuman.update');
    Route::get('/aturan', [AturanController::class, 'index'])->name('aturan');
    Route::get('/aturan/create', [AturanController::class, 'create'])->name('aturan.create');
    Route::post('/aturan', [AturanController::class, 'store'])->name('aturan.store');
    Route::get('/aturan/{id}/edit', [AturanController::class, 'edit'])->name('aturan.edit');
    Route::put('/aturan/{id}', [AturanController::class, 'update'])->name('aturan.update');
    Route::delete('/aturan/{id}', [AturanController::class, 'destroy'])->name('aturan.destroy');
    Route::get('/aturan/edit/{id}', [AturanController::class, 'edit']);
    Route::post('/aturan/update/{id}', [AturanController::class, 'update']);
    Route::get('/sakipwil', [SakipwilController::class, 'index'])->name('sakipwil');
    Route::get('/file/view/{satker}/{filename}', [SakipwilController::class, 'viewFile'])
    ->name('file.view');
    Route::get('/sakipvalidasi', [SakipvalidasiController::class, 'index'])->name('sakipvalidasi');
    Route::get('/chatsupport', [ChatsupportController::class, 'index'])->name('chatsupport');
    Route::get('/literasi', [LiterasiController::class, 'index'])->name('literasi');
    Route::get('/faq', [FaqController::class, 'index'])->name('faq');
    
    Route::controller(UbahpasswordController::class)->group(function () {   
        Route::get('/ubahpassword', 'index')->name('ubahpassword');
        Route::put('/password/update', 'updatePassword')->name('password.update');
    });
        

        Route::get('/indikator-view', fn () => redirect()->route('keloladata', ['tab' => 'sastra-saspro']))->name('indikator.view');
    // === Indikator 2025 (Dikomentari sesuai file asli) ===
    // Route::get('/indikator2025', [Indikator2025Controller::class, 'index'])->name('indikator2025.index');
    // Route::post('/pengukuran2025/store', [Indikator2025Controller::class, 'store'])->name('pengukuran2025.store');


Route::get('/upload-drive', [GoogleDriveController::class, 'upload']);

Route::get('/cek-gdrive', function () {
    $log = [];
    $log[] = "🔍 MEMULAI DIAGNOSTIK GOOGLE DRIVE...";

    try {
        // TAHAP 1: Cek Konfigurasi
        $log[] = "⏳ 1. Mengecek konfigurasi di .env & config...";
        if (!config('google.refresh_token') || !config('filesystems.disks.google.folder_id')) {
            throw new \Exception("Kredensial (Refresh Token) atau Folder ID belum lengkap!");
        }
        $log[] = "✅ Tahap 1 Lulus: Konfigurasi aman.";

        // TAHAP 2: Cek Mesin GoogleService
        $log[] = "⏳ 2. Mengetes mesin GoogleService (Meminta Token Segar)...";
       $client = app(\App\Services\GoogleService::class)->getClient();
        $token = $client->fetchAccessTokenWithRefreshToken();
        if (isset($token['error'])) {
            throw new \Exception("Token Error: " . ($token['error_description'] ?? $token['error']));
        }
        $log[] = "✅ Tahap 2 Lulus: Berhasil terhubung ke Google API.";

        // TAHAP 3: Cek Akses Baca (Read)
        $log[] = "⏳ 3. Mengetes akses baca (Melihat isi folder)...";
        $files = \Illuminate\Support\Facades\Storage::disk('google')->files('/');
        $log[] = "✅ Tahap 3 Lulus: Berhasil membaca folder (Ditemukan " . count($files) . " file).";

        // TAHAP 4: Cek Akses Tulis (Write)
        $log[] = "⏳ 4. Mengetes akses tulis (Mengupload file percobaan)...";
        $testFileName = 'test_koneksi_' . time() . '.txt';
        $upload = \Illuminate\Support\Facades\Storage::disk('google')->put($testFileName, 'Ini adalah file uji coba diagnostik otomatis.');
        if (!$upload) {
            throw new \Exception("Gagal mengupload file ke Google Drive.");
        }
        $log[] = "✅ Tahap 4 Lulus: Berhasil mengupload file '$testFileName'.";

        // TAHAP 5: Cek Akses Hapus (Delete)
        $log[] = "⏳ 5. Mengetes akses hapus (Membersihkan file percobaan)...";
        $delete = \Illuminate\Support\Facades\Storage::disk('google')->delete($testFileName);
        if (!$delete) {
            throw new \Exception("Berhasil upload, tapi gagal menghapus file. Cek permission folder.");
        }
        $log[] = "✅ Tahap 5 Lulus: Berhasil menghapus file percobaan.";

        $log[] = "KESIMPULAN: jalan";

        return response()->json([
            'status' => 'jalan',
            'catatan_sistem' => $log
        ], 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        $log[] = "ERROR : " . $e->getMessage();
        $log[] = "KESIMPULAN: Sistem Google Drive mengalami kendala. Silakan cek pesan error di atas.";
        
        return response()->json([
            'status' => 'ADA_MASALAH',
            'catatan_sistem' => $log
        ], 500, [], JSON_PRETTY_PRINT);
    }
});

});
