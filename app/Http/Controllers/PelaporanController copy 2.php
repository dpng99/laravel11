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

        // dd(session('level'));
        // $indikators = Indikator::where('link', $rumpun)->get();
        $indikators = Indikator::where('link', $rumpun)
            ->where(function ($query) use ($tahun) {
                $query->where('tahun', 'LIKE', "%$tahun%");
            })
            ->where(function ($query) use ($level) {
                if ($level == 1) {
                    $query->whereIn('lingkup', [0, 1]);
                } elseif ($level == 2) {
                    $query->whereIn('lingkup', [0, 2, 5]);
                } elseif ($level == 3) {
                    $query->whereIn('lingkup', [0, 3, 5, 6]);
                } elseif ($level == 4) {
                    $query->whereIn('lingkup', [0, 4, 6]);
                }
            })
            ->get();

        $data = [];
        foreach ($indikators as $indikator) {
            $total_ditangani = DB::table('pengukuran')
                ->where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $indikator->id)
                ->whereBetween('bulan', [$bulan_awal, $bulan_akhir])
                ->sum('ditangani');

            $total_diselesaikan = DB::table('pengukuran')
                ->where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $indikator->id)
                ->whereBetween('bulan', [$bulan_awal, $bulan_akhir])
                ->sum('diselesaikan');

            // $persentase = $total_ditangani > 0
            //     ? round(($total_diselesaikan / $total_ditangani) * 100, 2)
            //     : 0;
            $persentase = 0;
            $target_pk = DB::table('target')
                ->where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $indikator->id)
                ->value('target_tahun'); // Mengambil nilai dari kolom target_tahunan

            $target_pk = $target_pk ?? 0;
            $persentase = $persentase ?? 0;
            // $capaian_pk = $target_pk > 0
            //     ? round(($persentase / $target_pk) * 100, 2)
            //     : 0;

            // $faktor = Pengukuran::where('bulan', $bulan_awal)->value('faktor');
            // $langkah = Pengukuran::where('bulan', $bulan_awal)->value('langkah_optimalisasi');

            // $first = DB::table('pengukuran')
            //     ->where('id_satker', $id_satker)
            //     ->where('tahun', $tahun)
            //     ->where('indikator_id', $indikator->id)
            //     ->whereBetween('bulan', [$bulan_awal, $bulan_akhir])
            //     ->first();



            // $data[] = [
            //     'indikator_id' => $indikator->id,
            //     'indikator_nama' => $indikator->indikator_nama,
            //     'ditangani' => number_format($total_ditangani, 0, ',', '.'),
            //     'indikator_penghitungan' => $indikator->indikator_penghitungan,
            //     'diselesaikan' => number_format($total_diselesaikan, 0, ',', '.'),
            //     'persentase' => $persentase,
            //     'target_pk' => $target_pk,
            //     'capaian_pk' => $capaian_pk,
            //     'faktor' => $first->faktor ?? '',
            //     'langkah' => $first->langkah_optimalisasi ?? '',
            // ];

            // Cek apakah indikator_penghitungan hanya 1 kalimat atau lebih
            $labels = explode(',', $indikator->indikator_penghitungan ?? '');
            $labels = array_map('trim', $labels);

            if (count($labels) == 1) {
                // === MODE SATU KALIMAT ===
                // Ambil capaian dari bulan terakhir triwulan (3,6,9,12)
                $lastMonth = $bulan_akhir;

                $persentase = DB::table('pengukuran')
                    ->where('id_satker', $id_satker)
                    ->where('tahun', $tahun)
                    ->where('indikator_id', $indikator->id)
                    ->where('bulan', $lastMonth)
                    ->value('capaian') ?? 0;
            } 
            elseif (count($labels) > 1) {
                $rows = DB::table('pengukuran')
                    ->where('id_satker', $id_satker)
                    ->where('tahun', $tahun)
                    ->where('indikator_id', $indikator->id)
                    ->whereBetween('bulan', [1, $bulan_akhir]) // kumulatif
                    ->pluck('perhitungan');

                $pembilang = 0; // angka2
                $penyebut = 0;  // angka1

                foreach ($rows as $row) {
                    if ($row && str_contains($row, ';')) {
                        [$a, $b] = explode(';', $row);
                        $penyebut += (float) $a;
                        $pembilang += (float) $b;
                    }
                }

                $persentase = $penyebut > 0
                    ? round(($pembilang / $penyebut) * 100, 2)
                    : 0;
            }


            // === AMBIL TARGET TAHUNAN ===
            $target_pk = DB::table('target')
                ->where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $indikator->id)
                ->value('target_tahun') ?? 0;

            $capaian_pk = $target_pk > 0
                ? round(($persentase / $target_pk) * 100, 2)
                : 0;

            $first = DB::table('pengukuran')
                ->where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $indikator->id)
                ->whereBetween('bulan', [$bulan_awal, $bulan_akhir])
                ->orderBy('bulan', 'desc')
                ->first();

            $data[] = [
                'indikator_id' => $indikator->id,
                'indikator_nama' => $indikator->indikator_nama,
                'indikator_penghitungan' => $indikator->indikator_penghitungan,
                'persentase' => $persentase,
                'target_pk' => $target_pk,
                'capaian_pk' => $capaian_pk,
                'faktor' => $first->faktor ?? '',
                'langkah' => $first->langkah_optimalisasi ?? '',
            ];
            // \Log::info('Simpan Keterangan Request:', $request->all());
        }
        return response()->json($data);
    }


    public function simpanKeterangan(Request $request)
    {
        $request->validate([
            'indikator_id' => 'required|integer',
            'faktor' => 'nullable|string',
            'langkah' => 'nullable|string',
            'triwulan' => 'required|integer|min:1|max:4',
        ]);

        $bulan_akhir = ($request->triwulan - 1) * 3 + 3;

        $pengukuran = Pengukuran::where('id_satker', session('id_satker'))
            ->where('tahun', session('tahun_terpilih'))
            ->where('indikator_id', $request->indikator_id)
            ->where('bulan', $bulan_akhir)
            ->first();

        if ($pengukuran) {
            $pengukuran->faktor = $request->faktor;
            $pengukuran->langkah_optimalisasi = $request->langkah;
            $pengukuran->save();

            return response()->json(['status' => 'success']);
        } else {
            // Kirim response error
            return response()->json([
                'status' => 'error',
                'message' => 'Masih terdapat data indikator di Perjanjian Kerja Perencanaan atau di Pengukuran yang belum diisi.'
            ], 404);
        }
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
        $fileName = 'lkjip_' . $tahun . '_' . $id_perubahan . '_' . $triwulan . '.pdf';
        $file->move(public_path('uploads/repository/' . $idSatker), $fileName);

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


    // public function deleteLkjip($id)
    // {
    //     $idSatker = session('id_satker'); 
    //     $file = Lkjip::findOrFail($id);

    //     // Hapus file dari storage
    //     $filePath = public_path('uploads/repository/' . $idSatker . '/'. $file->filename);
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
            $file->move(public_path('uploads/repository/' . $idSatker), $filename);

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
