<?php
//up
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Indikator;
use App\Models\Pengukuran;

class PengukuranController extends Controller
{
    public function index(Request $request)
    {
        // $id_bidang = $request->get('id_bidang');
        $tahun = session('tahun_terpilih');
        $id_satker = session('id_satker');

        $indikators = [];
        $data = [];

        // if ($id_bidang) {
        //     $indikators = Indikator::where('id_bidang', $id_bidang)->get();

        //     foreach ($indikators as $indikator) {
        //         $subIndikators = explode(',', $indikator->sub_indikator);
        //         $pengukuranData = Pengukuran::where('indikator_id', $indikator->id)
        //             ->where('id_satker', $id_satker)
        //             ->where('tahun', $tahun)
        //             ->get();

        //         $data[$indikator->id] = [
        //             'nama' => $indikator->indikator_nama,
        //             'sub' => []
        //         ];

        //         foreach ($subIndikators as $sub) {
        //             $sub = trim($sub);
        //             $data[$indikator->id]['sub'][$sub] = $pengukuranData
        //                 ->where('sub_indikator', $sub)
        //                 ->where('id_satker', $id_satker)
        //                 ->keyBy('bulan'); // <--- agar mudah akses berdasarkan bulan
        //         }
        //     }
        // }

        return view('kelola.pengukuran', compact('data', 'indikators', 'tahun'));
    }

//     public function getIndikatorNama(Request $request)
//     {
//         $bidangId = $request->input('bidang_id');

//         try {
//             $indikators = Indikator::where('id_bidang', $bidangId)
//                 ->select('id', 'indikator_nama')
//                 ->get();

//             return response()->json($indikators);
//         } catch (\Exception $e) {
//             \Log::error('Gagal ambil indikator: ' . $e->getMessage());
//             return response()->json(['error' => 'Gagal mengambil data'], 500);
//         }
//     }
//  public function getDataByBidangAndSubIndikator($id_bidang, $subIndikator)
//     {
//         $data = Pengukuran::whereHas('indikator', function ($query) use ($id_bidang) {
//             $query->where('id_bidang', $id_bidang);
//         })->where('sub_indikator', $subIndikator)
//             ->select('bulan', 'ditangani', 'diselesaikan')
//             ->get();

//         return response()->json($data);
//     }

    public function store(Request $request)
    {
        $subIndikatorList = $request->input('sub_indikator_list');

        if (!is_array($subIndikatorList)) {
            return redirect()->back()->withErrors('Tidak ada data yang dikirim.');
        }

        $id_satker = session('id_satker');
        $tahun = session('tahun_terpilih');

        $bulanMap = [
            'JANUARI' => 1,
            'FEBRUARI' => 2,
            'MARET' => 3,
            'APRIL' => 4,
            'MEI' => 5,
            'JUNI' => 6,
            'JULI' => 7,
            'AGUSTUS' => 8,
            'SEPTEMBER' => 9,
            'OKTOBER' => 10,
            'NOVEMBER' => 11,
            'DESEMBER' => 12,
        ];
        $triwulanMap = [
            'TW1' => [3],
            'TW2' => [6],
            'TW3' => [9],
            'TW4' => [12],
        ];

        foreach ($subIndikatorList as $subIndikator) {
            $indikatorId = $request->input("indikator_id.$subIndikator");
            // === MODE BULANAN ===
            $ditanganiArray = $request->input("ditangani.$subIndikator", []);
            $diselesaikanArray = $request->input("diselesaikan.$subIndikator", []);

             // === Ambil data indikator dulu ===
    $indikator = Indikator::find($indikatorId);

            // === MODE TRIWULAN === (nama label dinamis, misalnya 'jumlah' atau 'realisasi')
            // cek semua input request apakah ada selain ditangani/diselesaikan
            $allInputs = $request->all();
            $customLabels = array_diff(array_keys($allInputs), [
                "_token",
                "sub_indikator_list",
                "indikator_id",
                "sisa_tahun_lalu",
                "ditangani",
                "diselesaikan"
            ]);

            // === Simpan Sisa Tahun Lalu (hanya di bulan Januari) ===
            $sisaTahunLalu = $request->input("sisa_tahun_lalu.$subIndikator");
            $sisaTahunLalu = $sisaTahunLalu ? str_replace('.', '', str_replace(',', '.', $sisaTahunLalu)) : null;

            // Simpan sisa_tahun_lalu hanya sekali di bulan Januari
            if (!is_null($sisaTahunLalu)) {
                $pengukuran = \App\Models\Pengukuran::firstOrNew([
                    'indikator_id' => $indikatorId,
                    'id_satker' => $id_satker,
                    'tahun' => $tahun,
                    'sub_indikator' => $subIndikator,
                    'bulan' => 1, // Januari
                ]);

                $pengukuran->sisa_tahun_lalu = $sisaTahunLalu;
                $pengukuran->save();
            }
            // === Proses Bulanan ===
            // Ambil label dari indikator
            $labels = [];
            if (!empty($indikator->indikator_penghitungan)) {
                $labels = array_map('trim', explode(',', strtolower($indikator->indikator_penghitungan)));
            }
            // Default kalau kosong → ['ditangani','diselesaikan']
            if (empty($labels)) {
                $labels = ['ditangani', 'diselesaikan'];
            }

            foreach ($bulanMap as $bulanNama => $bulanAngka) {
                $values = [];

                // ambil nilai sesuai labels
                foreach ($labels as $label) {
                    $val = $request->input("$label.$subIndikator.$bulanNama", null);

                    // kalau string angka ribuan → normalisasi
                    if (!is_null($val)) {
                        $val = str_replace('.', '', str_replace(',', '.', $val));
                    }
                    $values[] = $val ?? '';
                }

                // kalau semua kosong → skip
                if (count(array_filter($values, fn($v) => $v !== '')) === 0) {
                    continue;
                }

                // gabungkan dengan ; (misal: "32;15" atau "100" kalau 1 label)
                $capaian = implode(';', $values);

                $pengukuran = \App\Models\Pengukuran::firstOrNew([
                    'indikator_id'  => $indikatorId,
                    'id_satker'     => $id_satker,
                    'tahun'         => $tahun,
                    'sub_indikator' => $subIndikator,
                    'bulan'         => $bulanAngka,
                ]);

                $pengukuran->perhitungan = $capaian;

                // khusus januari → tambahkan sisa_tahun_lalu kalau ada
                if ($bulanAngka == 1) {
                    $pengukuran->sisa_tahun_lalu = $request->input("sisa_tahun_lalu.$subIndikator", null);
                }

                $pengukuran->save();
            }

            // dd($request->all());
            // === Proses Triwulan ===
            foreach ($customLabels as $labelKey) {
                $capaianArray = $request->input("$labelKey.$subIndikator", []);
                foreach ($triwulanMap as $tw => $bulanList) {
                    $nilai = $capaianArray[$tw] ?? null;
                    if (is_null($nilai)) continue;

                    $nilai = str_replace('.', '', str_replace(',', '.', $nilai));

                    foreach ($bulanList as $bulanAngka) {
                $pengukuran = \App\Models\Pengukuran::firstOrNew([
                    'indikator_id' => $indikatorId,
                    'id_satker' => $id_satker,
                    'tahun' => $tahun,
                    'sub_indikator' => $subIndikator,
                    'bulan' => $bulanAngka,
                ]);

                                      $pengukuran->capaian = $nilai; // simpan sesuai nama kolom di DB
                        $pengukuran->save();
                    }
                }
            }
        }
        // dd($request->all());


        return redirect()->back()->with('success', 'Data pengukuran berhasil disimpan atau diperbarui.');
    }

    public function updateInline(Request $request)
    {
        $validated = $request->validate([
            'indikator_id' => 'required|integer',
            'sub_indikator' => 'required|string',
            'bulan' => 'required|integer|min:1|max:12',
            'tipe' => 'required|in:ditangani,diselesaikan',
            'nilai' => 'nullable|string',
        ]);

        $id_satker = session('id_satker');
        $tahun = date('Y');

        $pengukuran = Pengukuran::firstOrNew([
            'indikator_id' => $request->indikator_id,
            'id_satker' => $id_satker,
            'tahun' => $tahun,
            'sub_indikator' => $request->sub_indikator,
            'bulan' => $request->bulan,
        ]);

        $pengukuran->{$request->tipe} = $request->nilai;
        $pengukuran->save();

        return response()->json(['success' => true, 'message' => 'Data berhasil disimpan']);
    }


    public function form($id)
    {
        $indikator = Indikator::findOrFail($id);
        return view('pengukuran.form_pengukuran', compact('indikator'));
    }

    public function getPengukuran($indikator_id)
    {
        $id_satker = session('id_satker');
        $data = Pengukuran::where('indikator_id', $indikator_id)->where('id_satker', $id_satker)->get([
            'sub_indikator',
            'bulan',
            'capaian',
            'perhitungan',
            'ditangani',
            'diselesaikan',
            'sisa_tahun_lalu'
        ]);

        return response()->json($data);
    }

    public function getSubindikator($rumpun)
{
    $tahun = date('Y');
    $level = session('id_sakip_level');

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

    return response()->json($indikators);
}

}
