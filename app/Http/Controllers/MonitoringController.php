<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Bidang;
use App\Models\Indikator;
use App\Models\Pengukuran;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

use function Laravel\Prompts\select;

class MonitoringController extends Controller
{
    public function index(Request $request)
{
    if (!session()->has('tahun_terpilih')) {
        return redirect()->route('pilih.tahun');
    }

    $id_satker = session('id_satker');
    $tahun = session('tahun_terpilih');
    $level = session('id_sakip_level');
    $search = $request->get('satker');

    // Ambil info user saat ini (dipakai pada branch selain admin)
    $id = DB::table('sinori_login')->where('id_satker', $id_satker)->first();

    if (in_array($id_satker, [999999, 'admin', 'Pengawasan', 'Panev','menpanrb'])) {
        // Ambil semua satker, urutkan berdasarkan id_kejati
        $satkers = DB::table('sinori_login')
            ->whereNotIn('id_satker', [888881, 888882, 'admin', 999999, 'Pengawasan', 'Panev','menpanrb'])
            ->where('id_satker', 'not like', 'was%')
            ->where('id_satker', 'not like', '00budi')
            ->where('id_kejati', 'not like', '87') // dikecualikan
            ->orderBy('id_kejati', 'asc')
            ->orderBy('id_kejari', 'asc')
            ->get();
    } else {
        // Pastikan $id ada sebelum akses ->id_kejati
        if ($id) {
            $satkers = DB::table('sinori_login')
                ->where('id_kejati', $id->id_kejati)
                ->where('id_satker', 'not like', 'was%') // dikecualikan
                ->get();
        } else {
            $satkers = collect(); // fallback
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


public function getSubIndikator2($rumpun, Request $request)
    {
        $tahun = session('tahun_terpilih');
        $tw = $request->query('triwulan', 1);
        $bulan_awal = ($tw - 1) * 3 + 1;
        $bulan_akhir = $bulan_awal + 2;
        // 1. Ambil id_satker dari request atau dari user login
        $id_satker = $request->query('id_satker');
        if (!$id_satker) {
            return response()->json(['error' => 'id_satker tidak ditemukan'], 400);
        }

        // 2. Cari data satker di sinori_login
        $satkerData = DB::table('sinori_login')
            ->where('id_satker', $id_satker)
            ->first(['id_kejati', 'id_kejari', 'satkernama', 'id_sakip_level']);

        if (!$satkerData) {
            return response()->json(['error' => 'Data satker tidak ditemukan'], 404);
        }

        // ambil level dari hasil query
        $level = $satkerData->id_sakip_level;

        // 3. Ambil indikator sesuai rumpun, tahun, dan lingkup level
        $indikators = Indikator::where('link', $rumpun)
            ->where(function ($query) use ($tahun) {
                $query->where('tahun', 'LIKE', "%$tahun%");
            })
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

            // === Tentukan label penghitungan (default: Ditangani, Diselesaikan) ===
            $labels = [];
            if (!empty($indikator->indikator_penghitungan)) {
                $labels = array_map('trim', explode(',', strtolower($indikator->indikator_penghitungan)));
            }
            if (empty($labels)) {
                $labels = ['ditangani', 'diselesaikan'];
            }

            if (count($labels) == 1) {
                // MODE 1 LABEL
                $lastMonth = $bulan_akhir;

                $persentase = DB::table('pengukuran')
                    ->where('id_satker', $id_satker)
                    ->where('tahun', $tahun)
                    ->where('indikator_id', $indikator->id)
                    ->where('bulan', $lastMonth)
                    ->orderBy('id', 'desc')
                    ->value('capaian') ?? 0;
            } elseif (count($labels) > 1) {
                // MODE MULTI LABEL
                $rows = DB::table('pengukuran')
                    ->where('id_satker', $id_satker)
                    ->where('tahun', $tahun)
                    ->where('indikator_id', $indikator->id)
                    ->whereBetween('bulan', [1, $bulan_akhir])
                    ->get(['sub_indikator', 'perhitungan']);

                $persentaseSub = [];

                foreach ($rows->groupBy('sub_indikator') as $subIndikator => $dataRow) {
                    $pembilang = 0;
                    $penyebut = 0;

                    foreach ($dataRow as $row) {
                        if (!empty($row->perhitungan) && str_contains($row->perhitungan, ';')) {
                            [$a, $b] = explode(';', $row->perhitungan);

                            // format: "penyebut;pembilang"
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

            // === Ambil Target PK ===
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
                ->where('bulan', $bulan_akhir)
                ->where(function ($q) {
                    $q->whereNotNull('faktor')
                        ->orWhereNotNull('langkah_optimalisasi');
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

 public function capaianSasproAll()
{
    if (!session()->has('tahun_terpilih')) {
        return redirect()->route('pilih.tahun');
    }

    $id_satker = session('id_satker');
    $tahun     = session('tahun_terpilih');
    $level     = session('id_sakip_level');

    $id = DB::table('sinori_login')->where('id_satker', $id_satker)->first();
    $satkers = collect();
    $indikatorIds = [];

    if (request()->has('indikator_ids')) {
        $indikatorIds = explode(',', request('indikator_ids'));
    }

    // Tentukan satker yang dihitung
    if (in_array($id_satker, ['admin'])) {
        $satkers = DB::table('sinori_login')
            ->whereIn('id_sakip_level', [1,2,3,4])
            ->pluck('id_satker');
    } elseif ($id_satker === 'menpanrb' || $id_satker === 'Pengawasan' || $id_satker === 'Panev') {
        $indikatorIds = [100,101,102,103,104,105,107,109];
    } elseif ($level === 0 && $id) {
        $satkers = DB::table('sinori_login')
            ->where('id_kejati', $id->id_kejati)
            ->whereRaw("id_satker NOT LIKE 'was%'")
            ->pluck('id_satker');
    } else {
        $satkers = collect([$id_satker]);
    }

    // Ambil saspro terkait indikator
    $sasproIds = Indikator::where('tahun', 'LIKE', "%$tahun%")
        ->when(!empty($indikatorIds), fn($q) => $q->whereIn('id', $indikatorIds))
        ->distinct()
        ->pluck('id_saspro');

    $dataSaspro = [];

    // Mapping triwulan ke bulan
    $twBulan = [
        1 => [1,2,3],
        2 => [4,5,6],
        3 => [7,8,9],
        4 => [10,11,12],
    ];

    foreach ($sasproIds as $id_saspro) {
        $saspro = DB::table('sinori_sakip_saspro')
            ->where('id', $id_saspro)
            ->first(['saspro_nama']);
        if (!$saspro) continue;

        $indikators = Indikator::where('id_saspro', $id_saspro)
            ->where('tahun', 'LIKE', "%$tahun%")
            ->get();

        $indikatorData = [];
        $sumAllCapaian = 0.0;
        $countAllCapaian = 0;

        foreach ($indikators as $indikator) {
            if ($level == 0 && !in_array($indikator->id, $indikatorIds)) continue;

            $target = is_numeric($indikator->target) ? (float)$indikator->target : 0;

            $indikatorTW = [
                'id' => $indikator->id,
                'nama' => $indikator->indikator_nama,
            ];

            for ($tw = 1; $tw <= 4; $tw++) {
                $bulanTW = $twBulan[$tw];
                $persentaseTW = null;

                // Gabungan perhitungan
                if (str_contains($indikator->indikator_penghitungan, ',')) {
                    $rows = DB::table('pengukuran')
                        ->when($level != 0, fn($q) => $q->whereIn('id_satker', $satkers))
                        ->where('indikator_id', $indikator->id)
                        ->where('tahun', $tahun)
                        ->whereIn('bulan', $bulanTW)
                        ->get(['perhitungan']);

                    $sum = 0; $c = 0;
                    foreach ($rows as $row) {
                        if (!empty($row->perhitungan) && str_contains($row->perhitungan,';')) {
                            [$penyebut,$pembilang] = array_map('floatval', explode(';',$row->perhitungan));
                            if ($penyebut > 0) {
                                $persen = ($pembilang/$penyebut)*100;
                                if ($persen != 0) { // skip 0
                                    $sum += $persen;
                                    $c++;
                                }
                            }
                        }
                    }
                    $persentaseTW = $c>0 ? round($sum/$c,2) : null;

                } else { 
                    // Tunggal → ambil bulan terakhir yang ada datanya
                    $rows = DB::table('pengukuran')
    ->when($level != 0, fn($q) => $q->whereIn('id_satker', $satkers))
    ->where('indikator_id', $indikator->id)
    ->where('tahun', $tahun)
    ->whereIn('bulan', $bulanTW)
    ->whereNotNull('capaian')
    ->get(['capaian']);

$sum = 0;
$count = 0;
foreach ($rows as $row) {
    $value = str_replace(',', '.', trim($row->capaian));
    if ($value !== '' && is_numeric($value) && (float)$value != 0) {
        $sum += (float)$value;
        $count++;
    }
}
$persentaseTW = $count > 0 ? round($sum / $count, 2) : null;

                }

                // Simpan data per triwulan
                $indikatorTW["target_tw{$tw}"] = $target;
                $indikatorTW["capaian_tw{$tw}"] = $persentaseTW;
                $indikatorTW["capaian_terhadap_target_tw{$tw}"] = ($persentaseTW !== null && $target > 0)
                    ? round(($persentaseTW / $target) * 100, 2)
                    : null;

                // Hitung rata-rata Saspro
                if ($persentaseTW !== null) {
                    $sumAllCapaian += $persentaseTW;
                    $countAllCapaian++;
                }
            } // end for TW

            $indikatorData[] = $indikatorTW;
        } // end foreach indikator

        $rataPersentase = $countAllCapaian > 0 ? round($sumAllCapaian / $countAllCapaian, 2) : null;
        $rataCapaian = ($rataPersentase !== null && $target > 0) ? round(($rataPersentase / $target) * 100, 2) : null;

        $dataSaspro[] = [
            'id_saspro' => $id_saspro,
            'nama_saspro' => $saspro->saspro_nama ?? 'N/A',
            'rata_persentase' => $rataPersentase,
            'rata_capaian' => $rataCapaian,
            'indikators' => $indikatorData
        ];
        $chartData = [];
foreach ($dataSaspro as $saspro) {
    foreach ($saspro['indikators'] as $indikator) {
        $chartData['labels'][] = $indikator['nama'];
        $chartData['datasets']['TW1'][] = $indikator['capaian_tw1'] ?? 0;
        $chartData['datasets']['TW2'][] = $indikator['capaian_tw2'] ?? 0;
        $chartData['datasets']['TW3'][] = $indikator['capaian_tw3'] ?? 0;
        $chartData['datasets']['TW4'][] = $indikator['capaian_tw4'] ?? 0;
    }
}
    } // end saspro loop

    return response()->json($dataSaspro);
}
// app/Http/Controllers/MonitoringController.php

    // ... (method index() Anda ada di atas sini) ...

    public function getBidang($idSatker)
    {
        // 1. Dapatkan info untuk satker YANG DIPILIH
        $satker = DB::table('sinori_login')->where('id_satker', $idSatker)->first();

        if (!$satker) {
            return response()->json(['error' => 'Satker not found'], 404);
        }

        // 2. Salin logika yang BENAR
        $level = $satker->id_sakip_level;
        $satkernama = $satker->satkernama ?? '';
        $bidangs = [];

        // --- INI PERBAIKANNYA ---
        // Ganti underscore ('_') dengan spasi (' ')
        $satkernama_with_spaces = str_replace('_', ' ', $satkernama);
        // Cari kata terakhir dari nama yang sudah pakai spasi
        $kataTerakhir = strtolower(trim(strrchr(' ' . $satkernama_with_spaces, ' ')));
        // Jika tidak ada spasi, gunakan seluruh nama
        if (empty($kataTerakhir)) {
            $kataTerakhir = strtolower($satkernama_with_spaces);
        }
        // --- AKHIR PERBAIKAN ---

        if ($level == 1) { // Jika Satker adalah JAM (Level 1)
            $bidangs = Bidang::where('bidang_lokasi', $level)
                ->where('hide', 0)
                // Terapkan filter kata terakhir
                ->whereRaw("LOWER(REPLACE(bidang_nama, '_', ' ')) LIKE ?", ['%' . $kataTerakhir . '%'])
                ->whereNotNull('bidang_level')
                ->orderBy('bidang_level', 'asc')
                ->get();
        
        } elseif (str_starts_with(strtoupper($satkernama_with_spaces), 'CABJARI')) { 
            // Jika Cabjari
            $bidangs = Bidang::where('bidang_lokasi', 4) // Asumsi Cabjari = lokasi 4
                ->whereNotNull('bidang_level')
                ->orderBy('bidang_level', 'asc')
                ->get();
            
            // ... (Logika ganti nama "Kepala Cabang...") ...
            
        } elseif ($level > 1) { // Jika Kejati (2) atau Kejari (3)
            // Tampilkan SEMUA bidang untuk level tersebut
            $bidangs = Bidang::where('bidang_lokasi', $level)
                ->whereNotNull('bidang_level')
                ->orderBy('bidang_level', 'asc')
                ->get();
        }
        
        // 3. Kembalikan data sebagai JSON
        return response()->json($bidangs);
    }
    
    // ... (method getSubIndikator2 dan capaianSasproAll Anda yang ada) ...

}
