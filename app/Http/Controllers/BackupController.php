<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function halamanBackupAdmin()
    {
        // 1. Pastikan hanya Admin (Level 99) yang bisa mengakses halaman ini
        if (auth()->user()->id_sakip_level != 99) {
            abort(403, 'Akses Ditolak. Halaman ini khusus Administrator.');
        }

        // 2. Ambil daftar Satker dari database
        // CATATAN: Silakan sesuaikan nama tabel 'users' atau 'master_satker' 
        // dan kolom 'nama_satker' dengan struktur database asli aplikasi Anda.
        $daftarSatker = \Illuminate\Support\Facades\DB::table('users')
                            ->select('id_satker', 'name as nama_satker') // Ubah 'name' jika kolom Anda berbeda
                            ->whereNotNull('id_satker')
                            ->distinct()
                            ->orderBy('nama_satker', 'asc')
                            ->get();

        return view('download-repo', compact('daftarSatker'));
    }
public function downloadZipSatker($id_satker)
    {
        // 1. Tentukan path folder lokal target
        $folderPath = public_path("uploads/repository/{$id_satker}");

        // 2. Cek apakah foldernya ada dan ada isinya
        if (!File::isDirectory($folderPath) || count(File::allFiles($folderPath)) === 0) {
            // Kembali ke halaman sebelumnya dengan pesan error (Blade session flash)
            return back()->with('error', "Folder repository untuk Satker {$id_satker} tidak ditemukan atau kosong.");
        }

        // 3. Tentukan nama dan lokasi sementara file ZIP
        $zipFileName = "Backup_Data_{$id_satker}_" . date('Ymd_His') . ".zip";
        
        // Simpan sementara di folder storage/app/public
        $zipFilePath = storage_path("app/public/{$zipFileName}");

        // 4. Inisialisasi proses Zipping
        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            
            // Ambil semua file di dalam folder satker tersebut
            $files = File::allFiles($folderPath);

            foreach ($files as $file) {
                // Tambahkan file ke ZIP
                // getRelativePathname() memastikan susunan sub-folder (jika ada) tetap rapi di dalam ZIP
                $zip->addFile($file->getRealPath(), $file->getRelativePathname());
            }
            
            $zip->close();
        } else {
            return back()->with('error', "Gagal membuat file ZIP. Pastikan ekstensi PHP ZipArchive aktif.");
        }

        // 5. Download file ZIP, lalu HAPUS dari server setelah selesai didownload agar hemat memori
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }
}
