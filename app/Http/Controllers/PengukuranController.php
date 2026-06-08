<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Indikator;
use App\Models\Pengukuran;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use App\Models\Bidang;
use Illuminate\Support\Facades\Auth;
class PengukuranController extends Controller
{
    private const SHARED_USER_FIELDS = [
        'id_satker',
        'id_kejati',
        'id_kejari',
        'id_level',
        'id_hidesatker',
        'satkernama',
        'id_sakip_level',
    ];

    private function tahunTerpilih()
    {
        return session('tahun_terpilih', date('Y'));
    }

    private function applyLingkupFilter($query, $level)
    {
        if ($level == 1) {
            $query->whereIn('lingkup', [0, 1]);
        } elseif ($level == 2) {
            $query->whereIn('lingkup', [0, 2, 5, 7]);
        } elseif ($level == 3) {
            $query->whereIn('lingkup', [0, 3, 5, 6, 7]);
        } elseif ($level == 4) {
            $query->whereIn('lingkup', [0, 4, 6]);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $tahun = $this->tahunTerpilih();
        $level = session('id_sakip_level');
        $satkernama = session('satkernama') ?? '';
        
        // Logika Pengambilan Bidang (Sesuai kode asli Anda)
        $kataTerakhir = strtolower(strrchr(' ' . $satkernama, ' '));
        $bidangs = []; 

        if ($level == 0) {
            $bidangs = Bidang::whereNotNull('bidang_level')
                ->where('hide', 0)
                ->orderBy('bidang_lokasi', 'asc')
                ->orderBy('bidang_level', 'asc')
                ->get();
        } elseif ($level == 1) {
            $bidangs = Bidang::where('bidang_lokasi', $level)
                ->where('hide', 0)
                ->whereRaw("LOWER(REPLACE(bidang_nama, '_', ' ')) LIKE ?", ['%' . strtolower(trim($kataTerakhir))])
                ->whereNotNull('bidang_level')
                ->orderBy('bidang_level', 'asc')
                ->get();
        } elseif (str_starts_with(strtoupper($satkernama), 'CABJARI')) {
             $bidangs = Bidang::where('bidang_lokasi', $level)
                ->whereNotNull('bidang_level')
                ->orderBy('bidang_level', 'asc')
                ->get();
             // Logic ubah nama
             if ($bidangs->isNotEmpty() && stripos($bidangs[0]->bidang_nama, 'kepala') === 0) {
                $bidangs[0]->bidang_nama = 'Kepala Cabang Kejaksaan Negeri';
             }
        } elseif ($level > 1) {
             $bidangs = Bidang::where('bidang_lokasi', $level)
                ->whereNotNull('bidang_level')
                ->orderBy('bidang_level', 'asc')
                ->get();
        }

        // Return ke Inertia dengan props
        return Inertia::render('Kelola/Pengukuran', [
            'tahun' => $tahun,
            'bidangs' => $bidangs,
            'auth' => [
                'user' => $this->safeAuthUser(),
                'satkernama' => $satkernama
            ]
        ]);
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

    private function safeAuthUser(): ?array
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return collect(self::SHARED_USER_FIELDS)
            ->mapWithKeys(fn ($field) => [$field => $user->{$field} ?? null])
            ->all();
    }
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
        $tahun = $this->tahunTerpilih();

        $bulanMap = [
            'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4,
            'MEI' => 5, 'JUNI' => 6, 'JULI' => 7, 'AGUSTUS' => 8,
            'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
        ];

        $triwulanMap = [
            'TW1' => [3],
            'TW2' => [6],
            'TW3' => [9],
            'TW4' => [12],
        ];

        $normalizeNumber = function ($val) {
            if ($val === null || $val === '' || $val === '-') {
                return null;
            }

            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);

            return (float) $val;
        };

        foreach ($subIndikatorList as $subIndikator) {
            $indikatorId = $request->input("indikator_id.$subIndikator");
            $indikator = Indikator::find($indikatorId);

            if (!$indikator) {
                continue;
            }

            $sisaTahunLalu = $normalizeNumber($request->input("sisa_tahun_lalu.$subIndikator"));

            $pengukuranSisa = Pengukuran::firstOrNew([
                'indikator_id'  => $indikatorId,
                'id_satker'     => $id_satker,
                'tahun'         => $tahun,
                'sub_indikator' => $subIndikator,
                'bulan'         => 1,
            ]);
            $pengukuranSisa->sisa_tahun_lalu = $sisaTahunLalu;
            $pengukuranSisa->save();

            $labels = [];
            if (!empty($indikator->indikator_penghitungan)) {
                $labels = array_map('trim', explode(',', strtolower($indikator->indikator_penghitungan)));
            }
            if (empty($labels)) {
                $labels = ['ditangani', 'diselesaikan'];
            }

            foreach ($bulanMap as $bulanNama => $bulanAngka) {
                $values = [];
                foreach ($labels as $label) {
                    $values[] = $normalizeNumber($request->input("$label.$subIndikator.$bulanNama"));
                }

                $hasValue = collect($values)->contains(fn($value) => $value !== null);
                $capaian = $hasValue
                    ? implode(';', array_map(fn($v) => $v ?? '', $values))
                    : null;

                $pengukuran = Pengukuran::firstOrNew([
                    'indikator_id'  => $indikatorId,
                    'id_satker'     => $id_satker,
                    'tahun'         => $tahun,
                    'sub_indikator' => $subIndikator,
                    'bulan'         => $bulanAngka,
                ]);
                $pengukuran->perhitungan = $capaian;

                if ($bulanAngka == 1) {
                    $pengukuran->sisa_tahun_lalu = $sisaTahunLalu;
                }

                $pengukuran->save();
            }

            foreach ($triwulanMap as $tw => $bulanList) {
                $nilai = null;
                foreach ($labels as $label) {
                    $candidate = $normalizeNumber($request->input("$label.$subIndikator.$tw"));
                    if ($candidate !== null) {
                        $nilai = $candidate;
                        break;
                    }
                }

                foreach ($bulanList as $bulanAngka) {
                    $pengukuran = Pengukuran::firstOrNew([
                        'indikator_id'  => $indikatorId,
                        'id_satker'     => $id_satker,
                        'tahun'         => $tahun,
                        'sub_indikator' => $subIndikator,
                        'bulan'         => $bulanAngka,
                    ]);
                    $pengukuran->capaian = $nilai;
                    $pengukuran->save();
                }
            }
        }

        return Redirect::back()->with('success', 'Data pengukuran berhasil disimpan atau diperbarui.');
    }


    // public function updateInline(Request $request)
    // {
    //     $validated = $request->validate([
    //         'indikator_id' => 'required|integer',
    //         'sub_indikator' => 'required|string',
    //         'bulan' => 'required|integer|min:1|max:12',
    //         'tipe' => 'required|in:ditangani,diselesaikan',
    //         'nilai' => 'nullable|string',
    //     ]);

    //     $id_satker = session('id_satker');
    //     $tahun = date('Y');

    //     $pengukuran = Pengukuran::firstOrNew([
    //         'indikator_id' => $request->indikator_id,
    //         'id_satker' => $id_satker,
    //         'tahun' => $tahun,
    //         'sub_indikator' => $request->sub_indikator,
    //         'bulan' => $request->bulan,
    //     ]);

    //     $pengukuran->{$request->tipe} = $request->nilai;
    //     $pengukuran->save();

    //     return response()->json(['success' => true, 'message' => 'Data berhasil disimpan']);
    // }


    public function form($id)
    {
        $indikator = Indikator::findOrFail($id);
        return view('pengukuran.form_pengukuran', compact('indikator'));
    }

    public function getIndikatorByBidang($id_bidang)
    {
        $bidang = Bidang::find($id_bidang);
        $rumpun = $bidang?->rumpun ?? $id_bidang;

        return $this->getSubindikator($rumpun);
    }

    public function getIndikatorNama(Request $request)
    {
        $bidangId = $request->input('bidang_id') ?? $request->route('id');

        if (!$bidangId) {
            return response()->json([]);
        }

        $bidang = Bidang::find($bidangId);
        $rumpun = $bidang?->rumpun ?? $bidangId;
        $tahun = $this->tahunTerpilih();
        $level = session('id_sakip_level');

        $indikators = Indikator::query()
            ->where('link', $rumpun)
            ->where('tahun', 'LIKE', "%$tahun%");

        $this->applyLingkupFilter($indikators, $level);

        return response()->json(
            $indikators->select('id', 'indikator_nama', 'sub_indikator', 'indikator_penghitungan')->get()
        );
    }

    public function getPengukuran($indikatorId)
    {
        $idSatker = session('id_satker');
        $tahun = $this->tahunTerpilih();

        $pengukuran = \App\Models\Pengukuran::where('indikator_id', $indikatorId)
            ->where('id_satker', $idSatker)
            ->where('tahun', $tahun)
            ->get(['sub_indikator', 'bulan', 'perhitungan', 'sisa_tahun_lalu', 'capaian']);

        return response()->json($pengukuran);
    }

    public function getSubindikator($rumpun)
    {
        $tahun = $this->tahunTerpilih();
        $level = session('id_sakip_level');

        $indikators = Indikator::query()
            ->where('link', $rumpun)
            ->where('tahun', 'LIKE', "%$tahun%");

        $this->applyLingkupFilter($indikators, $level);

        $indikators = $indikators->get();

        return response()->json($indikators);
    }

    public function getDataByBidangAndSubIndikator($id_bidang, $sub_indikator)
    {
        $bidang = Bidang::find($id_bidang);
        $rumpun = $bidang?->rumpun ?? $id_bidang;
        $indikatorIds = Indikator::where('link', $rumpun)->pluck('id');

        $data = Pengukuran::whereIn('indikator_id', $indikatorIds)
            ->where('id_satker', session('id_satker'))
            ->where('tahun', $this->tahunTerpilih())
            ->where('sub_indikator', $sub_indikator)
            ->orderBy('bulan')
            ->get();

        return response()->json($data);
    }

    public function updateInline(Request $request)
    {
        $validated = $request->validate([
            'indikator_id' => 'required|integer',
            'sub_indikator' => 'required|string',
            'bulan' => 'required|integer|min:1|max:12',
            'field' => 'required|string|in:perhitungan,capaian,sisa_tahun_lalu,faktor,langkah_optimalisasi',
            'value' => 'nullable',
        ]);

        $pengukuran = Pengukuran::firstOrNew([
            'indikator_id' => $validated['indikator_id'],
            'id_satker' => session('id_satker'),
            'tahun' => $this->tahunTerpilih(),
            'sub_indikator' => $validated['sub_indikator'],
            'bulan' => $validated['bulan'],
        ]);

        $pengukuran->{$validated['field']} = $validated['value'];
        $pengukuran->save();

        return response()->json(['success' => true]);
    }

    public function updateBulanan(Request $request)
    {
        return $this->updateInline($request);
    }
    
}

