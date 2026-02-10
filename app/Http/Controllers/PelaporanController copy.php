<?php

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

class PelaporanController extends Controller
{
    public function index(Request $request)
    {
        // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
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

        return view('kelola.pelaporan', ['tahun' => $tahun, 'bidangs' => $bidangs, 'lkjipFiles' => $lkjipFiles,  'rapatStaffEkaFiles' => $rapatStaffEkaFiles,]);
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

    public function getSubIndikator($rumpun) //milik pengukuran
{
    $indikators = Indikator::where('link', $rumpun)->get();

    return response()->json($indikators);
}
    public function uploadLkjip(Request $request)
    {
        $tahun = session('tahun_terpilih');
        $request->validate([
            'lkjip_file' => 'required|mimes:pdf|max:4096', // Max 4MB
            'triwulan' => 'required|in:TW 1,TW 2,TW 3,TW 4',
        ]);

        $idSatker = session('id_satker'); // Ambil id_satker dari session
        $triwulan = $request->input('triwulan');

        // Cek id_perubahan yang sudah ada
        $latestLkjip = Lkjip::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->where('triwulan', $triwulan)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        // Tentukan id_perubahan
        $id_perubahan = $latestLkjip ? $latestLkjip->id_perubahan + 1 : 0;

        // Upload file ke folder public/uploads/lkjip
        $file = $request->file('lkjip_file');
        $fileName = $idSatker . '_lkjip_' . $tahun . '_' . $triwulan . '_' . $id_perubahan . '.pdf';
        $file->move(public_path('uploads/lkjip'), $fileName);

        // Simpan data ke database
        Lkjip::create([
            'id_satker' => $idSatker,
            'id_periode' => $tahun,
            'triwulan' => $triwulan,
            'id_perubahan' => $id_perubahan,
            'id_filename' => $fileName,
            'id_tglupload' => now()->format('d/m/Y h:i A'),

        ]);
        return redirect()->route('pelaporan')->with(['success-lkjip' => 'File LKJiP berhasil diupload.', 'active_tab' => 'lkjip']);
    }


    public function deleteLkjip($id)
    {
        $file = Lkjip::findOrFail($id);

        // Hapus file dari storage
        $filePath = public_path('uploads/lkjip/' . $file->filename);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Hapus dari database
        $file->delete();

        return redirect()->back()->with('success-lkjip', 'File berhasil dihapus.');
    }

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
            $filename = $idSatker . '_rapat_staff_eka_' . $triwulan . '_' . $tahun . '_' . $id_perubahan . '.pdf';
            $file->move(public_path('uploads/rapat_staff_eka'), $filename);

            RapatStaffEka::create([
                'id_periode' => $tahun,
                'id_satker' => $idSatker,
                'id_perubahan' => $id_perubahan,
                'id_filename' => $filename,
                'id_tglupload' => now()->format('d/m/Y h:i A'),
                // Format dengan AM/PM
                'id_triwulan' => $triwulan,
            ]);

            return redirect()->route('pelaporan')->with(['success-rastaff' => 'File Rapat Staff EKA berhasil diunggah.', 'active_tab' => 'rapat-staff-eka']);
        }

        return back()->with('error', 'Gagal mengunggah file.');
    }

    
}
