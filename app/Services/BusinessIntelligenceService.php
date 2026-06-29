<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class BusinessIntelligenceService
{
    public function satkerIkssRows(string $year, string $satkerId, int $quarter): array
    {
        $quarter = max(1, min(4, $quarter));
        $materializedRows = $this->materializedIkssRows((int) $year, $satkerId, $quarter);

        if ($materializedRows !== []) {
            return $materializedRows;
        }

        $strategicObjectives = $this->strategicObjectives((int) $year);
        $ikssRows = $this->strategicIndicators((int) $year, $strategicObjectives->pluck('id')->all());
        $metrics = $this->measurementMatrix($year, $ikssRows, [$satkerId], $quarter);

        return collect($metrics['by_satker'][$satkerId] ?? [])
            ->filter(fn ($row) => ($row['achievement'] ?? null) !== null)
            ->map(fn ($row) => [
                'ss_id' => $row['ss_id'],
                'nama_ss' => $row['ss_name'],
                'ikss_id' => $row['ikss_id'],
                'nama_ikss' => $row['ikss_name'],
                'target' => $row['target'],
                'capaian' => $row['capaian'],
                'nilai_ikss' => $row['achievement'],
            ])
            ->values()
            ->all();
    }

    public function satkerIkssParameterRows(string $year, string $satkerId, int $quarter): array
    {
        $quarter = max(1, min(4, $quarter));
        $month = $quarter * 3;
        $objectives = $this->strategicObjectives((int) $year);
        $ikssRows = $this->strategicIndicators((int) $year, $objectives->pluck('id')->all());
        $measurementMap = $this->measurementIdsByIkss($ikssRows, $year);
        $measurementIds = collect($measurementMap)->flatten()->unique()->values();

        if ($measurementIds->isEmpty()) {
            return [];
        }

        $targetRows = DB::table('target')
            ->where('id_satker', $satkerId)
            ->where('tahun', $year)
            ->whereIn('indikator_id', $measurementIds)
            ->get([
                'indikator_id',
                'target_tahun',
                'target_triwulan_1',
                'target_triwulan_2',
                'target_triwulan_3',
                'target_triwulan_4',
            ])
            ->groupBy(fn ($row) => (string) $row->indikator_id);
        $pengukuranRows = DB::table('pengukuran')
            ->where('id_satker', $satkerId)
            ->where('tahun', $year)
            ->whereIn('indikator_id', $measurementIds)
            ->whereBetween('bulan', [1, $month])
            ->get(['indikator_id', 'bulan', 'sub_indikator', 'capaian', 'perhitungan'])
            ->groupBy(fn ($row) => (string) $row->indikator_id);
        $indicators = DB::table('sinori_sakip_indikator')
            ->whereIn('id', $measurementIds)
            ->get(['id', 'indikator_nama', 'indikator_penghitungan'])
            ->keyBy(fn ($row) => (string) $row->id);
        $rows = [];

        foreach ($ikssRows as $ikss) {
            foreach ($measurementMap[$ikss['id']] ?? [] as $measurementId) {
                $indicator = $indicators->get((string) $measurementId);
                $target = $this->targetValue($targetRows->get((string) $measurementId, collect()), $quarter);
                $summary = $this->summarizePengukuran(
                    $pengukuranRows->get((string) $measurementId, collect()),
                    $this->indicatorLabels($indicator?->indikator_penghitungan),
                    $month
                );
                $capaian = $summary['persentase'];

                if ($target === null && $capaian === null) {
                    continue;
                }

                $rows[] = [
                    'ikss_id' => (string) $ikss['id'],
                    'measurement_id' => (string) $measurementId,
                    'name' => (string) ($indicator?->indikator_nama ?? $ikss['name']),
                    'target' => $target,
                    'capaian' => $capaian,
                    'achievement' => ($target !== null && $target > 0 && $capaian !== null)
                        ? round(($capaian / $target) * 100, 2)
                        : null,
                ];
            }
        }

        return $rows;
    }

    private function materializedIkssRows(int $year, string $satkerId, int $quarter): array
    {
        if (! Schema::hasTable('ikss_results')) {
            return [];
        }

        $fallbackMetadata = $this->fallbackStrategicMetadata($year);
        $query = DB::table('ikss_results as result')
            ->where('result.satker_id', $satkerId)
            ->where('result.year', $year)
            ->where('result.quarter', $quarter)
            ->whereNotNull('result.capaian');
        $rows = Schema::hasTable('indikator_sastra') && Schema::hasTable('sakip_sastra_new')
            ? $query
                ->leftJoin('indikator_sastra as ikss', 'ikss.kode_indikator', '=', 'result.ikss_id')
                ->leftJoin('sakip_sastra_new as ss', 'ss.id_sastra', '=', 'ikss.kode_sastra')
                ->orderBy('ss.urutan')
                ->orderBy('ikss.urutan')
                ->get([
                    'result.ikss_id',
                    'ikss.kode_sastra as ss_id',
                    'ss.nama_sastra as nama_ss',
                    'ikss.nama_indikator as nama_ikss',
                    'result.target',
                    'result.capaian',
                    'result.achievement as nilai_ikss',
                ])
            : $query
                ->orderBy('result.ikss_id')
                ->get([
                    'result.ikss_id',
                    DB::raw('NULL as ss_id'),
                    DB::raw('NULL as nama_ss'),
                    DB::raw('NULL as nama_ikss'),
                    'result.target',
                    'result.capaian',
                    'result.achievement as nilai_ikss',
                ]);

        return $rows
            ->map(fn ($row) => [
                'ss_id' => (string) ($row->ss_id ?? $fallbackMetadata[(string) $row->ikss_id]['ss_id'] ?? ''),
                'nama_ss' => (string) ($row->nama_ss ?? $fallbackMetadata[(string) $row->ikss_id]['ss_name'] ?? $row->ss_id ?? ''),
                'ikss_id' => (string) $row->ikss_id,
                'nama_ikss' => (string) ($row->nama_ikss ?? $fallbackMetadata[(string) $row->ikss_id]['ikss_name'] ?? $row->ikss_id),
                'target' => $row->target === null ? null : (float) $row->target,
                'capaian' => $row->capaian === null ? null : (float) $row->capaian,
                'nilai_ikss' => (float) ($row->nilai_ikss ?? $row->capaian ?? 0),
            ])
            ->all();
    }

    private function fallbackStrategicMetadata(int $year): array
    {
        if (! Schema::hasTable('ikss_parameters')) {
            return [];
        }

        return DB::table('ikss_parameters')
            ->where('is_active', true)
            ->where('is_result', true)
            ->where(fn ($query) => $query->whereNull('valid_from_year')->orWhere('valid_from_year', '<=', $year))
            ->where(fn ($query) => $query->whereNull('valid_until_year')->orWhere('valid_until_year', '>=', $year))
            ->orderBy('ikss_id')
            ->orderBy('sort_order')
            ->get(['ikss_id', 'name'])
            ->mapWithKeys(function ($parameter) {
                $ikssId = (string) $parameter->ikss_id;
                preg_match('/(?:IKSS)?(\d+)[\.\-](\d+)/i', $ikssId, $match);
                $ssId = isset($match[1]) ? 'SS'.$match[1] : '';

                return [$ikssId => [
                    'ss_id' => $ssId,
                    'ss_name' => $this->fallbackSsName($ssId),
                    'ikss_name' => $this->cleanStrategicName((string) $parameter->name),
                ]];
            })
            ->all();
    }

    private function fallbackSsName(string $ssId): string
    {
        return match ($ssId) {
            'SS1' => 'Terwujudnya kelembagaan hukum yang transparan dan adil',
            'SS2' => 'Terwujudnya efektivitas penegakan hukum dan keadilan melalui transformasi sistem penuntutan',
            'SS3' => 'Terwujudnya efektivitas pelaksanaan kewenangan Advocaat Generaal',
            'SS4' => 'Terwujudnya tata kelola organisasi yang optimal, transparan, dan akuntabel',
            default => $ssId,
        };
    }

    private function cleanStrategicName(string $name): string
    {
        return trim((string) preg_replace('/^IKSS\s*\d+[\.\-]\d+\s*-\s*/i', '', $name));
    }

    public function analyze(string $selectedYear): array
    {
        $snapshot = $this->snapshot($selectedYear);
        $process = new Process([
            config('bi.python_binary'),
            base_path('bi/akip_bi.py'),
        ]);

        $process->setTimeout(config('bi.timeout'));
        $process->setInput(json_encode($snapshot, JSON_THROW_ON_ERROR));

        try {
            $process->mustRun();

            return json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Mesin BI Python tidak dapat dijalankan: '.trim($process->getErrorOutput() ?: $exception->getMessage()),
                previous: $exception
            );
        }
    }

    private function snapshot(string $selectedYear): array
    {
        $year = is_numeric($selectedYear) ? (int) $selectedYear : (int) date('Y');
        $years = range($year - 3, $year);
        $quarter = $this->reportingQuarter($year);
        $satkers = app(SatkerAccessService::class)
            ->baseSatkerQuery()
            ->select('id_satker', 'satkernama', 'id_kejati')
            ->orderBy('id_satker')
            ->get();
        $satkerIds = $satkers->pluck('id_satker')->map(fn ($id) => (string) $id)->all();
        $kejatiNames = $satkers
            ->filter(fn ($satker) => (string) $satker->id_satker === (string) $satker->id_kejati)
            ->mapWithKeys(fn ($satker) => [(string) $satker->id_kejati => str_replace('_', ' ', (string) $satker->satkernama)])
            ->all();

        $strategicObjectives = $this->strategicObjectives($year);
        $ikssRows = $this->strategicIndicators($year, $strategicObjectives->pluck('id')->all());
        $metricsByYear = [];

        foreach ($years as $analysisYear) {
            $metricsByYear[(string) $analysisYear] = $this->measurementMatrix(
                (string) $analysisYear,
                $ikssRows,
                $satkerIds,
                $this->reportingQuarter($analysisYear)
            );
        }

        $currentMetrics = $metricsByYear[(string) $year] ?? ['by_satker' => [], 'by_ikss' => []];
        $previousYear = (string) $years[max(count($years) - 2, 0)];
        $previousMetrics = $metricsByYear[$previousYear] ?? ['by_satker' => [], 'by_ikss' => []];
        $ikssCount = max($ikssRows->count(), 1);

        $satkerRows = $satkers->map(function ($satker) use ($years, $metricsByYear, $currentMetrics, $ikssRows, $ikssCount, $kejatiNames) {
            $id = (string) $satker->id_satker;
            $history = [];

            foreach ($years as $analysisYear) {
                $metrics = $metricsByYear[(string) $analysisYear]['by_satker'][$id] ?? [];
                $achievementValues = $this->values($metrics, 'achievement');
                $history[(string) $analysisYear] = $achievementValues === []
                    ? 0.0
                    : round($this->average($achievementValues), 1);
            }

            $currentRows = collect($currentMetrics['by_satker'][$id] ?? [])->values();
            $achievementValues = $currentRows
                ->pluck('achievement')
                ->filter(fn ($value) => $value !== null)
                ->map(fn ($value) => (float) $value)
                ->values()
                ->all();
            $targetValues = $currentRows
                ->pluck('target')
                ->filter(fn ($value) => $value !== null)
                ->map(fn ($value) => (float) $value)
                ->values()
                ->all();
            $capaianValues = $currentRows
                ->pluck('capaian')
                ->filter(fn ($value) => $value !== null)
                ->map(fn ($value) => (float) $value)
                ->values()
                ->all();
            $attentionIkss = $currentRows
                ->filter(fn ($row) => ($row['achievement'] ?? null) === null || (float) $row['achievement'] < 100)
                ->sortBy(fn ($row) => $row['achievement'] ?? -1)
                ->take(5)
                ->map(fn ($row) => [
                    'id' => $row['ikss_id'],
                    'name' => $row['ikss_name'],
                    'ss_id' => $row['ss_id'],
                    'target' => $row['target'],
                    'capaian' => $row['capaian'],
                    'achievement' => $row['achievement'],
                    'status' => ($row['achievement'] ?? null) === null ? 'Belum ada data' : 'Di bawah target',
                ])
                ->values()
                ->all();

            return [
                'id_satker' => $id,
                'satkernama' => str_replace('_', ' ', (string) $satker->satkernama),
                'id_kejati' => (string) $satker->id_kejati,
                'kejati_name' => $kejatiNames[(string) $satker->id_kejati] ?? (string) $satker->id_kejati,
                'history' => $history,
                'achievement' => $achievementValues === [] ? 0.0 : round($this->average($achievementValues), 1),
                'target_average' => $targetValues === [] ? null : round($this->average($targetValues), 2),
                'capaian_average' => $capaianValues === [] ? null : round($this->average($capaianValues), 2),
                'measured_ikss' => count($achievementValues),
                'ikss_total' => $ikssCount,
                'under_target_count' => $currentRows
                    ->filter(fn ($row) => ($row['achievement'] ?? null) !== null && (float) $row['achievement'] < 100)
                    ->count(),
                'missing_ikss_count' => max($ikssRows->count() - count($achievementValues), 0),
                'attention_ikss' => $attentionIkss,
            ];
        })->values()->all();

        $ikssMetrics = $ikssRows->map(function ($ikss) use ($currentMetrics, $previousMetrics, $satkers) {
            $current = $currentMetrics['by_ikss'][$ikss['id']] ?? [];
            $previous = $previousMetrics['by_ikss'][$ikss['id']] ?? [];
            $achievementValues = $this->values($current, 'achievement');
            $previousValues = $this->values($previous, 'achievement');
            $measuredSatkers = count($achievementValues);
            $averageAchievement = $achievementValues === [] ? null : round($this->average($achievementValues), 1);
            $previousAverage = $previousValues === [] ? null : round($this->average($previousValues), 1);

            return [
                'id' => $ikss['id'],
                'ss_id' => $ikss['ss_id'],
                'ss_name' => $ikss['ss_name'],
                'name' => $ikss['name'],
                'target_average' => $this->nullableAverage($current, 'target'),
                'capaian_average' => $this->nullableAverage($current, 'capaian'),
                'average_achievement' => $averageAchievement,
                'change' => $averageAchievement !== null && $previousAverage !== null
                    ? round($averageAchievement - $previousAverage, 1)
                    : null,
                'measured_satkers' => $measuredSatkers,
                'coverage' => $satkers->count() > 0 ? round(($measuredSatkers / $satkers->count()) * 100, 1) : 0.0,
                'below_target_satkers' => collect($current)
                    ->filter(fn ($row) => ($row['achievement'] ?? null) !== null && (float) $row['achievement'] < 100)
                    ->count(),
                'missing_satkers' => max($satkers->count() - $measuredSatkers, 0),
                'measurement_ids' => $ikss['measurement_ids'] ?? [],
            ];
        })->values()->all();

        $strategicObjectiveMetrics = $strategicObjectives->map(function ($objective) use ($ikssMetrics) {
            $rows = collect($ikssMetrics)
                ->where('ss_id', $objective['id'])
                ->values();
            $achievementValues = $rows
                ->pluck('average_achievement')
                ->filter(fn ($value) => $value !== null)
                ->map(fn ($value) => (float) $value)
                ->values()
                ->all();

            return [
                'id' => $objective['id'],
                'name' => $objective['name'],
                'average_achievement' => $achievementValues === [] ? null : round($this->average($achievementValues), 1),
                'ikss_count' => $rows->count(),
                'measured_ikss' => count($achievementValues),
                'below_target_ikss' => $rows
                    ->filter(fn ($row) => ($row['average_achievement'] ?? null) !== null && (float) $row['average_achievement'] < 100)
                    ->count(),
                'coverage' => $rows->count() > 0 ? round((count($achievementValues) / $rows->count()) * 100, 1) : 0.0,
            ];
        })->values()->all();

        return [
            'selected_year' => (string) $year,
            'years' => array_map('strval', $years),
            'triwulan' => $quarter,
            'satker_count' => $satkers->count(),
            'ss_count' => $strategicObjectives->count(),
            'ikss_count' => $ikssRows->count(),
            'measurement_basis' => 'SS, IKSS, target, capaian, dan capaian terhadap target',
            'satkers' => $satkerRows,
            'strategic_objectives' => $strategicObjectiveMetrics,
            'ikss' => $ikssMetrics,
        ];
    }

    private function strategicObjectives(int $year): Collection
    {
        if (! Schema::hasTable('sakip_sastra_new')) {
            return collect();
        }

        $query = DB::table('sakip_sastra_new')
            ->select($this->selectExistingColumns('sakip_sastra_new', [
                'id_sastra',
                'nama_sastra',
                'target',
                'tahun',
                'hide',
                'urutan',
                'link',
                'lingkup',
            ]));

        $this->applyMasterFilters($query, 'sakip_sastra_new', (string) $year);

        $this->applyOrdering($query, 'sakip_sastra_new', 'id_sastra');

        return $query->get()
            ->map(fn ($row) => [
                'id' => (string) $row->id_sastra,
                'name' => (string) $row->nama_sastra,
                'target' => $this->numberValue($row->target ?? null),
            ])
            ->values();
    }

    private function strategicIndicators(int $year, array $strategicObjectiveIds): Collection
    {
        if (! Schema::hasTable('indikator_sastra')) {
            return collect();
        }

        $ssNames = $this->strategicObjectives($year)->pluck('name', 'id');
        $query = DB::table('indikator_sastra')
            ->select($this->selectExistingColumns('indikator_sastra', [
                'kode_indikator',
                'kode_sastra',
                'nama_indikator',
                'tahun',
                'hide',
                'urutan',
                'link',
                'lingkup',
            ]));

        if ($strategicObjectiveIds !== []) {
            $query->whereIn('kode_sastra', $strategicObjectiveIds);
        }

        $this->applyMasterFilters($query, 'indikator_sastra', (string) $year);
        $this->applyOrdering($query, 'indikator_sastra', 'kode_indikator');

        $rows = $query->get()
            ->map(fn ($row) => [
                'id' => (string) $row->kode_indikator,
                'ss_id' => (string) $row->kode_sastra,
                'ss_name' => (string) ($ssNames[(string) $row->kode_sastra] ?? $row->kode_sastra),
                'name' => (string) $row->nama_indikator,
                'measurement_ids' => [],
            ])
            ->values();

        $map = $this->measurementIdsByIkss($rows, (string) $year);

        return $rows
            ->map(function ($row) use ($map) {
                $row['measurement_ids'] = $map[$row['id']] ?? [];

                return $row;
            })
            ->values();
    }

    private function measurementMatrix(string $year, Collection $ikssRows, array $satkerIds, int $quarter): array
    {
        $month = $quarter * 3;
        $measurementMap = $this->measurementIdsByIkss($ikssRows, $year);
        $yearIkssRows = $ikssRows
            ->map(function ($row) use ($measurementMap) {
                $row['measurement_ids'] = $measurementMap[$row['id']] ?? [];

                return $row;
            })
            ->values();
        $measurementIds = $yearIkssRows
            ->flatMap(fn ($row) => $row['measurement_ids'] ?? [])
            ->unique()
            ->values()
            ->all();

        $targetRows = collect();
        $pengukuranRows = collect();
        $indicatorLabels = collect();

        if ($satkerIds !== [] && $measurementIds !== []) {
            $targetRows = DB::table('target')
                ->whereIn('id_satker', $satkerIds)
                ->where('tahun', $year)
                ->whereIn('indikator_id', $measurementIds)
                ->get([
                    'indikator_id',
                    'id_satker',
                    'target_tahun',
                    'target_triwulan_1',
                    'target_triwulan_2',
                    'target_triwulan_3',
                    'target_triwulan_4',
                ])
                ->groupBy(fn ($row) => $this->metricKey($row->id_satker, $row->indikator_id));

            $pengukuranRows = DB::table('pengukuran')
                ->whereIn('id_satker', $satkerIds)
                ->where('tahun', $year)
                ->whereIn('indikator_id', $measurementIds)
                ->whereBetween('bulan', [1, $month])
                ->get([
                    'indikator_id',
                    'id_satker',
                    'bulan',
                    'sub_indikator',
                    'capaian',
                    'perhitungan',
                ])
                ->groupBy(fn ($row) => $this->metricKey($row->id_satker, $row->indikator_id));

            $indicatorLabels = DB::table('sinori_sakip_indikator')
                ->whereIn('id', $measurementIds)
                ->get(['id', 'indikator_penghitungan'])
                ->keyBy(fn ($row) => (string) $row->id);
        }

        $bySatker = [];
        $byIkss = [];

        foreach ($satkerIds as $satkerId) {
            foreach ($yearIkssRows as $ikss) {
                $sourceRows = [];

                foreach (($ikss['measurement_ids'] ?? []) as $measurementId) {
                    $key = $this->metricKey($satkerId, $measurementId);
                    $target = $this->targetValue($targetRows->get($key, collect()), $quarter);
                    $labels = $this->indicatorLabels($indicatorLabels->get((string) $measurementId)?->indikator_penghitungan);
                    $summary = $this->summarizePengukuran($pengukuranRows->get($key, collect()), $labels, $month);
                    $capaian = $summary['persentase'];

                    if ($target !== null || $capaian !== null) {
                        $sourceRows[] = [
                            'target' => $target,
                            'capaian' => $capaian,
                            'achievement' => ($target !== null && $target > 0 && $capaian !== null)
                                ? round(($capaian / $target) * 100, 2)
                                : null,
                        ];
                    }
                }

                $row = [
                    'ikss_id' => $ikss['id'],
                    'ikss_name' => $ikss['name'],
                    'ss_id' => $ikss['ss_id'],
                    'ss_name' => $ikss['ss_name'],
                    'target' => $this->averageNullableArray(array_column($sourceRows, 'target')),
                    'capaian' => $this->averageNullableArray(array_column($sourceRows, 'capaian')),
                    'achievement' => $this->averageNullableArray(array_column($sourceRows, 'achievement')),
                    'measurement_ids' => $ikss['measurement_ids'] ?? [],
                ];

                $bySatker[$satkerId][$ikss['id']] = $row;
                $byIkss[$ikss['id']][$satkerId] = $row;
            }
        }

        return [
            'by_satker' => $bySatker,
            'by_ikss' => $byIkss,
        ];
    }

    private function measurementIdsByIkss(Collection $ikssRows, string $year): array
    {
        if (! Schema::hasTable('sinori_sakip_indikator') || ! Schema::hasTable('target') || ! Schema::hasTable('pengukuran')) {
            return $ikssRows->mapWithKeys(fn ($row) => [$row['id'] => []])->all();
        }

        $metricIds = collect()
            ->merge(DB::table('target')->where('tahun', $year)->distinct()->pluck('indikator_id'))
            ->merge(DB::table('pengukuran')->where('tahun', $year)->distinct()->pluck('indikator_id'))
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($metricIds->isEmpty()) {
            return $ikssRows->mapWithKeys(fn ($row) => [$row['id'] => []])->all();
        }

        $query = DB::table('sinori_sakip_indikator')
            ->whereIn('id', $metricIds->all())
            ->select($this->selectExistingColumns('sinori_sakip_indikator', [
                'id',
                'indikator_nama',
                'tahun',
                'hide',
            ]));

        $this->applyMasterFilters($query, 'sinori_sakip_indikator', $year);

        $measurementIndicators = $query->get();
        $byId = $measurementIndicators->keyBy(fn ($row) => (string) $row->id);
        $byName = $measurementIndicators->groupBy(fn ($row) => $this->normalizeText((string) $row->indikator_nama));

        return $ikssRows
            ->mapWithKeys(function ($ikss) use ($byId, $byName) {
                $ids = collect();
                $ikssId = (string) $ikss['id'];

                if ($byId->has($ikssId)) {
                    $ids->push($ikssId);
                }

                $nameKey = $this->normalizeText((string) $ikss['name']);
                if ($nameKey !== '' && $byName->has($nameKey)) {
                    $ids = $ids->merge($byName->get($nameKey)->pluck('id')->map(fn ($id) => (string) $id));
                }

                return [$ikssId => $ids->unique()->values()->all()];
            })
            ->all();
    }

    private function reportingQuarter(int $year): int
    {
        $now = now(config('app.timezone', 'Asia/Jakarta'));
        $currentYear = (int) $now->year;

        if ($year < $currentYear) {
            return 4;
        }

        if ($year > $currentYear) {
            return 1;
        }

        return max(1, min(4, intdiv(((int) $now->month - 1), 3)));
    }

    private function applyMasterFilters($query, string $table, string $year): void
    {
        if (Schema::hasColumn($table, 'tahun')) {
            $query->where(function ($query) use ($year) {
                $query->whereNull('tahun')
                    ->orWhere('tahun', '')
                    ->orWhere('tahun', $year)
                    ->orWhereRaw(
                        "FIND_IN_SET(?, REPLACE(CAST(tahun AS CHAR), ' ', '')) > 0",
                        [$year]
                    );
            });
        }

        if (Schema::hasColumn($table, 'hide')) {
            $query->where(function ($query) {
                $query->whereNull('hide')
                    ->orWhere('hide', '')
                    ->orWhere('hide', '0')
                    ->orWhere('hide', 0);
            });
        }
    }

    private function applyOrdering($query, string $table, string $fallbackColumn): void
    {
        if (Schema::hasColumn($table, 'urutan')) {
            $query->orderByRaw('COALESCE(urutan, 999999)');
        }

        $query->orderBy($fallbackColumn);
    }

    private function selectExistingColumns(string $table, array $columns): array
    {
        return collect($columns)
            ->filter(fn ($column) => Schema::hasColumn($table, $column))
            ->values()
            ->all();
    }

    private function metricKey($satkerId, $indicatorId): string
    {
        return (string) $satkerId.'|'.(string) $indicatorId;
    }

    private function targetValue($targetRows, int $quarter): ?float
    {
        $column = 'target_triwulan_'.$quarter;
        $values = collect($targetRows)
            ->map(function ($row) use ($column) {
                $quarterValue = $this->numberValue($row->{$column} ?? null);

                if ($quarterValue !== null && $quarterValue > 0) {
                    return $quarterValue;
                }

                $yearValue = $this->numberValue($row->target_tahun ?? null);

                return $yearValue !== null && $yearValue > 0 ? $yearValue : null;
            })
            ->filter(fn ($value) => $value !== null)
            ->values()
            ->all();

        return $values === [] ? null : round($this->average($values), 2);
    }

    private function summarizePengukuran($rows, array $labels, int $month): array
    {
        $rows = collect($rows);
        $capaianValues = $rows
            ->filter(fn ($row) => (int) $row->bulan === $month)
            ->map(fn ($row) => $this->numberValue($row->capaian ?? null))
            ->filter(fn ($value) => $value !== null)
            ->values();

        if ($capaianValues->isEmpty()) {
            $capaianValues = $rows
                ->filter(fn ($row) => (int) $row->bulan <= $month)
                ->sortByDesc(fn ($row) => (int) $row->bulan)
                ->groupBy(fn ($row) => trim((string) ($row->sub_indikator ?? '')))
                ->map(function ($subRows) {
                    return $subRows
                        ->map(fn ($row) => $this->numberValue($row->capaian ?? null))
                        ->first(fn ($value) => $value !== null);
                })
                ->filter(fn ($value) => $value !== null)
                ->values();
        }

        if ($capaianValues->isNotEmpty()) {
            return [
                'persentase' => round($capaianValues->avg(), 2),
            ];
        }

        if (count($labels) < 2) {
            return ['persentase' => null];
        }

        $persentaseSub = [];
        $rows
            ->filter(fn ($row) => (int) $row->bulan >= 1 && (int) $row->bulan <= $month)
            ->groupBy(fn ($row) => trim((string) ($row->sub_indikator ?? '')))
            ->each(function ($subRows) use (&$persentaseSub) {
                $penyebut = 0.0;
                $pembilang = 0.0;

                foreach ($subRows as $row) {
                    $parts = collect(explode(';', (string) ($row->perhitungan ?? '')))
                        ->map(fn ($part) => $this->numberValue($part))
                        ->values();

                    if ($parts->count() < 2) {
                        continue;
                    }

                    if ($parts->get(0) !== null) {
                        $penyebut += (float) $parts->get(0);
                    }

                    if ($parts->get(1) !== null) {
                        $pembilang += (float) $parts->get(1);
                    }
                }

                if ($penyebut > 0) {
                    $persentaseSub[] = round(($pembilang / $penyebut) * 100, 2);
                }
            });

        return [
            'persentase' => $persentaseSub === [] ? null : round($this->average($persentaseSub), 2),
        ];
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

    private function normalizeText(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function values(array $rows, string $key): array
    {
        return collect($rows)
            ->pluck($key)
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value)
            ->values()
            ->all();
    }

    private function nullableAverage(array $rows, string $key): ?float
    {
        $values = $this->values($rows, $key);

        return $values === [] ? null : round($this->average($values), 2);
    }

    private function averageNullableArray(array $values): ?float
    {
        $values = collect($values)
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value)
            ->values()
            ->all();

        return $values === [] ? null : round($this->average($values), 2);
    }

    private function average(array $values): float
    {
        return array_sum($values) / max(count($values), 1);
    }
}
