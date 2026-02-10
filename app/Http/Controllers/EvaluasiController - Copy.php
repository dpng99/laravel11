<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\LheAkip;
use App\Models\MonevRenaksi;
use App\Models\TlLheAkip;

class EvaluasiController extends Controller
{
    public function index()
    {
        // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }
        // Ambil tahun yang dipilih dari session
        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');

        $lheAkipFiles = LheAkip::orderBy('id_tglupload', 'desc')->get();
        $lheAkipFiles = LheAkip::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy('id_tglupload', 'desc')
            ->get();
        $tlLheAkipFiles = TLLheAkip::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy('id_tglupload', 'desc')
            ->get();
        $monevRenaksiFiles = MonevRenaksi::where('id_periode', $tahun)
            ->where('id_satker', $idSatker)
            ->orderBy('id_perubahan', 'desc')
            ->get();

        // === Ambil dokumen bukti dukung (group by kriteria) ===
        $buktiDukung = DB::table('bukti_dukung')
            ->where('id_satker', $idSatker)
            ->get()
            ->groupBy('id_kriteria');

        // === Ambil semua kriteria yang punya format_nama_file ===
        $kriteria = DB::table('kriteria')->get();

        foreach ($kriteria as $row) {
            if (!$row->format_nama_file) continue;

            $formats = array_map('trim', explode(';', $row->format_nama_file));

            foreach ($formats as $format) {
                $regex = '/^' . str_replace('_', '.*', preg_quote($format, '/')) . '/i';

                $dokumenList = DB::table('bukti_dukung')
                    ->where('id_satker', $idSatker)
                    ->where('id_kriteria', $row->id)
                    ->get()
                    ->filter(fn($d) => preg_match($regex, $d->link_bukti_dukung));

                if ($dokumenList->isNotEmpty()) {
                    if (!isset($buktiDukung[$row->id])) {
                        $buktiDukung[$row->id] = collect();
                    }
                    foreach ($dokumenList as $dok) {
                        $buktiDukung[$row->id]->push($dok);
                    }
                }
            }
        }

        $komponen = DB::table('komponen')
            ->leftJoin('sub_komponen', 'komponen.id', '=', 'sub_komponen.id_komponen')
            ->leftJoin('kriteria', 'sub_komponen.id_subkomponen', '=', 'kriteria.id_sub_komponen')
            ->select(
                'komponen.id as id_komponen',
                'komponen.nama_komponen',
                'sub_komponen.id_subkomponen',
                'sub_komponen.nama_subkomponen',
                'kriteria.id as id_kriteria',
                'kriteria.nama_kriteria',
                'kriteria.bukti_pengisian',
                'kriteria.format_nama_file' // ini penting biar dipakai untuk nama file
            )
            ->orderBy('komponen.id')
            ->orderBy('sub_komponen.id_subkomponen')
            ->orderBy('kriteria.id')
            ->get();


        // Tambahkan info jumlah kebutuhan vs realisasi
        $komponen->transform(function ($item) use ($buktiDukung) {
            // pecah kebutuhan dari kriteria
            $kebutuhan = $item->bukti_pengisian
                ? array_map('trim', explode(';', $item->bukti_pengisian))
                : [];

            // dokumen realisasi
            $realisasi = isset($buktiDukung[$item->id_kriteria])
                ? $buktiDukung[$item->id_kriteria]
                : collect();

            $item->jumlah_kebutuhan = count($kebutuhan);
            $item->jumlah_realisasi = $realisasi->count();
            $item->persen_terpenuhi = $item->jumlah_kebutuhan > 0
                ? round(($item->jumlah_realisasi / $item->jumlah_kebutuhan) * 100, 2)
                : 0;

            return $item;
        });
        return view('kelola.evaluasi', ['tahun' => $tahun, 'lheAkipFiles' => $lheAkipFiles, 'monevRenaksiFiles' => $monevRenaksiFiles, 'tlLheAkipFiles' => $tlLheAkipFiles, 'komponen' => $komponen, 'idSatker' => $idSatker, 'buktiDukung' => $buktiDukung]);
    }

    // 📌 Upload LHE AKIP
    public function uploadLheAkip(Request $request)
    {
        $request->validate([
            'lhe_akip_file' => 'required|mimes:pdf|max:4096',
        ]);
        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');

        // Cek id_perubahan yang sudah ada
        $latestLhe = LheAkip::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        // Tentukan id_perubahan
        $id_perubahan = $latestLhe ? $latestLhe->id_perubahan + 1 : 0;

        // Upload file ke folder public/uploads/lkjip
        $file = $request->file('lhe_akip_file');
        $fileName = 'lhe_' . $tahun . '_' . $id_perubahan . '.pdf';
        $file->move(base_path('uploads/repository/' . $idSatker), $fileName);

        LheAkip::create([
            'id_periode' => $tahun,
            'id_satker' => $idSatker,
            'id_perubahan' => $id_perubahan,
            'id_filename' => $fileName,
            'id_tglupload' => now()->format('d/m/Y h:i A'),

        ]);

        return redirect()->route('evaluasi')->with(['success-lhe' => 'LHE AKIP berhasil diupload!', 'active_tab' => 'lhe-akip']);
    }

    public function uploadTlLheAkip(Request $request)
    {
        $request->validate([
            'tl_lhe_akip_file' => 'required|mimes:pdf|max:4096',
        ]);

        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');

        // Cek versi terbaru
        $latestFile = TlLheAkip::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        $id_perubahan = $latestFile ? $latestFile->id_perubahan + 1 : 0;

        if ($request->hasFile('tl_lhe_akip_file')) {
            $file = $request->file('tl_lhe_akip_file');
            $filename = 'tl_lhe_akip_' . $tahun . '_' . $id_perubahan . '.pdf';
            $file->move(base_path('uploads/repository/' . $idSatker), $filename);

            TlLheAkip::create([
                'id_periode' => $tahun,
                'id_satker' => $idSatker,
                'id_perubahan' => $id_perubahan,
                'id_filename' => $filename,
                'id_tglupload' => now()->format('d/m/Y h:i A'),

            ]);

            return redirect()->route('evaluasi')->with(['success-tllhe' => 'File TL LHE AKIP berhasil diunggah.', 'active_tab' => 'tl-lhe-akip']);
        }

        return back()->with('error', 'Gagal mengunggah file.');
    }

    public function uploadMonevRenaksi(Request $request)
    {
        $request->validate([
            'id_triwulan' => 'required|in:TW 1,TW 2,TW 3,TW 4',
            'monev_file' => 'required|mimes:pdf|max:4096',
        ]);

        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');
        $id_triwulan = $request->input('id_triwulan');

        // Ambil versi terakhir untuk triwulan tersebut
        $latestmonev = MonevRenaksi::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->where('id_triwulan', $id_triwulan)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        $id_perubahan = $latestmonev ? $latestmonev->id_perubahan + 1 : 0;

        if ($request->hasFile('monev_file')) {
            $file = $request->file('monev_file');

            // Pastikan folder ada
            $destination = base_path('uploads/repository/' . $idSatker);
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }

            $filename = 'renaksieval_' . $tahun . '_' . $id_perubahan . '_' . $id_triwulan . '.pdf';
            $file->move($destination, $filename);

            // Simpan data ke database
            MonevRenaksi::create([
                'id_periode' => $tahun,
                'id_satker' => $idSatker,
                'id_perubahan' => $id_perubahan,
                'id_filename' => $filename,
                'id_tglupload' => now()->format('d/m/Y h:i A'),
                'id_triwulan' => $id_triwulan,
            ]);

            return redirect()->route('evaluasi')
                ->with([
                    'success-monev' => 'File Monev Renaksi berhasil diunggah.',
                    'active_tab' => 'monev-renaksi'
                ]);
        }

        return back()->with('error', 'Gagal mengunggah file.');
    }

    
   public function verifikasi(Request $request)
{
    $idKriteria    = $request->input('id_kriteria');
    $idSatker      = session('id_satker');
    $idKomponen    = $request->input('id_komponen');
    $idSubKomponen = $request->input('id_subkomponen'); // samakan naming
    $tahun         = session('tahun_terpilih');

    $kriteria = DB::table('kriteria')->where('id', $idKriteria)->first();
    if (!$kriteria) {
        return response()->json(['success' => false, 'message' => 'Kriteria tidak ditemukan.']);
    }

    $formats = array_filter(array_map('trim', explode(';', $kriteria->format_nama_file ?? '')));
$verifFormats = collect($formats)->filter(fn($f) => stripos($f, '(perubahan terakhir)') !== false);

    $mapTable = [
        'renstra'     => ['table' => 'sinori_sakip_renstra',    'prefix' => 'renstra'],
        'renja'       => ['table' => 'sinori_sakip_renja',      'prefix' => 'renja'],
        'iku'         => ['table' => 'sinori_sakip_iku',        'prefix' => 'IKU'],
        'pk'          => ['table' => 'pk',                      'prefix' => 'pk'],
        'renaksi'     => ['table' => 'sinori_sakip_renaksi',    'prefix' => 'renaksi'],
        'rkakl'       => ['table' => 'sinori_sakip_rkakl',      'prefix' => 'rkakl'],
        'dipa'        => ['table' => 'sinori_sakip_dipa',       'prefix' => 'dipa'],
        'lakip'       => ['table' => 'sinori_sakip_lakip',      'prefix' => 'lkjip'],
        'rastaff'     => ['table' => 'sinori_sakip_rastaff',    'prefix' => 'rastaff'],
        'renaksieval' => ['table' => 'sinori_sakip_renaksieval','prefix' => 'renaksieval'],
        'lhe'         => ['table' => 'lhe',                     'prefix' => 'lhe'],
        'tl_lhe_akip' => ['table' => 'tl_lhe_akip',             'prefix' => 'tl_lhe_akip'],
    ];

    $results = [];

    foreach ($formats as $format) {
        if (stripos($format, '(perubahan terakhir)') !== false) {
            $tipe = strtolower(strtok($format, '_'));
            if (!isset($mapTable[$tipe])) continue;

            $source = $mapTable[$tipe];

            preg_match('/\d{4}/', $format, $match);
            $tahunFormat = $match[0] ?? $tahun;

            preg_match('/TW\s*[IVX]+/i', $format, $matchPeriode);
            $periodeTambahan = $matchPeriode[0] ?? null;

            $idPeriode = ($tipe === 'renstra')
                ? (($tahunFormat == 2024) ? 'P1' : 'P2')
                : $tahunFormat;

            $dokumen = DB::table($source['table'])
                ->where('id_satker', $idSatker)
                ->where('id_periode', $idPeriode)
                ->orderByDesc('id_perubahan')
                ->first();

            if ($dokumen) {
                $namaFile = $source['prefix'] . '_' . $tahunFormat . '_' . $dokumen->id_perubahan;

                if ($periodeTambahan) {
                    $namaFile .= '_' . strtoupper($periodeTambahan);
                }
                $namaFile .= '.pdf';

                // simpan (kalau sudah ada → update tanggal, kalau belum → insert baru)
                DB::table('bukti_dukung')->updateOrInsert(
                    [
                        'id_kriteria'       => $idKriteria,
                        'id_satker'         => $idSatker,
                        'id_komponen'       => $idKomponen,
                        'id_sub_komponen'   => $idSubKomponen,
                        'link_bukti_dukung' => $namaFile,
                    ],
                    [
                 'tgl_pengisian' => now()->format('d/m/Y h:i A'),]
                );

                $results[] = $namaFile;
            }
        }
    }
  return response()->json(['success' => true]);
}


/**
 * Upload manual (untuk format tanpa "(perubahan terakhir)")
 */
public function upload(Request $request)
{
    $request->validate([
        'files.*'        => 'required|mimes:pdf|max:2048',
        'id_kriteria'    => 'required',
        'id_komponen'    => 'required',
        'id_subkomponen' => 'required',
    ]);

    $idKriteria    = $request->input('id_kriteria');
    $idSatker      = session('id_satker');
    $idKomponen    = $request->input('id_komponen');
    $idSubKomponen = $request->input('id_subkomponen');

    $kriteria = DB::table('kriteria')->where('id', $idKriteria)->first();
    if (!$kriteria) {
        return response()->json(['success' => false, 'message' => 'Kriteria tidak ditemukan.']);
    }

    $formats = array_filter(array_map('trim', explode(';', $kriteria->format_nama_file ?? '')));
    $manualFormats = collect($formats)->filter(fn($f) => stripos($f, '(perubahan terakhir)') === false);

    $tujuanPath = public_path("uploads/repository/{$idSatker}");
    if (!file_exists($tujuanPath)) {
        mkdir($tujuanPath, 0777, true);
    }

    $uploadedFiles = [];
    foreach ($request->file('files') as $index => $file) {
        $manualFormat = $manualFormats[$index] ?? 'Dokumen';
        $namaFile = str_replace(' ', '_', $manualFormat) . '.pdf';

        $file->move($tujuanPath, $namaFile);

        DB::table('bukti_dukung')->updateOrInsert(
            [
                'id_kriteria'     => $idKriteria,
                'id_satker'       => $idSatker,
                'id_komponen'     => $idKomponen,
                'id_sub_komponen' => $idSubKomponen,
                'link_bukti_dukung' => $namaFile,
            ],
            [
                'tgl_pengisian' => now()->format('d/m/Y h:i A'),
            ]
        );

        $uploadedFiles[] = asset("uploads/repository/{$idSatker}/{$namaFile}");
    }

    return back()->with('success', 'Upload berhasil.');
}

}
