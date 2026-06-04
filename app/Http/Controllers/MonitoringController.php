<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Bidang;
use App\Models\Indikator;
use App\Services\SatkerAccessService;
use Inertia\Inertia;

class MonitoringController extends Controller
{
    private function normalizeTriwulan($triwulan): int
    {
        $tw = (int) $triwulan;

        return $tw >= 1 && $tw <= 4 ? $tw : 1;
    }

    private function triwulanBulan(int $triwulan): int
    {
        return $this->normalizeTriwulan($triwulan) * 3;
    }

    private function completedTriwulanForYear($tahun): int
    {
        preg_match('/\d{4}/', (string) $tahun, $matches);
        $selectedYear = isset($matches[0]) ? (int) $matches[0] : 0;

        if ($selectedYear === 0) {
            return 4;
        }

        $now = now(config('app.timezone', 'Asia/Jakarta'));
        $currentYear = (int) $now->year;

        if ($selectedYear < $currentYear) {
            return 4;
        }

        if ($selectedYear > $currentYear) {
            return 0;
        }

        return max(0, min(4, intdiv(((int) $now->month - 1), 3)));
    }

    private function indicatorLabels(?string $rawLabels): array
    {
        $labels = collect(explode(',', strtolower((string) $rawLabels)))
            ->map(fn ($label) => trim($label))
            ->filter()
            ->values()
            ->all();

        return $labels ?: ['ditangani', 'diselesaikan'];
    }

    private function numberValue($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }

        $value = str_replace(['%', ' '], '', $value);

        if (str_contains($value, ',') && str_contains($value, '.')) {
            if (strrpos($value, ',') > strrpos($value, '.')) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, ',')) {
            if (preg_match('/^-?\d{1,3}(,\d{3})+$/', $value)) {
                $value = str_replace(',', '', $value);
            } else {
                $value = str_replace(',', '.', $value);
            }
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3}){2,}$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        $value = preg_replace('/[^0-9.\-]/', '', $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function summarizePengukuran($rows, array $labels, int $bulan): array
    {
        $rows = collect($rows);
        $totalDitangani = 0.0;
        $totalDiselesaikan = 0.0;
        $capaianValues = $rows
            ->filter(fn ($row) => (int) $row->bulan === $bulan)
            ->map(fn ($row) => $this->numberValue($row->capaian ?? null))
            ->filter(fn ($value) => $value !== null)
            ->values();

        if ($capaianValues->isNotEmpty()) {
            return [
                'persentase' => round($capaianValues->avg(), 2),
                'total_ditangani' => $totalDitangani,
                'total_diselesaikan' => $totalDiselesaikan,
            ];
        }

        if (count($labels) === 1) {
            return [
                'persentase' => null,
                'total_ditangani' => $totalDitangani,
                'total_diselesaikan' => $totalDiselesaikan,
            ];
        }

        $persentaseSub = [];
        $rows
            ->filter(fn ($row) => (int) $row->bulan >= 1 && (int) $row->bulan <= $bulan)
            ->groupBy(fn ($row) => ($row->id_satker ?? '') . '|' . trim((string) ($row->sub_indikator ?? '')))
            ->each(function ($subRows) use (&$persentaseSub, &$totalDitangani, &$totalDiselesaikan) {
                $penyebut = 0.0;
                $pembilang = 0.0;

                foreach ($subRows as $row) {
                    $parts = collect(explode(';', (string) ($row->perhitungan ?? '')))
                        ->map(fn ($part) => $this->numberValue($part))
                        ->values();

                    if ($parts->count() < 2) {
                        continue;
                    }

                    $a = $parts->get(0);
                    $b = $parts->get(1);

                    if ($a !== null) {
                        $penyebut += $a;
                        $totalDitangani += $a;
                    }

                    if ($b !== null) {
                        $pembilang += $b;
                        $totalDiselesaikan += $b;
                    }
                }

                if ($penyebut > 0) {
                    $persentaseSub[] = round(($pembilang / $penyebut) * 100, 2);
                }
            });

        return [
            'persentase' => $persentaseSub ? round(array_sum($persentaseSub) / count($persentaseSub), 2) : null,
            'total_ditangani' => round($totalDitangani, 2),
            'total_diselesaikan' => round($totalDiselesaikan, 2),
        ];
    }

    private function targetValue($targetRows, int $triwulan): float
    {
        $column = 'target_triwulan_' . $this->normalizeTriwulan($triwulan);

        $values = collect($targetRows)
            ->map(function ($row) use ($column) {
                $triwulanValue = $this->numberValue($row->{$column} ?? null);

                if ($triwulanValue !== null && $triwulanValue > 0) {
                    return $triwulanValue;
                }

                return $this->numberValue($row->target_tahun ?? null);
            })
            ->filter(fn ($value) => $value !== null)
            ->values();

        return $values->isNotEmpty() ? round($values->avg(), 2) : 0.0;
    }

    private function mergedPengukuranText($rows, string $column, int $bulan): string
    {
        $rows = collect($rows);
        $text = $rows
            ->filter(fn ($row) => (int) $row->bulan === $bulan)
            ->map(fn ($row) => trim((string) ($row->{$column} ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->implode("\n");

        if ($text !== '') {
            return $text;
        }

        return $rows
            ->filter(fn ($row) => (int) $row->bulan >= 1 && (int) $row->bulan <= $bulan)
            ->sortByDesc(fn ($row) => (int) $row->bulan)
            ->map(fn ($row) => trim((string) ($row->{$column} ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->implode("\n");
    }

    private function applyLingkupFilter($query, $level): void
    {
        if ($level == 1) {
            $query->whereIn('lingkup', [0, 1]);
        } elseif ($level == 2) {
            $query->whereIn('lingkup', [0, 2, 5, 7]);
        } elseif ($level == 3) {
            $query->whereIn('lingkup', [0, 3, 5, 6, 7]);
        } elseif ($level == 4) {
            $query->whereIn('lingkup', [0, 4, 6, 7]);
        }
    }

    private function canSeeAllMonitoringSatkers(string $idSatker, $level): bool
    {
        return app(SatkerAccessService::class)->canSeeAllSatkers($idSatker, $level);
    }

    private function monitoringSatkerQuery()
    {
        return app(SatkerAccessService::class)->baseSatkerQuery();
    }

    private function monitoringSatkers(string $idSatker, $level, $login)
    {
        $query = $this->monitoringSatkerQuery();

        if ($this->canSeeAllMonitoringSatkers($idSatker, $level)) {
            return $query->pluck('id_satker');
        }

        if ((int) $level === 2 && $login) {
            return $query
                ->where('id_kejati', $login->id_kejati)
                ->pluck('id_satker');
        }

        return collect([$idSatker]);
    }

    private function selectableMonitoringSatkers(string $idSatker, $level, $login)
    {
        $query = $this->monitoringSatkerQuery()
            ->select('id_satker', 'satkernama', 'id_kejati', 'id_kejari', 'id_sakip_level');

        if (! $this->canSeeAllMonitoringSatkers($idSatker, $level)) {
            if ((int) $level === 2 && $login) {
                $query->where('id_kejati', $login->id_kejati);
            } else {
                $query->where('id_satker', $idSatker);
            }
        }

        return $query
            ->orderBy('id_kejati', 'asc')
            ->orderBy('id_kejari', 'asc')
            ->get();
    }

    private function requestedIndicatorIds(): array
    {
        return collect(explode(',', (string) request('indikator_ids', '')))
            ->map(fn ($id) => trim($id))
            ->filter()
            ->values()
            ->all();
    }

    private function applyMasterTahunFilter($query, string $tahun): void
    {
        $query->where(function ($query) use ($tahun) {
            $query->whereNull('tahun')
                ->orWhere('tahun', '')
                ->orWhere('tahun', $tahun)
                ->orWhere('tahun', 'LIKE', "%{$tahun}%");
        });
    }

    private function applyMasterHideFilter($query): void
    {
        $query->where(function ($query) {
            $query->whereNull('hide')
                ->orWhere('hide', '')
                ->orWhere('hide', '0')
                ->orWhere('hide', 0);
        });
    }

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
        $userLevel = session('id_sakip_level');
        $level = $userLevel;
        $search = $request->get('satker');

        // Ambil info user saat ini
        $id = DB::table('sinori_login')->where('id_satker', $id_satker)->first();

        // 1. Logika Penentuan Daftar Satker Pengonfirmasi Hak Akses
        $satkers = $this->selectableMonitoringSatkers((string) $id_satker, $userLevel, $id);
        $allowedSatkers = $satkers->pluck('id_satker')->map(fn ($satker) => (string) $satker);

        if (! $search && ! $this->canSeeAllMonitoringSatkers((string) $id_satker, $userLevel) && (int) $userLevel !== 2) {
            $search = $id_satker;
        }

        $selectedSatker = null;
        $bidangs = [];

        if ($search && $allowedSatkers->contains((string) $search)) {
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
            'bidangs' => $bidangs,
            'id_satker' => $id_satker,
            'levelSakip' => $userLevel,
        ]);
    }

    /**
     * API Ambil Bidang Berdasarkan Satker Dinamis
     */
    public function getBidang($idSatker)
    {
        if (! app(SatkerAccessService::class)->canAccessSatker((string) $idSatker)) {
            return response()->json(['error' => 'Anda tidak memiliki akses ke satker ini'], 403);
        }

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
        if (! session()->has('tahun_terpilih')) {
            return response()->json(['error' => 'Tahun belum dipilih'], 400);
        }

        $tahun = session('tahun_terpilih');
        $tw = $this->normalizeTriwulan($request->query('triwulan', 1));
        $bulan = $this->triwulanBulan($tw);
        $maxCompletedTw = $this->completedTriwulanForYear($tahun);

        $id_satker = $request->query('id_satker');
        if (!$id_satker) {
            return response()->json(['error' => 'id_satker tidak ditemukan'], 400);
        }

        if (! app(SatkerAccessService::class)->canAccessSatker((string) $id_satker)) {
            return response()->json(['error' => 'Anda tidak memiliki akses ke satker ini'], 403);
        }

        $satkerData = DB::table('sinori_login')
            ->where('id_satker', $id_satker)
            ->first(['id_kejati', 'id_kejari', 'satkernama', 'id_sakip_level']);

        if (!$satkerData) {
            return response()->json(['error' => 'Data satker tidak ditemukan'], 404);
        }

        $level = $satkerData->id_sakip_level;

        $indikators = Indikator::query()
            ->where('link', $rumpun)
            ->where('tahun', 'LIKE', "%$tahun%")
            ->where(function ($query) use ($level) {
                $this->applyLingkupFilter($query, $level);
            })
            ->orderBy('id')
            ->get();

        if ($tw > $maxCompletedTw) {
            return response()->json([]);
        }

        $pengukuranByIndikator = DB::table('pengukuran')
            ->where('id_satker', $id_satker)
            ->where('tahun', $tahun)
            ->whereIn('indikator_id', $indikators->pluck('id'))
            ->whereBetween('bulan', [1, $bulan])
            ->get([
                'indikator_id',
                'id_satker',
                'bulan',
                'sub_indikator',
                'capaian',
                'perhitungan',
                'faktor',
                'langkah_optimalisasi',
            ])
            ->groupBy('indikator_id');

        $targetByIndikator = DB::table('target')
            ->where('id_satker', $id_satker)
            ->where('tahun', $tahun)
            ->whereIn('indikator_id', $indikators->pluck('id'))
            ->get()
            ->groupBy('indikator_id');

        $data = [];

        foreach ($indikators as $indikator) {
            $labels = $this->indicatorLabels($indikator->indikator_penghitungan);
            $rows = $pengukuranByIndikator->get($indikator->id, collect());
            $summary = $this->summarizePengukuran($rows, $labels, $bulan);
            $persentase = $summary['persentase'] ?? 0;
            $target_pk = $this->targetValue($targetByIndikator->get($indikator->id, collect()), $tw);
            $capaian_pk = $target_pk > 0 ? round(($persentase / $target_pk) * 100, 2) : 0;

            $data[] = [
                'indikator_id' => $indikator->id,
                'indikator_nama' => $indikator->indikator_nama,
                'indikator_penghitungan' => $indikator->indikator_penghitungan ?: 'Ditangani, Diselesaikan',
                'total_ditangani' => $summary['total_ditangani'],
                'total_diselesaikan' => $summary['total_diselesaikan'],
                'persentase' => $persentase,
                'target_pk' => $target_pk,
                'capaian_pk' => $capaian_pk,
                'faktor' => $this->mergedPengukuranText($rows, 'faktor', $bulan),
                'langkah' => $this->mergedPengukuranText($rows, 'langkah_optimalisasi', $bulan),
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
        $id_satker = (string) session('id_satker');
        $level = session('id_sakip_level');
        $login = DB::table('sinori_login')->where('id_satker', $id_satker)->first();

        $query = $this->monitoringSatkerQuery()
            ->select('id_satker', 'satkernama', 'id_kejati', 'id_kejari', 'id_sakip_level');

        if (! $this->canSeeAllMonitoringSatkers($id_satker, $level)) {
            if ((int) $level === 2 && $login) {
                $query->where('id_kejati', $login->id_kejati);
            } else {
                $query->where('id_satker', $id_satker);
            }
        }

        $satkers = $query
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

        $id_satker = (string) session('id_satker');
        $tahun     = session('tahun_terpilih');
        $level     = session('id_sakip_level');

        $id = DB::table('sinori_login')->where('id_satker', $id_satker)->first();
        $satkers = $this->monitoringSatkers($id_satker, $level, $id)
            ->filter()
            ->map(fn ($satker) => (string) $satker)
            ->values();

        if ($satkers->isEmpty()) {
            return response()->json([]);
        }

        $sastraQuery = DB::table('sakip_sastra_new')
            ->select(['id_sastra', 'nama_sastra', 'target', 'tahun', 'hide', 'urutan', 'link', 'lingkup']);
        $this->applyMasterTahunFilter($sastraQuery, (string) $tahun);
        $this->applyMasterHideFilter($sastraQuery);

        $sastras = $sastraQuery
            ->orderByRaw('COALESCE(urutan, 999999), id_sastra')
            ->get();

        if ($sastras->isEmpty()) {
            return response()->json([]);
        }

        $requestedIndicatorIds = $this->requestedIndicatorIds();
        $indikatorQuery = DB::table('indikator_sastra')
            ->select(['kode_indikator', 'kode_sastra', 'nama_indikator', 'tahun', 'hide', 'urutan', 'link', 'lingkup'])
            ->whereIn('kode_sastra', $sastras->pluck('id_sastra'))
            ->when($requestedIndicatorIds, fn ($query) => $query->whereIn('kode_indikator', $requestedIndicatorIds));

        $this->applyMasterTahunFilter($indikatorQuery, (string) $tahun);
        $this->applyMasterHideFilter($indikatorQuery);

        if (! $this->canSeeAllMonitoringSatkers($id_satker, $level)) {
            $this->applyLingkupFilter($indikatorQuery, $level);
        }

        $indikators = $indikatorQuery
            ->orderBy('kode_sastra')
            ->orderByRaw('COALESCE(urutan, 999999), kode_indikator')
            ->get();

        if ($indikators->isEmpty()) {
            return response()->json([]);
        }

        $indikatorIds = $indikators->pluck('kode_indikator');
        $penghitunganByIndikator = Indikator::query()
            ->whereIn('id', $indikatorIds)
            ->where('tahun', 'LIKE', "%{$tahun}%")
            ->get(['id', 'indikator_penghitungan'])
            ->keyBy(fn ($indikator) => (string) $indikator->id);

        $pengukuranByIndikator = DB::table('pengukuran')
            ->whereIn('id_satker', $satkers)
            ->where('tahun', $tahun)
            ->whereIn('indikator_id', $indikatorIds)
            ->whereBetween('bulan', [1, 12])
            ->get([
                'indikator_id',
                'id_satker',
                'bulan',
                'sub_indikator',
                'capaian',
                'perhitungan',
            ])
            ->groupBy('indikator_id');

        $targetByIndikator = DB::table('target')
            ->whereIn('id_satker', $satkers)
            ->where('tahun', $tahun)
            ->whereIn('indikator_id', $indikatorIds)
            ->get()
            ->groupBy('indikator_id');

        $dataSastra = [];
        $indikatorsBySastra = $indikators->groupBy(fn ($indikator) => (string) $indikator->kode_sastra);
        $maxCompletedTw = $this->completedTriwulanForYear($tahun);

        foreach ($sastras as $sastra) {
            $indikatorRows = $indikatorsBySastra->get((string) $sastra->id_sastra, collect());
            $indikatorData = [];
            $allPersentase = [];
            $allCapaianTarget = [];

            foreach ($indikatorRows as $indikator) {
                $kodeIndikator = (string) $indikator->kode_indikator;
                $labels = $this->indicatorLabels($penghitunganByIndikator->get($kodeIndikator)?->indikator_penghitungan);
                $rows = $pengukuranByIndikator->get($kodeIndikator, collect());
                $targetRows = $targetByIndikator->get($kodeIndikator, collect());

                $indikatorTW = [
                    'id' => $kodeIndikator,
                    'kode_indikator' => $kodeIndikator,
                    'nama' => $indikator->nama_indikator,
                ];

                for ($tw = 1; $tw <= 4; $tw++) {
                    if ($tw > $maxCompletedTw) {
                        $indikatorTW["target_tw{$tw}"] = null;
                        $indikatorTW["capaian_tw{$tw}"] = null;
                        $indikatorTW["capaian_terhadap_target_tw{$tw}"] = null;
                        continue;
                    }

                    $summary = $this->summarizePengukuran($rows, $labels, $this->triwulanBulan($tw));
                    $persentaseTW = $summary['persentase'];
                    $target = $persentaseTW !== null ? $this->targetValue($targetRows, $tw) : null;
                    $capaianTarget = ($persentaseTW !== null && $target !== null && $target > 0)
                        ? round(($persentaseTW / $target) * 100, 2)
                        : null;

                    $indikatorTW["target_tw{$tw}"] = $target;
                    $indikatorTW["capaian_tw{$tw}"] = $persentaseTW;
                    $indikatorTW["capaian_terhadap_target_tw{$tw}"] = $capaianTarget;

                    if ($persentaseTW !== null) {
                        $allPersentase[] = $persentaseTW;
                    }

                    if ($capaianTarget !== null) {
                        $allCapaianTarget[] = $capaianTarget;
                    }
                }

                $indikatorData[] = $indikatorTW;
            }

            $dataSastra[] = [
                'id_saspro' => (string) $sastra->id_sastra,
                'nama_saspro' => (string) $sastra->nama_sastra,
                'rata_persentase' => $allPersentase ? round(array_sum($allPersentase) / count($allPersentase), 2) : null,
                'rata_capaian' => $allCapaianTarget ? round(array_sum($allCapaianTarget) / count($allCapaianTarget), 2) : null,
                'indikators' => $indikatorData,
            ];
        }

        return response()->json($dataSastra);
    }

    public function capaianSasproPerKejati()
    {
        return $this->capaianSasproAll();
    }
}
