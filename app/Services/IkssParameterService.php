<?php

namespace App\Services;

use App\Models\IkssCalculationRun;
use App\Models\IkssParameter;
use App\Models\IkssParameterInput;
use App\Models\IkssParameterResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class IkssParameterService
{
    public function __construct(private readonly IkssCalculationEngine $engine) {}

    public function catalog(int $year, ?int $level = null): array
    {
        $version = Cache::store('file')->get('ikss-parameter-catalog-version', 1);
        $cacheKey = "ikss-parameter-catalog:{$version}:{$year}:".($level ?? 'all');

        return Cache::store('file')->remember($cacheKey, now()->addMinutes(15), function () use ($year, $level) {
            $strategicMetadata = $this->strategicMetadata($year);

            return $this->activeParameters($year)
                ->filter(fn (IkssParameter $parameter) => $level === null || $this->appliesToLevel($parameter, $level))
                ->reject(fn (IkssParameter $parameter) => $parameter->source_type === 'legacy')
                ->groupBy('ikss_id')
                ->map(fn (Collection $parameters, string $ikssId) => [
                    'ikss_id' => $ikssId,
                    'ss_id' => $strategicMetadata[$ikssId]['ss_id'] ?? null,
                    'ss_name' => $strategicMetadata[$ikssId]['ss_name'] ?? null,
                    'ikss_name' => $strategicMetadata[$ikssId]['ikss_name'] ?? $ikssId,
                    'parameters' => $parameters->map(fn (IkssParameter $parameter) => [
                        'id' => $parameter->id,
                        'parent_id' => $parameter->parent_id,
                        'group_id' => $parameter->group_id,
                        'group_code' => $parameter->group?->code,
                        'group_name' => $parameter->group?->name,
                        'group_description' => $parameter->group?->description,
                        'code' => $parameter->code,
                        'name' => $parameter->name,
                        'description' => $parameter->description,
                        'parameter_role' => $parameter->parameter_role,
                        'input_mode' => $parameter->input_mode,
                        'source_type' => $parameter->source_type,
                        'source_reference' => $parameter->source_reference,
                        'value_type' => $parameter->value_type,
                        'unit' => $parameter->unit,
                        'period_type' => $parameter->period_type,
                        'calculation_method' => $parameter->calculation_method,
                        'aggregation_method' => $parameter->aggregation_method,
                        'aggregation_scope' => $parameter->aggregation_scope,
                        'decimal_places' => $parameter->decimal_places,
                        'is_result' => $parameter->is_result,
                        'is_required' => $parameter->is_required,
                        'include_in_report' => $parameter->include_in_report,
                        'entry_levels' => $parameter->entry_levels,
                        'aggregate_to_levels' => $parameter->aggregate_to_levels,
                        'can_enter' => $level !== null && $this->canEnterAtLevel($parameter, $level),
                        'dependencies' => $parameter->dependencies->map(fn ($dependency) => [
                            'source_parameter_id' => $dependency->source_parameter_id,
                            'source_code' => $dependency->sourceParameter?->code,
                            'role' => $dependency->role,
                            'weight' => $dependency->weight,
                        ])->values()->all(),
                    ])->values()->all(),
                ])
                ->values()
                ->all();
        });
    }

    public function valuesForSatker(string $satkerId, int $year, int $quarter): array
    {
        $satker = $this->satker($satkerId);
        $parameters = $this->activeParameters($year)
            ->filter(fn (IkssParameter $parameter) => $this->appliesToLevel($parameter, (int) $satker->id_sakip_level))
            ->keyBy('id');
        $inputs = IkssParameterInput::query()
            ->with('items')
            ->where('satker_id', $satkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->orderBy('month')
            ->get();
            
        $results = IkssParameterResult::query()
            ->with('items')
            ->where('satker_id', $satkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->orderBy('month')
            ->get();
            
        $values = $inputs->concat($results)->groupBy('parameter_id');

        return $parameters
            ->map(fn (IkssParameter $parameter) => [
                'parameter_id' => $parameter->id,
                'ikss_id' => $parameter->ikss_id,
                'code' => $parameter->code,
                'name' => $parameter->name,
                'group_code' => $parameter->group?->code,
                'parameter_role' => $parameter->parameter_role,
                'input_mode' => $parameter->input_mode,
                'unit' => $parameter->unit,
                'value_type' => $parameter->value_type,
                'calculation_method' => $parameter->calculation_method,
                'is_required' => $parameter->is_required,
                'values' => collect($values->get($parameter->id, []))
                    ->map(fn ($value) => [
                        'month' => $value->month,
                        'value_decimal' => $value->value_decimal === null ? null : (float) $value->value_decimal,
                        'value_text' => $value->value_text,
                        'source_type' => $value->source_type,
                        'status' => $value->status,
                        'completeness' => (float) $value->completeness,
                        'updated_at' => $value->updated_at?->toIso8601String(),
                        'items' => $value->items->map(fn ($item) => [
                            'item_key' => $item->item_key,
                            'item_label' => $item->item_label,
                            'value_decimal' => $item->value_decimal === null ? null : (float) $item->value_decimal,
                            'value_text' => $item->value_text,
                            'sort_order' => $item->sort_order,
                            'metadata' => $item->metadata,
                        ])->values()->all(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    public function storeValues(
        string $satkerId,
        int $year,
        int $quarter,
        array $entries,
        ?string $actor = null
    ): array {
        $satker = $this->satker($satkerId);
        $parameters = $this->activeParameters($year)->keyBy('id');
        $now = now();
        $rows = [];
        $itemEntries = [];
        $clearEntries = [];
        $entryKeys = [];

        $existingValues = IkssParameterInput::query()
            ->where('satker_id', $satkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->get()
            ->keyBy(fn (IkssParameterInput $value) => $value->parameter_id.'|'.$value->month);

        foreach ($entries as $index => $entry) {
            $parameter = $parameters->get((int) $entry['parameter_id']);

            if (! $parameter) {
                throw ValidationException::withMessages([
                    "values.{$index}.parameter_id" => 'Parameter IKSS tidak aktif atau tidak berlaku pada tahun tersebut.',
                ]);
            }

            if ($parameter->calculation_method !== 'input' || $parameter->source_type !== 'manual') {
                throw ValidationException::withMessages([
                    "values.{$index}.parameter_id" => 'Parameter hasil rumus atau sumber eksternal tidak dapat diisi manual.',
                ]);
            }

            if (! $this->canEnterAtLevel($parameter, (int) $satker->id_sakip_level)) {
                throw ValidationException::withMessages([
                    "values.{$index}.parameter_id" => 'Parameter tidak berlaku untuk level satuan kerja ini.',
                ]);
            }

            $month = (int) ($entry['month'] ?? 0);
            $this->assertMonthMatchesPeriod($parameter, $month, $quarter, $index);
            $entryKey = $parameter->id.'|'.$month;

            if (isset($entryKeys[$entryKey])) {
                throw ValidationException::withMessages([
                    "values.{$index}.parameter_id" => 'Parameter dan periode yang sama dikirim lebih dari satu kali.',
                ]);
            }
            $entryKeys[$entryKey] = true;

            $existingValue = $existingValues->get($entryKey);
            if ($existingValue?->status === 'locked') {
                throw ValidationException::withMessages([
                    "values.{$index}.parameter_id" => 'Nilai parameter sudah dikunci dan tidak dapat diubah.',
                ]);
            }

            if ((bool) ($entry['clear'] ?? false)) {
                $clearEntries[] = ['parameter_id' => $parameter->id, 'month' => $month];

                continue;
            }

            $items = collect($entry['items'] ?? [])
                ->filter(fn ($item) => isset($item['item_key'], $item['item_label']))
                ->values();
            $this->assertUniqueItems($items, $index);
            $valueDecimal = array_key_exists('value_decimal', $entry) ? $entry['value_decimal'] : null;
            $this->assertValueMatchesType($parameter, $valueDecimal, $entry['value_text'] ?? null, $items, $index);

            if ($valueDecimal === null && $items->isNotEmpty()) {
                $valueDecimal = $this->engine->calculate(
                    $parameter->aggregation_method,
                    $items->pluck('value_decimal')->filter(fn ($value) => is_numeric($value))->all(),
                    $parameter->formula_config ?? [],
                    $parameter->decimal_places
                );
            }

            $rows[] = [
                'parameter_id' => $parameter->id,
                'satker_id' => $satkerId,
                'year' => $year,
                'quarter' => $quarter,
                'month' => $month,
                'value_decimal' => $valueDecimal,
                'value_text' => $entry['value_text'] ?? null,
                'source_type' => 'manual',
                'status' => $entry['status'] ?? 'draft',
                'source_count' => 1,
                'completeness' => 100,
                'metadata' => isset($entry['metadata']) ? json_encode($entry['metadata']) : null,
                'entered_by' => $actor,
                'verified_by' => null,
                'verified_at' => null,
                'calculated_at' => null,
                'lock_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (array_key_exists('items', $entry)) {
                $itemEntries[$entryKey] = $items->all();
            }
        }

        DB::transaction(function () use ($rows, $itemEntries, $clearEntries, $satker, $satkerId, $year, $quarter) {
            foreach ($clearEntries as $clearEntry) {
                IkssParameterInput::query()
                    ->where('parameter_id', $clearEntry['parameter_id'])
                    ->where('satker_id', $satkerId)
                    ->where('year', $year)
                    ->where('quarter', $quarter)
                    ->where('month', $clearEntry['month'])
                    ->where('source_type', 'manual')
                    ->delete();
            }

            if ($rows !== []) {
                DB::table('ikss_parameter_inputs')->upsert(
                    $rows,
                    ['parameter_id', 'satker_id', 'year', 'quarter', 'month'],
                    [
                        'value_decimal',
                        'value_text',
                        'source_type',
                        'status',
                        'source_count',
                        'completeness',
                        'metadata',
                        'entered_by',
                        'verified_by',
                        'verified_at',
                        'calculated_at',
                        'updated_at',
                    ]
                );
            }

            $this->syncValueItems($satkerId, $year, $quarter, $itemEntries);
            $this->recalculateSatker($satkerId, $year, $quarter, 'data_entry');

            if (in_array((int) $satker->id_sakip_level, [3, 4], true)) {
                $this->recalculateKejati((string) $satker->id_kejati, $year, $quarter, 'child_update');
            }
        });

        $this->forgetPeriodCache($satkerId, $year, $quarter);

        return $this->summary($satkerId, $year, $quarter);
    }

    public function recalculateSatker(string $satkerId, int $year, int $quarter, string $trigger = 'manual'): array
    {
        $run = $this->startRun($satkerId, $year, $quarter, 'satker', $trigger);

        try {
            return DB::transaction(function () use ($satkerId, $year, $quarter, $run) {
                $satker = $this->satker($satkerId);
                $parameters = $this->activeParameters($year)
                    ->filter(fn (IkssParameter $parameter) => $this->appliesToLevel($parameter, (int) $satker->id_sakip_level))
                    ->values();
                $this->hydrateExternalValues($satkerId, $year, $quarter, $parameters);
                $derivedIds = $parameters->where('calculation_method', '!=', 'input')->pluck('id')->all();

                IkssParameterResult::query()
                    ->where('satker_id', $satkerId)
                    ->where('year', $year)
                    ->where('quarter', $quarter)
                    ->where(function ($query) use ($derivedIds) {
                        $query->where('source_type', 'monthly_rollup');

                        if ($derivedIds !== []) {
                            $query->orWhere(fn ($derived) => $derived
                                ->where('source_type', 'formula')
                                ->whereIn('parameter_id', $derivedIds));
                        }
                    })
                    ->delete();

                $periodValues = $this->periodValueMap($satkerId, $year, $quarter, $parameters);
                $monthlyRows = $this->monthlySummaryRows($satkerId, $year, $quarter, $parameters, $periodValues);
                $derivedRows = $this->calculateDerivedValues($satkerId, $year, $quarter, $parameters, $periodValues);
                $generatedRows = array_merge($monthlyRows, $derivedRows);

                if ($generatedRows !== []) {
                    DB::table('ikss_parameter_results')->upsert(
                        $generatedRows,
                        ['parameter_id', 'satker_id', 'year', 'quarter', 'month'],
                        [
                            'value_decimal',
                            'value_text',
                            'source_type',
                            'status',
                            'source_count',
                            'completeness',
                            'metadata',
                            'calculated_at',
                            'updated_at',
                        ]
                    );
                }

                $periodValues = $this->periodValueMap($satkerId, $year, $quarter, $parameters);
                $resultsCount = $this->materializeResults($satkerId, $year, $quarter, $parameters, $periodValues);

                $run->update([
                    'status' => 'completed',
                    'parameters_count' => $parameters->count(),
                    'values_count' => count($periodValues),
                    'finished_at' => now(),
                    'stats' => ['generated_values' => count($generatedRows), 'results' => $resultsCount],
                ]);

                $this->forgetPeriodCache($satkerId, $year, $quarter);

                return ['generated_values' => count($generatedRows), 'results' => $resultsCount];
            });
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function recalculateKejati(string $idKejati, int $year, int $quarter, string $trigger = 'manual'): array
    {
        $kejati = DB::table('sinori_login')
            ->where('id_kejati', $idKejati)
            ->where('id_sakip_level', 2)
            ->first(['id_satker', 'id_kejati']);

        if (! $kejati) {
            throw ValidationException::withMessages(['satker_id' => 'Kejaksaan Tinggi induk tidak ditemukan.']);
        }

        $run = $this->startRun((string) $kejati->id_satker, $year, $quarter, 'regional_rollup', $trigger);

        try {
            return DB::transaction(function () use ($idKejati, $year, $quarter, $kejati, $run) {
                $children = DB::table('sinori_login')
                    ->where('id_kejati', $idKejati)
                    ->whereIn('id_sakip_level', [3, 4])
                    ->pluck('id_satker')
                    ->map(fn ($id) => (string) $id)
                    ->values();
                $parameters = $this->activeParameters($year)
                    ->filter(fn (IkssParameter $parameter) => $parameter->calculation_method === 'input')
                    ->filter(fn (IkssParameter $parameter) => ! in_array($parameter->source_type, ['target', 'system'], true))
                    ->filter(fn (IkssParameter $parameter) => in_array(2, $parameter->aggregate_to_levels ?? [2], true))
                    ->values();
                $parameterIds = $parameters->pluck('id')->all();

                IkssParameterResult::query()
                    ->where('satker_id', (string) $kejati->id_satker)
                    ->where('year', $year)
                    ->where('quarter', $quarter)
                    ->where('source_type', 'regional_rollup')
                    ->whereIn('parameter_id', $parameterIds)
                    ->delete();

                $childInputs = IkssParameterInput::query()
                    ->with('items')
                    ->whereIn('satker_id', $children)
                    ->whereIn('parameter_id', $parameterIds)
                    ->where('year', $year)
                    ->where('quarter', $quarter)
                    ->where('month', 0)
                    ->get();
                    
                $childResults = IkssParameterResult::query()
                    ->with('items')
                    ->whereIn('satker_id', $children)
                    ->whereIn('parameter_id', $parameterIds)
                    ->where('year', $year)
                    ->where('quarter', $quarter)
                    ->where('month', 0)
                    ->get();
                    
                $childValues = $childInputs->concat($childResults)->groupBy('parameter_id');
                $now = now();
                $rows = [];

                foreach ($parameters as $parameter) {
                    $values = collect($childValues->get($parameter->id, []))->filter(fn ($value) => $value->value_decimal !== null);
                    $value = $this->engine->calculate(
                        $parameter->aggregation_method,
                        $values->map(fn ($row) => [
                            'value' => $row->value_decimal,
                            'weight' => $row->source_count ?: 1,
                        ])->all(),
                        $parameter->formula_config ?? [],
                        $parameter->decimal_places
                    );

                    if ($value === null) {
                        continue;
                    }

                    $rows[] = [
                        'parameter_id' => $parameter->id,
                        'satker_id' => (string) $kejati->id_satker,
                        'year' => $year,
                        'quarter' => $quarter,
                        'month' => 0,
                        'value_decimal' => $value,
                        'value_text' => null,
                        'source_type' => 'regional_rollup',
                        'status' => 'calculated',
                        'source_count' => $sourceRows->pluck('satker_id')->unique()->count(),
                        'completeness' => $children->isEmpty()
                            ? 0
                            : round(($sourceRows->pluck('satker_id')->unique()->count() / $children->count()) * 100, 2),
                        'metadata' => json_encode(['id_kejati' => $idKejati, 'child_count' => $children->count()]),
                        'calculated_at' => $now,
                        'lock_version' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('ikss_parameter_results')->upsert(
                        $rows,
                        ['parameter_id', 'satker_id', 'year', 'quarter', 'month'],
                        [
                            'value_decimal',
                            'value_text',
                            'source_type',
                            'status',
                            'source_count',
                            'completeness',
                            'metadata',
                            'calculated_at',
                            'updated_at',
                        ]
                    );
                }

                $this->syncRegionalItems(
                    (string) $kejati->id_satker,
                    $year,
                    $quarter,
                    $parameters,
                    $childValues
                );
                $result = $this->recalculateSatker((string) $kejati->id_satker, $year, $quarter, 'regional_rollup');
                $this->applyRegionalResultTargets(
                    (string) $kejati->id_satker,
                    $children->all(),
                    $year,
                    $quarter
                );
                $run->update([
                    'status' => 'completed',
                    'parameters_count' => $parameters->count(),
                    'values_count' => count($rows),
                    'finished_at' => now(),
                    'stats' => ['children' => $children->count()] + $result,
                ]);

                return ['children' => $children->count(), 'regional_values' => count($rows)] + $result;
            });
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function summary(string $satkerId, int $year, int $quarter): array
    {
        $cacheKey = "ikss-summary:{$satkerId}:{$year}:{$quarter}";

        return Cache::store('file')->remember($cacheKey, now()->addMinutes(10), function () use ($satkerId, $year, $quarter) {
            $fallbackMetadata = $this->fallbackStrategicMetadata($year);
            $query = DB::table('ikss_results as result')
                ->where('result.satker_id', $satkerId)
                ->where('result.year', $year)
                ->where('result.quarter', $quarter);
            $rows = Schema::hasTable('indikator_sastra') && Schema::hasTable('sakip_sastra_new')
                ? $query
                    ->leftJoin('indikator_sastra as ikss', 'ikss.kode_indikator', '=', 'result.ikss_id')
                    ->leftJoin('sakip_sastra_new as ss', 'ss.id_sastra', '=', 'ikss.kode_sastra')
                    ->orderBy('ss.urutan')
                    ->orderBy('ikss.urutan')
                    ->get([
                        'result.ikss_id',
                        'ikss.kode_sastra as ss_id',
                        'ss.nama_sastra as ss_name',
                        'ikss.nama_indikator as ikss_name',
                        'result.target',
                        'result.capaian',
                        'result.achievement',
                        'result.source_count',
                        'result.completeness',
                        'result.status',
                        'result.calculated_at',
                    ])
                : $query
                    ->orderBy('result.ikss_id')
                    ->get([
                        'result.ikss_id',
                        DB::raw('NULL as ss_id'),
                        DB::raw('NULL as ss_name'),
                        DB::raw('NULL as ikss_name'),
                        'result.target',
                        'result.capaian',
                        'result.achievement',
                        'result.source_count',
                        'result.completeness',
                        'result.status',
                        'result.calculated_at',
                    ]);

            return [
                'satker_id' => $satkerId,
                'year' => $year,
                'quarter' => $quarter,
                'results' => $rows->map(fn ($row) => [
                    'ikss_id' => (string) $row->ikss_id,
                    'ss_id' => (string) ($row->ss_id ?? $fallbackMetadata[(string) $row->ikss_id]['ss_id'] ?? ''),
                    'ss_name' => (string) ($row->ss_name ?? $fallbackMetadata[(string) $row->ikss_id]['ss_name'] ?? $row->ss_id ?? ''),
                    'ikss_name' => (string) ($row->ikss_name ?? $fallbackMetadata[(string) $row->ikss_id]['ikss_name'] ?? $row->ikss_id),
                    'target' => $row->target === null ? null : (float) $row->target,
                    'capaian' => $row->capaian === null ? null : (float) $row->capaian,
                    'achievement' => $row->achievement === null ? null : (float) $row->achievement,
                    'source_count' => (int) $row->source_count,
                    'completeness' => (float) $row->completeness,
                    'status' => $row->status,
                    'calculated_at' => $row->calculated_at,
                ])->all(),
            ];
        });
    }

    public function saveDefinition(array $data): IkssParameter
    {
        return DB::transaction(function () use ($data) {
            $dependencies = $data['dependencies'] ?? [];
            unset($data['dependencies']);

            if (isset($data['valid_from_year'], $data['valid_until_year'])
                && $data['valid_from_year'] > $data['valid_until_year']) {
                throw ValidationException::withMessages([
                    'valid_until_year' => 'Tahun akhir berlaku harus sama dengan atau setelah tahun mulai.',
                ]);
            }

            $parameter = IkssParameter::query()->updateOrCreate(
                ['ikss_id' => $data['ikss_id'], 'code' => $data['code']],
                $data
            );

            $this->assertValidDependencies($parameter, $dependencies);
            $parameter->dependencies()->delete();
            foreach ($dependencies as $dependency) {
                $parameter->dependencies()->create($dependency);
            }

            Cache::store('file')->put(
                'ikss-parameter-catalog-version',
                (int) Cache::store('file')->get('ikss-parameter-catalog-version', 1) + 1
            );

            return $parameter->load('dependencies.sourceParameter');
        });
    }

    private function activeParameters(int $year): Collection
    {
        return IkssParameter::query()
            ->with(['group:id,code,name,description,group_type,settings', 'dependencies.sourceParameter:id,code,name'])
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('valid_from_year')->orWhere('valid_from_year', '<=', $year))
            ->where(fn ($query) => $query->whereNull('valid_until_year')->orWhere('valid_until_year', '>=', $year))
            ->orderBy('ikss_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function strategicMetadata(int $year): array
    {
        $fallback = $this->fallbackStrategicMetadata($year);

        if (! Schema::hasTable('indikator_sastra') || ! Schema::hasTable('sakip_sastra_new')) {
            return $fallback;
        }

        $query = DB::table('indikator_sastra as ikss')
            ->leftJoin('sakip_sastra_new as ss', 'ss.id_sastra', '=', 'ikss.kode_sastra');

        if (Schema::hasColumn('indikator_sastra', 'tahun')) {
            $this->applyMasterYearFilter($query, 'ikss.tahun', $year);
        }
        if (Schema::hasColumn('sakip_sastra_new', 'tahun')) {
            $this->applyMasterYearFilter($query, 'ss.tahun', $year);
        }

        $master = $query
            ->get([
                'ikss.kode_indikator',
                'ikss.kode_sastra',
                'ikss.nama_indikator',
                'ss.nama_sastra',
            ])
            ->mapWithKeys(fn ($row) => [(string) $row->kode_indikator => [
                'ss_id' => (string) $row->kode_sastra,
                'ss_name' => (string) ($row->nama_sastra ?? $row->kode_sastra),
                'ikss_name' => (string) ($row->nama_indikator ?? $row->kode_indikator),
            ]])
            ->all();

        return array_replace($fallback, $master);
    }

    private function fallbackStrategicMetadata(int $year): array
    {
        return IkssParameter::query()
            ->where('is_active', true)
            ->where('is_result', true)
            ->where(fn ($query) => $query->whereNull('valid_from_year')->orWhere('valid_from_year', '<=', $year))
            ->where(fn ($query) => $query->whereNull('valid_until_year')->orWhere('valid_until_year', '>=', $year))
            ->orderBy('ikss_id')
            ->orderBy('sort_order')
            ->get(['ikss_id', 'name'])
            ->mapWithKeys(function (IkssParameter $parameter) {
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

    private function applyMasterYearFilter($query, string $column, int $year): void
    {
        $query->where(function ($yearQuery) use ($column, $year) {
            $yearQuery->whereNull($column)
                ->orWhere($column, '')
                ->orWhere($column, (string) $year)
                ->orWhereRaw(
                    "FIND_IN_SET(?, REPLACE(CAST({$column} AS CHAR), ' ', '')) > 0",
                    [(string) $year]
                );
        });
    }

    private function periodValueMap(string $satkerId, int $year, int $quarter, Collection $parameters): array
    {
        $inputs = IkssParameterInput::query()
            ->with('items')
            ->where('satker_id', $satkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->orderBy('month')
            ->orderBy('id')
            ->get();
            
        $results = IkssParameterResult::query()
            ->with('items')
            ->where('satker_id', $satkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->orderBy('month')
            ->orderBy('id')
            ->get();
            
        $rows = $inputs->concat($results)->groupBy('parameter_id');
        $map = [];

        foreach ($parameters as $parameter) {
            $parameterRows = collect($rows->get($parameter->id, []));
            $periodRow = $parameterRows->firstWhere('month', 0);

            if ($periodRow?->value_decimal !== null) {
                $map[$parameter->id] = [
                    'value' => (float) $periodRow->value_decimal,
                    'source_count' => (int) $periodRow->source_count,
                    'completeness' => (float) $periodRow->completeness,
                    'source_type' => $periodRow->source_type,
                ];

                continue;
            }

            if ($periodRow?->items->isNotEmpty()) {
                $value = $this->engine->calculate(
                    $parameter->aggregation_method,
                    $periodRow->items->pluck('value_decimal')->filter(fn ($value) => $value !== null)->all(),
                    $parameter->formula_config ?? [],
                    $parameter->decimal_places
                );

                if ($value !== null) {
                    $map[$parameter->id] = [
                        'value' => $value,
                        'source_count' => $periodRow->items->count(),
                        'completeness' => (float) $periodRow->completeness,
                        'source_type' => $periodRow->source_type,
                    ];

                    continue;
                }
            }

            $monthlyRows = $parameterRows->where('month', '>', 0)->filter(fn ($row) => $row->value_decimal !== null);
            $value = $this->engine->calculate(
                $parameter->aggregation_method,
                $monthlyRows->pluck('value_decimal')->all(),
                $parameter->formula_config ?? [],
                $parameter->decimal_places
            );

            if ($value !== null) {
                $map[$parameter->id] = [
                    'value' => $value,
                    'source_count' => $monthlyRows->count(),
                    'completeness' => min(100, round(($monthlyRows->count() / 3) * 100, 2)),
                    'source_type' => 'monthly_rollup',
                ];
            }
        }

        return $map;
    }

    private function calculateDerivedValues(
        string $satkerId,
        int $year,
        int $quarter,
        Collection $parameters,
        array &$periodValues
    ): array {
        $derived = $parameters->where('calculation_method', '!=', 'input')->values();
        $rowsByParameter = [];
        $now = now();

        for ($pass = 0; $pass < max($derived->count(), 1); $pass++) {
            $changed = false;

            foreach ($derived as $parameter) {
                $inputs = $parameter->dependencies
                    ->filter(fn ($dependency) => isset($periodValues[$dependency->source_parameter_id]))
                    ->map(fn ($dependency) => [
                        'value' => $periodValues[$dependency->source_parameter_id]['value'],
                        'role' => $dependency->role,
                        'weight' => $dependency->weight ?? 1,
                    ])
                    ->all();
                $value = $this->engine->calculate(
                    $parameter->calculation_method,
                    $inputs,
                    $parameter->formula_config ?? [],
                    $parameter->decimal_places
                );

                if ($value === null || (($periodValues[$parameter->id]['value'] ?? null) === $value)) {
                    continue;
                }

                $sourceCount = $parameter->dependencies->count();
                $availableCount = count($inputs);
                $completeness = $sourceCount === 0 ? 0 : round(($availableCount / $sourceCount) * 100, 2);
                $periodValues[$parameter->id] = [
                    'value' => $value,
                    'source_count' => $availableCount,
                    'completeness' => $completeness,
                    'source_type' => 'formula',
                ];
                $rowsByParameter[$parameter->id] = [
                    'parameter_id' => $parameter->id,
                    'satker_id' => $satkerId,
                    'year' => $year,
                    'quarter' => $quarter,
                    'month' => 0,
                    'value_decimal' => $value,
                    'value_text' => null,
                    'source_type' => 'formula',
                    'status' => $completeness >= 100 ? 'calculated' : 'incomplete',
                    'source_count' => $availableCount,
                    'completeness' => $completeness,
                    'metadata' => json_encode(['dependency_count' => $sourceCount]),
                    'calculated_at' => $now,
                    'lock_version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $changed = true;
            }

            if (! $changed) {
                break;
            }
        }

        return array_values($rowsByParameter);
    }

    private function monthlySummaryRows(
        string $satkerId,
        int $year,
        int $quarter,
        Collection $parameters,
        array $periodValues
    ): array {
        $now = now();

        return $parameters
            ->where('period_type', 'monthly')
            ->filter(fn ($parameter) => ($periodValues[$parameter->id]['source_type'] ?? null) === 'monthly_rollup')
            ->map(fn ($parameter) => [
                'parameter_id' => $parameter->id,
                'satker_id' => $satkerId,
                'year' => $year,
                'quarter' => $quarter,
                'month' => 0,
                'value_decimal' => $periodValues[$parameter->id]['value'],
                'value_text' => null,
                'source_type' => 'monthly_rollup',
                'status' => $periodValues[$parameter->id]['completeness'] >= 100 ? 'calculated' : 'incomplete',
                'source_count' => $periodValues[$parameter->id]['source_count'],
                'completeness' => $periodValues[$parameter->id]['completeness'],
                'metadata' => json_encode(['months_in_quarter' => 3]),
                'calculated_at' => $now,
                'lock_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();
    }

    private function materializeResults(
        string $satkerId,
        int $year,
        int $quarter,
        Collection $parameters,
        array $periodValues
    ): int {
        $resultParameters = $parameters->where('is_result', true)->groupBy('ikss_id');
        $targets = $this->targetValuesByIkss($satkerId, $year, $quarter, $parameters);
        $now = now();
        $rows = [];

        foreach ($resultParameters as $ikssId => $ikssParameters) {
            $preferredParameters = $ikssParameters
                ->reject(fn ($parameter) => $parameter->source_type === 'legacy')
                ->values();
            $preferredAvailable = $preferredParameters
                ->filter(fn ($parameter) => isset($periodValues[$parameter->id]));
            $usingPreferred = $preferredAvailable->isNotEmpty();
            $available = $usingPreferred
                ? $preferredAvailable
                : $ikssParameters->filter(fn ($parameter) => isset($periodValues[$parameter->id]));
            $capaian = $this->engine->calculate(
                'average',
                $available->map(fn ($parameter) => $periodValues[$parameter->id]['value'])->all(),
                [],
                6
            );
            $target = $targets[$ikssId]['value'] ?? null;
            $completenessParameters = $usingPreferred
                ? $preferredParameters
                : $available;
            $requiredCount = $completenessParameters->where('is_required', true)->count();
            $completenessBasis = $requiredCount > 0
                ? $completenessParameters->where('is_required', true)
                : $completenessParameters;
            $completeness = $completenessBasis->isEmpty()
                ? 0
                : round($completenessBasis->avg(
                    fn ($parameter) => $periodValues[$parameter->id]['completeness'] ?? 0
                ), 2);

            $rows[] = [
                'ikss_id' => $ikssId,
                'satker_id' => $satkerId,
                'year' => $year,
                'quarter' => $quarter,
                'target' => $target,
                'capaian' => $capaian,
                'achievement' => ($target !== null && $target > 0 && $capaian !== null)
                    ? round(($capaian / $target) * 100, 4)
                    : null,
                'source_count' => $available->sum(fn ($parameter) => $periodValues[$parameter->id]['source_count'] ?? 0),
                'completeness' => $completeness,
                'status' => $completeness >= 100 ? 'complete' : 'incomplete',
                'details' => json_encode([
                    'parameter_ids' => $ikssParameters->pluck('id')->all(),
                    'preferred_parameter_ids' => $preferredParameters->pluck('id')->all(),
                    'available_parameter_ids' => $available->pluck('id')->all(),
                ]),
                'calculated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('ikss_results')->upsert(
                $rows,
                ['ikss_id', 'satker_id', 'year', 'quarter'],
                [
                    'target',
                    'capaian',
                    'achievement',
                    'source_count',
                    'completeness',
                    'status',
                    'details',
                    'calculated_at',
                    'updated_at',
                ]
            );
        }

        $activeIkssIds = $resultParameters->keys()->all();
        DB::table('ikss_results')
            ->where('satker_id', $satkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->when($activeIkssIds !== [], fn ($query) => $query->whereNotIn('ikss_id', $activeIkssIds))
            ->delete();

        return count($rows);
    }

    private function legacyTargets(string $satkerId, int $year, int $quarter, Collection $parameters): array
    {
        if (! Schema::hasTable('target')) {
            return [];
        }

        $legacyIds = $parameters->pluck('legacy_indicator_id')->filter()->unique()->values();

        if ($legacyIds->isEmpty()) {
            return [];
        }

        $column = 'target_triwulan_'.$quarter;

        return DB::table('target')
            ->where('id_satker', $satkerId)
            ->where('tahun', (string) $year)
            ->whereIn('indikator_id', $legacyIds)
            ->get(['indikator_id', $column, 'target_tahun'])
            ->groupBy('indikator_id')
            ->map(function (Collection $rows) use ($column) {
                $values = $rows->map(function ($row) use ($column) {
                    $quarterValue = is_numeric($row->{$column}) ? (float) $row->{$column} : null;

                    return $quarterValue !== null && $quarterValue > 0
                        ? $quarterValue
                        : (is_numeric($row->target_tahun) && (float) $row->target_tahun > 0
                            ? (float) $row->target_tahun
                            : null);
                })->filter(fn ($value) => $value !== null);

                return $values->isEmpty() ? null : $values->avg();
            })
            ->all();
    }

    private function targetValuesByIkss(string $satkerId, int $year, int $quarter, Collection $parameters): array
    {
        $references = $parameters
            ->where('is_result', true)
            ->groupBy('ikss_id')
            ->map(fn (Collection $rows) => $rows
                ->pluck('legacy_indicator_id')
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->all())
            ->all();

        return $this->targetValuesForReferences($satkerId, $year, $quarter, $references);
    }

    private function targetValuesForReferences(string $satkerId, int $year, int $quarter, array $references): array
    {
        if ($references === [] || ! Schema::hasTable('target')) {
            return [];
        }

        $column = 'target_triwulan_'.$quarter;
        $targetRows = DB::table('target')
            ->where('id_satker', $satkerId)
            ->where('tahun', (string) $year)
            ->get(['indikator_id', $column, 'target_tahun']);

        if ($targetRows->isEmpty()) {
            return [];
        }

        $measurementNames = Schema::hasTable('sinori_sakip_indikator')
            ? DB::table('sinori_sakip_indikator')
                ->whereIn('id', $targetRows->pluck('indikator_id')->unique()->all())
                ->pluck('indikator_nama', 'id')
                ->mapWithKeys(fn ($name, $id) => [(string) $id => $this->normalizeText((string) $name)])
            : collect();
        $ikssNames = Schema::hasTable('indikator_sastra')
            ? DB::table('indikator_sastra')
                ->whereIn('kode_indikator', array_keys($references))
                ->pluck('nama_indikator', 'kode_indikator')
                ->mapWithKeys(fn ($name, $id) => [(string) $id => $this->normalizeText((string) $name)])
            : collect();
        $result = [];

        foreach ($references as $reference => $explicitIds) {
            $reference = (string) $reference;
            $candidateIds = collect($explicitIds)->map(fn ($id) => (string) $id);

            if (ctype_digit($reference)) {
                $candidateIds->push($reference);
            }

            $ikssName = $ikssNames->get($reference);
            if ($ikssName) {
                $candidateIds = $candidateIds->merge(
                    $measurementNames
                        ->filter(fn ($name) => $name === $ikssName)
                        ->keys()
                );
            }

            $values = $targetRows
                ->filter(fn ($row) => $candidateIds->contains((string) $row->indikator_id))
                ->map(function ($row) use ($column) {
                    $quarterValue = is_numeric($row->{$column}) ? (float) $row->{$column} : null;

                    return $quarterValue !== null && $quarterValue > 0
                        ? $quarterValue
                        : (is_numeric($row->target_tahun) && (float) $row->target_tahun > 0
                            ? (float) $row->target_tahun
                            : null);
                })
                ->filter(fn ($value) => $value !== null);

            if ($values->isNotEmpty()) {
                $result[$reference] = [
                    'value' => (float) $values->avg(),
                    'source_count' => $values->count(),
                ];
            }
        }

        return $result;
    }

    private function hydrateExternalValues(string $satkerId, int $year, int $quarter, Collection $parameters): void
    {
        $this->hydrateDipaValues($satkerId, $year, $quarter, $parameters);

        $targetParameters = $parameters
            ->where('source_type', 'target')
            ->filter(fn ($parameter) => $parameter->source_reference || $parameter->legacy_indicator_id)
            ->values();

        if ($targetParameters->isEmpty() || ! Schema::hasTable('target')) {
            return;
        }

        IkssParameterResult::query()
            ->where('satker_id', $satkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->where('month', 0)
            ->where('source_type', 'target')
            ->whereIn('parameter_id', $targetParameters->pluck('id'))
            ->delete();

        $legacyIdsByIkss = $parameters
            ->groupBy('ikss_id')
            ->map(fn (Collection $rows) => $rows
                ->pluck('legacy_indicator_id')
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->all());
        $references = $targetParameters
            ->groupBy(fn ($parameter) => (string) ($parameter->source_reference ?: $parameter->ikss_id))
            ->map(function (Collection $rows, string $reference) use ($legacyIdsByIkss) {
                return $rows
                    ->pluck('legacy_indicator_id')
                    ->filter()
                    ->map(fn ($id) => (string) $id)
                    ->merge($legacyIdsByIkss->get($reference, []))
                    ->unique()
                    ->values()
                    ->all();
            })
            ->all();
        $targets = $this->targetValuesForReferences($satkerId, $year, $quarter, $references);
        $now = now();
        $rows = [];

        foreach ($targetParameters as $parameter) {
            $reference = (string) ($parameter->source_reference ?: $parameter->ikss_id);
            $target = $targets[$reference] ?? null;
            $value = $target['value'] ?? null;

            if ($value === null) {
                continue;
            }

            $rows[] = [
                'parameter_id' => $parameter->id,
                'satker_id' => $satkerId,
                'year' => $year,
                'quarter' => $quarter,
                'month' => 0,
                'value_decimal' => $value,
                'value_text' => null,
                'source_type' => 'target',
                'status' => 'calculated',
                'source_count' => $target['source_count'] ?? 1,
                'completeness' => 100,
                'metadata' => json_encode(['source_reference' => $reference]),
                'calculated_at' => $now,
                'lock_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('ikss_parameter_results')->upsert(
                $rows,
                ['parameter_id', 'satker_id', 'year', 'quarter', 'month'],
                [
                    'value_decimal',
                    'source_type',
                    'status',
                    'source_count',
                    'completeness',
                    'metadata',
                    'calculated_at',
                    'updated_at',
                ]
            );
        }
    }

    private function hydrateDipaValues(string $satkerId, int $year, int $quarter, Collection $parameters): void
    {
        $dipaParameters = $parameters
            ->where('source_type', 'system')
            ->filter(fn (IkssParameter $parameter) => str_starts_with((string) $parameter->source_reference, 'dipa.'))
            ->values();

        if ($dipaParameters->isEmpty() || ! Schema::hasTable('sinori_sakip_dipa')) {
            return;
        }

        IkssParameterResult::query()
            ->where('satker_id', $satkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->where('month', 0)
            ->whereIn('parameter_id', $dipaParameters->pluck('id'))
            ->delete();

        $dipa = DB::table('sinori_sakip_dipa')
            ->where('id_satker', $satkerId)
            ->where('id_periode', $year)
            ->orderByRaw('CAST(id_perubahan AS UNSIGNED) DESC')
            ->orderByDesc('id')
            ->first(['id', 'id_perubahan', 'id_tglupload', 'id_pagu', 'id_gakyankum', 'id_dukman']);

        if (! $dipa) {
            return;
        }

        $values = [
            'dipa.id_pagu' => $this->currencyValue($dipa->id_pagu),
            'dipa.id_gakyankum' => $this->currencyValue($dipa->id_gakyankum),
            'dipa.id_dukman' => $this->currencyValue($dipa->id_dukman),
        ];
        $componentTotal = collect([$values['dipa.id_gakyankum'], $values['dipa.id_dukman']])
            ->filter(fn ($value) => $value !== null)
            ->sum();
        $totalAdjusted = false;

        if ($componentTotal > 0 && (
            $values['dipa.id_pagu'] === null
            || abs($values['dipa.id_pagu'] - $componentTotal) / $componentTotal > 0.01
        )) {
            $values['dipa.id_pagu'] = $componentTotal;
            $totalAdjusted = true;
        }

        $now = now();
        $rows = [];

        foreach ($dipaParameters as $parameter) {
            $reference = (string) $parameter->source_reference;
            $value = $values[$reference] ?? null;

            if ($value === null) {
                continue;
            }

            $rows[] = [
                'parameter_id' => $parameter->id,
                'satker_id' => $satkerId,
                'year' => $year,
                'quarter' => $quarter,
                'month' => 0,
                'value_decimal' => $value,
                'value_text' => null,
                'source_type' => 'system',
                'status' => 'calculated',
                'source_count' => 1,
                'completeness' => 100,
                'metadata' => json_encode([
                    'source_reference' => $reference,
                    'dipa_id' => $dipa->id,
                    'dipa_revision' => $dipa->id_perubahan,
                    'dipa_uploaded_at' => $dipa->id_tglupload,
                    'total_adjusted_from_components' => $reference === 'dipa.id_pagu' && $totalAdjusted,
                ]),
                'calculated_at' => $now,
                'lock_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('ikss_parameter_results')->insert($rows);
        }
    }

    private function currencyValue(mixed $value): ?float
    {
        $normalized = preg_replace('/[^\d-]/', '', trim((string) $value));

        return $normalized === null || $normalized === '' || $normalized === '-'
            ? null
            : (float) $normalized;
    }

    private function syncValueItems(string $satkerId, int $year, int $quarter, array $itemEntries): void
    {
        if ($itemEntries === []) {
            return;
        }

        $parameterIds = collect(array_keys($itemEntries))
            ->map(fn ($key) => (int) explode('|', $key, 2)[0])
            ->unique()
            ->values();
        $values = IkssParameterInput::query()
            ->where('satker_id', $satkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->whereIn('parameter_id', $parameterIds)
            ->get()
            ->keyBy(fn ($value) => $value->parameter_id.'|'.$value->month);
        $now = now();

        foreach ($itemEntries as $key => $items) {
            $value = $values->get($key);

            if (! $value) {
                continue;
            }

            $value->items()->delete();

            if ($items === []) {
                continue;
            }

            $value->items()->insert(collect($items)->map(fn ($item, $index) => [
                'parameter_input_id' => $value->id,
                'item_key' => (string) $item['item_key'],
                'item_label' => (string) $item['item_label'],
                'value_decimal' => $item['value_decimal'] ?? null,
                'value_text' => $item['value_text'] ?? null,
                'sort_order' => (int) ($item['sort_order'] ?? $index),
                'metadata' => isset($item['metadata']) ? json_encode($item['metadata']) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }
    }

    private function syncRegionalItems(
        string $kejatiSatkerId,
        int $year,
        int $quarter,
        Collection $parameters,
        Collection $childValues
    ): void {
        $itemParameters = $parameters->whereIn('input_mode', ['list', 'table'])->values();

        if ($itemParameters->isEmpty()) {
            return;
        }

        $regionalValues = IkssParameterResult::query()
            ->where('satker_id', $kejatiSatkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->where('month', 0)
            ->whereIn('parameter_id', $itemParameters->pluck('id'))
            ->get()
            ->keyBy('parameter_id');
        $now = now();

        foreach ($itemParameters as $parameter) {
            $regionalValue = $regionalValues->get($parameter->id);

            if (! $regionalValue) {
                continue;
            }

            $regionalValue->items()->delete();
            $sourceItems = collect($childValues->get($parameter->id, []))
                ->flatMap(fn ($value) => $value->items->map(fn ($item) => [
                    'item_key' => $item->item_key,
                    'item_label' => $item->item_label,
                    'group_key' => $this->regionalItemKey($item->item_label, $item->item_key),
                    'value' => $item->value_decimal,
                    'value_text' => $item->value_text,
                    'sort_order' => $item->sort_order,
                ]))
                ->groupBy('group_key');
            $rows = [];

            foreach ($sourceItems as $itemKey => $items) {
                $value = $this->engine->calculate(
                    $parameter->aggregation_method,
                    $items->pluck('value')->filter(fn ($itemValue) => $itemValue !== null)->all(),
                    $parameter->formula_config ?? [],
                    $parameter->decimal_places
                );
                $first = $items->first();
                $rows[] = [
                    'parameter_result_id' => $regionalValue->id,
                    'item_key' => (string) $itemKey,
                    'item_label' => (string) ($first['item_label'] ?? $itemKey),
                    'value_decimal' => $value,
                    'value_text' => $items->pluck('value_text')->filter()->first(),
                    'sort_order' => (int) ($first['sort_order'] ?? 0),
                    'metadata' => json_encode(['source_count' => $items->count()]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                DB::table('ikss_parameter_result_items')->insert($rows);
            }
        }
    }

    private function applyRegionalResultTargets(
        string $kejatiSatkerId,
        array $childSatkerIds,
        int $year,
        int $quarter
    ): void {
        if ($childSatkerIds === []) {
            return;
        }

        $targets = DB::table('ikss_results')
            ->whereIn('satker_id', $childSatkerIds)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->whereNotNull('target')
            ->groupBy('ikss_id')
            ->selectRaw('ikss_id, AVG(target) as target')
            ->pluck('target', 'ikss_id');

        foreach ($targets as $ikssId => $target) {
            DB::table('ikss_results')
                ->where('ikss_id', $ikssId)
                ->where('satker_id', $kejatiSatkerId)
                ->where('year', $year)
                ->where('quarter', $quarter)
                ->whereNull('target')
                ->update([
                    'target' => $target,
                    'achievement' => DB::raw("CASE WHEN capaian IS NOT NULL AND {$target} > 0 THEN ROUND((capaian / {$target}) * 100, 4) ELSE NULL END"),
                    'updated_at' => now(),
                ]);
        }

        $this->forgetPeriodCache($kejatiSatkerId, $year, $quarter);
    }

    private function assertUniqueItems(Collection $items, int $entryIndex): void
    {
        $duplicates = $items
            ->pluck('item_key')
            ->map(fn ($key) => mb_strtolower(trim((string) $key), 'UTF-8'))
            ->duplicates();

        if ($duplicates->isNotEmpty()) {
            throw ValidationException::withMessages([
                "values.{$entryIndex}.items" => 'Setiap baris parameter harus memiliki item_key yang unik.',
            ]);
        }
    }

    private function assertValueMatchesType(
        IkssParameter $parameter,
        mixed $valueDecimal,
        mixed $valueText,
        Collection $items,
        int $entryIndex
    ): void {
        if ($valueDecimal === null && blank($valueText) && $items->isEmpty()) {
            throw ValidationException::withMessages([
                "values.{$entryIndex}" => 'Isi nilai parameter atau gunakan opsi clear untuk menghapus nilai.',
            ]);
        }

        $numericValues = collect([$valueDecimal])
            ->merge($items->pluck('value_decimal'))
            ->filter(fn ($value) => $value !== null && $value !== '');

        if ($parameter->value_type === 'integer'
            && $numericValues->contains(fn ($value) => ! is_numeric($value) || floor((float) $value) !== (float) $value)) {
            throw ValidationException::withMessages([
                "values.{$entryIndex}.value_decimal" => 'Parameter ini hanya menerima bilangan bulat.',
            ]);
        }

        $minimum = $parameter->formula_config['minimum'] ?? null;
        $maximum = $parameter->formula_config['maximum'] ?? null;

        if ($minimum !== null && $numericValues->contains(fn ($value) => (float) $value < (float) $minimum)) {
            throw ValidationException::withMessages([
                "values.{$entryIndex}.value_decimal" => "Nilai parameter minimal {$minimum}.",
            ]);
        }

        if ($maximum !== null && $numericValues->contains(fn ($value) => (float) $value > (float) $maximum)) {
            throw ValidationException::withMessages([
                "values.{$entryIndex}.value_decimal" => "Nilai parameter maksimal {$maximum}.",
            ]);
        }
    }

    private function assertValidDependencies(IkssParameter $parameter, array $dependencies): void
    {
        $sourceIds = collect($dependencies)->pluck('source_parameter_id')->map(fn ($id) => (int) $id);

        if ($sourceIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'dependencies' => 'Satu parameter sumber tidak boleh dipakai lebih dari satu kali.',
            ]);
        }

        if ($sourceIds->contains((int) $parameter->id)) {
            throw ValidationException::withMessages([
                'dependencies' => 'Parameter tidak dapat bergantung pada dirinya sendiri.',
            ]);
        }

        foreach ($sourceIds as $sourceId) {
            $visited = [];
            $pending = [$sourceId];

            while ($pending !== []) {
                $current = (int) array_pop($pending);
                if ($current === (int) $parameter->id) {
                    throw ValidationException::withMessages([
                        'dependencies' => 'Dependensi membentuk siklus formula.',
                    ]);
                }

                if (isset($visited[$current])) {
                    continue;
                }
                $visited[$current] = true;

                $pending = array_merge(
                    $pending,
                    DB::table('ikss_parameter_dependencies')
                        ->where('parameter_id', $current)
                        ->pluck('source_parameter_id')
                        ->map(fn ($id) => (int) $id)
                        ->all()
                );
            }
        }

        if (in_array($parameter->calculation_method, ['ratio', 'percentage'], true)) {
            $roles = collect($dependencies)->pluck('role');

            if (! $roles->contains('numerator') || ! $roles->contains('denominator')) {
                throw ValidationException::withMessages([
                    'dependencies' => 'Formula rasio harus memiliki pembilang dan penyebut.',
                ]);
            }
        }
    }

    private function regionalItemKey(string $label, string $fallback): string
    {
        $key = Str::slug($label);

        return Str::limit($key !== '' ? $key : $fallback, 150, '');
    }

    private function normalizeText(string $value): string
    {
        $value = Str::ascii(mb_strtolower($value, 'UTF-8'));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function canEnterAtLevel(IkssParameter $parameter, int $level): bool
    {
        return in_array($level, $parameter->entry_levels ?? [3, 4], true);
    }

    private function appliesToLevel(IkssParameter $parameter, int $level): bool
    {
        return $this->canEnterAtLevel($parameter, $level)
            || in_array($level, $parameter->aggregate_to_levels ?? [2], true);
    }

    private function assertMonthMatchesPeriod(IkssParameter $parameter, int $month, int $quarter, int $index): void
    {
        $valid = $parameter->period_type === 'monthly'
            ? $month >= (($quarter - 1) * 3) + 1 && $month <= $quarter * 3
            : $month === 0;

        if (! $valid) {
            throw ValidationException::withMessages([
                "values.{$index}.month" => $parameter->period_type === 'monthly'
                    ? 'Bulan harus berada di dalam triwulan yang dipilih.'
                    : 'Parameter triwulanan/tahunan harus menggunakan month = 0.',
            ]);
        }
    }

    private function satker(string $satkerId): object
    {
        $satker = DB::table('sinori_login')
            ->where('id_satker', $satkerId)
            ->first(['id_satker', 'id_kejati', 'id_kejari', 'id_sakip_level']);

        if (! $satker) {
            throw ValidationException::withMessages(['satker_id' => 'Satuan kerja tidak ditemukan.']);
        }

        return $satker;
    }

    private function startRun(string $satkerId, int $year, int $quarter, string $scope, string $trigger): IkssCalculationRun
    {
        return IkssCalculationRun::query()->create([
            'year' => $year,
            'quarter' => $quarter,
            'satker_id' => $satkerId,
            'scope' => $scope,
            'trigger' => $trigger,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    private function forgetPeriodCache(string $satkerId, int $year, int $quarter): void
    {
        Cache::store('file')->forget("ikss-summary:{$satkerId}:{$year}:{$quarter}");
    }
}
