<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Bidang;
use App\Models\Indikator;
use App\Models\Pengukuran;
use App\Models\sastra_indikator_view;
use Inertia\Inertia;

class MonitoringController extends Controller
{
    /**
     * Halaman Utama Monitoring
     */
    public function index(Request $request)
    {
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }

        $id_satker = session('id_satker');
        $tahun = session('tahun_terpilih');
        $level = session('id_sakip_level');
        $search = $request->get('satker');

        // Ambil info user saat ini
        $id = DB::table('sinori_login')->where('id_satker', $id_satker)->first();

        // 1. Logika Penentuan Daftar Satker Pengonfirmasi Hak Akses
        if (in_array($id_satker, [999999, 'admin', 'Pengawasan', 'Panev', 'menpanrb'])) {
            $satkers = DB::table('sinori_login')
                ->whereNotIn('id_satker', [888881, 888882, 'admin', 999999, 'Pengawasan', 'Panev', 'menpanrb'])
                ->where('id_satker', 'not like', 'was%')
                ->where('id_satker', 'not like', '00budi')
                ->where('id_kejati', 'not like', '87')
                ->orderBy('id_kejati', 'asc')
                ->orderBy('id_kejari', 'asc')
                ->get();
        } else {
            if ($id) {
                $satkers = DB::table('sinori_login')
                    ->where('id_kejati', $id->id_kejati)
                    ->where('id_satker', 'not like', 'was%')
                    ->get();
            } else {
                $satkers = collect();
            }
        }

        $selectedSatker = null;
        $bidangs = [];

        if ($search) {
            $selectedSatker = DB::table('sinori_login')
                ->where('id_satker', $search)
                ->first(['id_satker', 'satkernama', 'id_kejati', 'id_kejari', 'id_sakip_level']);

            if ($selectedSatker) {
                $level = $selectedSatker->id_sakip_level;
                $satkernama = $selectedSatker->satkernama ?? '';
                $satkernama_with_spaces = str_replace('_', ' ', $satkernama);
                $kataTerakhir = strtolower(trim(strrchr(' ' . $satkernama_with_spaces, ' ')));
                if (empty($kataTerakhir)) {
                    $kataTerakhir = strtolower($satkernama_with_spaces);
                }

                if ($level == 0) {
                    $bidangs = Bidang::whereNotNull('bidang_level')
                        ->where('hide', 0)
                        ->orderBy('bidang_lokasi', 'asc')
                        ->orderBy('bidang_level', 'asc')
                        ->get();
                } elseif ($level == 1) {
                    $bidangs = Bidang::where('bidang_lokasi', $level)
                        ->where('hide', 0)
                        ->whereRaw("LOWER(REPLACE(bidang_nama, '_', ' ')) LIKE ?", ['%' . $kataTerakhir . '%'])
                        ->whereNotNull('bidang_level')
                        ->orderBy('bidang_level', 'asc')
                        ->get();
                } elseif (str_starts_with(strtoupper($satkernama), 'CABJARI')) {
                    $bidangs = Bidang::where('bidang_lokasi', $level)
                        ->whereNotNull('bidang_level')
                        ->orderBy('bidang_level', 'asc')
                        ->get();

                    if ($bidangs->isNotEmpty() && stripos($bidangs[0]->bidang_nama, 'kepala') === 0) {
                        $bidangs[0]->bidang_nama = 'Kepala Cabang Kejaksaan Negeri';
                    }
                } elseif ($level > 1) {
                    $bidangs = Bidang::where('bidang_lokasi', $level)
                        ->whereNotNull('bidang_level')
                        ->orderBy('bidang_level', 'asc')
                        ->get();
                }
            }
        }

        return Inertia::render('Monitoring', [
            'tahun' => $tahun,
            'satkers' => $satkers,
            'search' => $search,
            'selectedSatker' => $selectedSatker,
            'bidangs' => $bidangs
        ]);
    }

    /**
     * API Ambil Bidang Berdasarkan Satker Dinamis
     */
    public function getBidang($idSatker)
    {
        $satker = DB::table('sinori_login')->where('id_satker', $idSatker)->first();

        if (!$satker) {
            return response()->json(['error' => 'Satker not found'], 404);
        }

        $level = $satker->id_sakip_level;
        $satkernama = $satker->satkernama ?? '';
        $bidangs = [];

        $satkernama_with_spaces = str_replace('_', ' ', $satkernama);
        $kataTerakhir = strtolower(trim(strrchr(' ' . $satkernama_with_spaces, ' ')));
        if (empty($kataTerakhir)) {
            $kataTerakhir = strtolower($satkernama_with_spaces);
        }

        if ($level == 1) {
            $bidangs = Bidang::where('bidang_lokasi', $level)
                ->where('hide', 0)
                ->whereRaw("LOWER(REPLACE(bidang_nama, '_', ' ')) LIKE ?", ['%' . $kataTerakhir . '%'])
                ->whereNotNull('bidang_level')
                ->orderBy('bidang_level', 'asc')
                ->get();
        } elseif (str_starts_with(strtoupper($satkernama_with_spaces), 'CABJARI')) {
            $bidangs = Bidang::where('bidang_lokasi', 4)
                ->whereNotNull('bidang_level')
                ->orderBy('bidang_level', 'asc')
                ->get();
        } elseif ($level > 1) {
            $bidangs = Bidang::where('bidang_lokasi', $level)
                ->whereNotNull('bidang_level')
                ->orderBy('bidang_level', 'asc')
                ->get();
        }

        return response()->json($bidangs);
    }

    /**
     * API Ambil Sub Indikator Monitoring (Rumpun)
     */
    public function getSubIndikator2($rumpun, Request $request)
    {
        $tahun = session('tahun_terpilih');
        $tw = $request->query('triwulan', 1);
        $bulan_awal = ($tw - 1) * 3 + 1;
        $bulan_akhir = $bulan_awal + 2;

        $id_satker = $request->query('id_satker');
        if (!$id_satker) {
            return response()->json(['error' => 'id_satker tidak ditemukan'], 400);
        }

        $satkerData = DB::table('sinori_login')
            ->where('id_satker', $id_satker)
            ->first(['id_kejati', 'id_kejari', 'satkernama', 'id_sakip_level']);

        if (!$satkerData) {
            return response()->json(['error' => 'Data satker tidak ditemukan'], 404);
        }

        $level = $satkerData->id_sakip_level;

        $indikators = Indikator::where('link', $rumpun)
            ->where('tahun', 'LIKE', "%$tahun%")
            ->where(function ($query) use ($level) {
                if ($level == 1) {
                    $query->whereIn('lingkup', [0, 1]);
                } elseif ($level == 2) {
                    $query->whereIn('lingkup', [0, 2, 5, 7]);
                } elseif ($level == 3) {
                    $query->whereIn('lingkup', [0, 3, 5, 6, 7]);
                } elseif ($level == 4) {
                    $query->whereIn('lingkup', [0, 4, 6, 7]);
                }
            })
            ->get();

        $data = [];

        foreach ($indikators as $indikator) {
            $persentase = 0;
            $labels = !empty($indikator->indikator_penghitungan)
                ? array_map('trim', explode(',', strtolower($indikator->indikator_penghitungan)))
                : ['ditangani', 'diselesaikan'];

            if (count($labels) == 1) {
                $persentase = DB::table('pengukuran')
                    ->where('id_satker', $id_satker)
                    ->where('tahun', $tahun)
                    ->where('indikator_id', $indikator->id)
                    ->where('bulan', $bulan_akhir)
                    ->orderBy('id', 'desc')
                    ->value('capaian') ?? 0;
            } elseif (count($labels) > 1) {
                $rows = DB::table('pengukuran')
                    ->where('id_satker', $id_satker)
                    ->where('tahun', $tahun)
                    ->where('indikator_id', $indikator->id)
                    ->whereBetween('bulan', [1, $bulan_akhir])
                    ->get(['sub_indikator', 'perhitungan']);

                $persentaseSub = [];

                foreach ($rows->groupBy('sub_indikator') as $subIndikator => $dataRow) {
                    $pembilang = 0; $penyebut = 0;
                    foreach ($dataRow as $row) {
                        if (!empty($row->perhitungan) && str_contains($row->perhitungan, ';')) {
                            [$a, $b] = explode(';', $row->perhitungan);
                            $penyebut += (float) $a;
                            $pembilang += (float) $b;
                        }
                    }
                    if ($penyebut > 0) {
                        $persentaseSub[] = round(($pembilang / $penyebut) * 100, 2);
                    }
                }

                $persentase = count($persentaseSub) > 0
                    ? round(array_sum($persentaseSub) / count($persentaseSub), 2)
                    : 0;
            }

            $target_pk = DB::table('target')
                ->where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $indikator->id)
                ->value('target_tahun') ?? 0;

            $capaian_pk = $target_pk > 0 ? round(($persentase / $target_pk) * 100, 2) : 0;

            $first = DB::table('pengukuran')
                ->where('id_satker', $id_satker)
                ->where('tahun', $tahun)
                ->where('indikator_id', $indikator->id)
                ->where('bulan', $bulan_akhir)
                ->where(function ($q) {
                    $q->whereNotNull('faktor')->orWhereNotNull('langkah_optimalisasi');
                })
                ->orderBy('bulan', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $data[] = [
                'indikator_id' => $indikator->id,
                'indikator_nama' => $indikator->indikator_nama,
                'indikator_penghitungan' => $indikator->indikator_penghitungan ?: 'Ditangani, Diselesaikan',
                'persentase' => $persentase,
                'target_pk' => $target_pk,
                'capaian_pk' => $capaian_pk,
                'faktor' => $first->faktor ?? '',
                'langkah' => $first->langkah_optimalisasi ?? '',
                'id_kejati' => $satkerData->id_kejati,
                'id_kejari' => $satkerData->id_kejari,
                'satkernama' => $satkerData->satkernama,
            ];
        }

        return response()->json($data);
    }

    public function getSubIndikator($rumpun, $id_satker, Request $request)
    {
        $request->merge(['id_satker' => $id_satker]);

        return $this->getSubIndikator2($rumpun, $request);
    }

    public function searchSatker(Request $request)
    {
        $keyword = $request->query('q', $request->query('term', ''));

        $satkers = DB::table('sinori_login')
            ->select('id_satker', 'satkernama', 'id_kejati', 'id_kejari', 'id_sakip_level')
            ->where('id_satker', 'not like', 'was%')
            ->where(function ($query) use ($keyword) {
                if ($keyword !== '') {
                    $query->where('id_satker', 'like', "%{$keyword}%")
                        ->orWhere('satkernama', 'like', "%{$keyword}%");
                }
            })
            ->orderBy('id_kejati')
            ->orderBy('id_kejari')
            ->limit(25)
            ->get()
            ->map(function ($satker) {
                $satker->satkernama = str_replace('_', ' ', $satker->satkernama);

                return $satker;
            });

        return response()->json($satkers);
    }

    /**
     * API Ambil Seluruh Capaian Sastra / Saspro (View Database Terintegrasi)
     */
    public function capaianSasproAll()
    {
        if (!session()->has('tahun_terpilih')) {
            return response()->json(['error' => 'Tahun belum dipilih'], 400);
        }

        $id_satker = session('id_satker');
        $tahun     = session('tahun_terpilih');
        $level     = session('id_sakip_level');

        $id = DB::table('sinori_login')->where('id_satker', $id_satker)->first();
        $satkers = collect();
        $indikatorIds = [];

        if (request()->has('indikator_ids') && !empty(request('indikator_ids'))) {
            $indikatorIds = explode(',', request('indikator_ids'));
        }

        // --- REFAKTOR LOGIKA HAK AKSES PUSAT ---
        if (in_array($id_satker, ['admin', 'menpanrb', 'Pengawasan', 'Panev'])) {
            $satkers = DB::table('sinori_login')
                ->whereIn('id_sakip_level', [1, 2, 3, 4])
                ->pluck('id_satker');

            // Khusus menpanrb, Pengawasan, Panev: injeksi kode_indikator default jika tidak ada request filter
            if (in_array($id_satker, ['menpanrb', 'Pengawasan', 'Panev']) && empty($indikatorIds)) {
                $indikatorIds = [100, 101, 102, 103, 104, 105, 107, 109];
            }
        } elseif ($level === 0 && $id) {
            // Level Kejati
            if (empty($indikatorIds)) {
                $indikatorIds = sastra_indikator_view::select('kode_indikator')->pluck('kode_indikator')->toArray();
            }
            $satkers = DB::table('sinori_login')
                ->where('id_kejati', $id->id_kejati)
                ->whereRaw("id_satker NOT LIKE 'was%'")
                ->pluck('id_satker');
        } else {
            // Level Satker Mandiri
            $satkers = collect([$id_satker]);
        }

        // Ambil saspro terkait indikator dari View
        $sastraId = sastra_indikator_view::where('tahun', 'LIKE', "%$tahun%")
            ->when(!empty($indikatorIds), fn($q) => $q->whereIn('kode_indikator', $indikatorIds))
            ->distinct()
            ->pluck('id_sastra');

        $dataSastra = [];
        $twBulan = [
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12],
        ];

        foreach ($sastraId as $id_sastra) {
            $saspro = DB::table('sakip_sastra_new')
                ->where('id_sastra', $id_sastra)
                ->first(['nama_sastra']);
            if (!$saspro) continue;

            // Perbaikan sintaks: format array pada get() Eloquent
            $indikators = sastra_indikator_view::where('id_sastra', $id_sastra)
                ->get(['id', 'kode_indikator', 'indikator_nama', 'indikator_penghitungan', 'target']);

            $indikatorData = [];
            $sumAllCapaian = 0.0;
            $countAllCapaian = 0;

            foreach ($indikators as $indikator) {
                // Validasi filter in_array menggunakan kode_indikator (huruf/kode)
                if (in_array($id_satker, ['menpanrb', 'Pengawasan', 'Panev']) || ($level == 0)) {
                    if (!in_array($indikator->kode_indikator, $indikatorIds)) continue;
                }

                $target = is_numeric($indikator->target) ? (float)$indikator->target : 0;

                $indikatorTW = [
                    'id' => $indikator->id,
                    'kode_indikator' => $indikator->kode_indikator,
                    'nama' => $indikator->indikator_nama,
                ];

                for ($tw = 1; $tw <= 4; $tw++) {
                    $bulanTW = $twBulan[$tw];
                    $persentaseTW = null;

                    if (str_contains($indikator->indikator_penghitungan, ',')) {
                        $rows = DB::table('pengukuran')
                            ->when(!in_array($id_satker, ['admin', 'menpanrb', 'Pengawasan', 'Panev']), fn($q) => $q->whereIn('id_satker', $satkers))
                            ->where('indikator_id', $indikator->kode_indikator) // Menggunakan string/huruf kode_indikator
                            ->where('tahun', $tahun)
                            ->whereIn('bulan', $bulanTW)
                            ->get(['perhitungan']);

                        $sum = 0; $c = 0;
                        foreach ($rows as $row) {
                            if (!empty($row->perhitungan) && str_contains($row->perhitungan, ';')) {
                                [$penyebut, $pembilang] = array_map('floatval', explode(';', $row->perhitungan));
                                if ($penyebut > 0) {
                                    $persen = ($pembilang / $penyebut) * 100;
                                    if ($persen != 0) {
                                        $sum += $persen;
                                        $c++;
                                    }
                                }
                            }
                        }
                        $persentaseTW = $c > 0 ? round($sum / $c, 2) : null;
                    } else {
                        $rows = DB::table('pengukuran')
                            ->when(!in_array($id_satker, ['admin', 'menpanrb', 'Pengawasan', 'Panev']), fn($q) => $q->whereIn('id_satker', $satkers))
                            ->where('indikator_id', $indikator->kode_indikator) // Menggunakan string/huruf kode_indikator
                            ->where('tahun', $tahun)
                            ->whereIn('bulan', $bulanTW)
                            ->whereNotNull('capaian')
                            ->get(['capaian']);

                        $sum = 0; $count = 0;
                        foreach ($rows as $row) {
                            $value = str_replace(',', '.', trim($row->capaian));
                            if ($value !== '' && is_numeric($value) && (float)$value != 0) {
                                $sum += (float)$value;
                                $count++;
                            }
                        }
                        $persentaseTW = $count > 0 ? round($sum / $count, 2) : null;
                    }

                    $indikatorTW["target_tw{$tw}"] = $target;
                    $indikatorTW["capaian_tw{$tw}"] = $persentaseTW;
                    $indikatorTW["capaian_terhadap_target_tw{$tw}"] = ($persentaseTW !== null && $target > 0)
                        ? round(($persentaseTW / $target) * 100, 2)
                        : null;

                    if ($persentaseTW !== null) {
                        $sumAllCapaian += $persentaseTW;
                        $countAllCapaian++;
                    }
                }

                $indikatorData[] = $indikatorTW;
            }

            $rataPersentase = $countAllCapaian > 0 ? round($sumAllCapaian / $countAllCapaian, 2) : null;
            $rataCapaian = ($rataPersentase !== null && $target > 0) ? round(($rataPersentase / $target) * 100, 2) : null;

            $dataSastra[] = [
                'id_saspro' => $id_sastra,
                'nama_saspro' => $saspro->nama_sastra ?? 'N/A',
                'rata_persentase' => $rataPersentase,
                'rata_capaian' => $rataCapaian,
                'indikators' => $indikatorData
            ];
        }

        return response()->json($dataSastra);
    }

    public function capaianSasproPerKejati()
    {
        return $this->capaianSasproAll();
    }
}
