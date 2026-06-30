<?php

namespace App\Services;

use App\Models\IkssParameter;
use App\Models\IkssParameterInput;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IkssRegionSimulationService
{
    private const ACTOR = 'simulation:region-full-entry';

    public function __construct(private readonly IkssParameterService $parameters) {}

    public function simulate(string $idKejati, int $year, int $quarter, bool $replace = false): array
    {
        if ($quarter < 1 || $quarter > 4) {
            throw ValidationException::withMessages(['quarter' => 'Triwulan harus berada pada rentang 1 sampai 4.']);
        }

        $kejati = DB::table('sinori_login')
            ->where('id_kejati', $idKejati)
            ->where('id_sakip_level', 2)
            ->first(['id_satker', 'satkernama']);
        $satkers = DB::table('sinori_login')
            ->where('id_kejati', $idKejati)
            ->whereIn('id_sakip_level', [3, 4])
            ->orderBy('id_sakip_level')
            ->orderBy('id_satker')
            ->get(['id_satker', 'satkernama', 'id_sakip_level']);

        if (! $kejati || $satkers->isEmpty()) {
            throw ValidationException::withMessages([
                'id_kejati' => 'Kejaksaan Tinggi atau satuan kerja turunannya tidak ditemukan.',
            ]);
        }

        $parameters = IkssParameter::query()
            ->where('is_active', true)
            ->where('calculation_method', 'input')
            ->where('source_type', 'manual')
            ->where(fn ($query) => $query->whereNull('valid_from_year')->orWhere('valid_from_year', '<=', $year))
            ->where(fn ($query) => $query->whereNull('valid_until_year')->orWhere('valid_until_year', '>=', $year))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $parameterIds = $parameters->pluck('id')->all();
        $satkerIds = $satkers->pluck('id_satker')->map(fn ($id) => (string) $id)->all();
        $existing = IkssParameterInput::query()
            ->with('items')
            ->whereIn('satker_id', $satkerIds)
            ->whereIn('parameter_id', $parameterIds)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->get()
            ->keyBy(fn (IkssParameterInput $value) => $this->valueKey(
                (string) $value->satker_id,
                (int) $value->parameter_id,
                (int) $value->month
            ));

        $now = now();
        $rows = [];
        $tableKeys = [];
        $expectedKeys = [];
        $preserved = 0;
        $lockedEmpty = 0;

        foreach ($satkers as $satkerIndex => $satker) {
            foreach ($parameters as $parameter) {
                if (! in_array((int) $satker->id_sakip_level, $parameter->entry_levels ?? [3, 4], true)) {
                    continue;
                }

                foreach ($this->monthsFor($parameter, $quarter) as $month) {
                    $key = $this->valueKey((string) $satker->id_satker, (int) $parameter->id, $month);
                    $expectedKeys[$key] = true;
                    $current = $existing->get($key);

                    if (! $replace && $current && $this->hasValue($current)) {
                        $preserved++;

                        continue;
                    }

                    if ($current?->status === 'locked') {
                        $lockedEmpty++;

                        continue;
                    }

                    $rows[] = $this->simulationRow(
                        $parameter,
                        $satker,
                        $satkerIndex,
                        $idKejati,
                        $year,
                        $quarter,
                        $month,
                        $now
                    );

                    if ($parameter->input_mode === 'table') {
                        $tableKeys[$key] = [
                            'parameter_id' => (int) $parameter->id,
                            'satker_id' => (string) $satker->id_satker,
                            'month' => $month,
                            'value_type' => (string) $parameter->value_type,
                        ];
                    }
                }
            }
        }

        DB::transaction(function () use ($rows, $tableKeys, $replace, $year, $quarter, $now) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('ikss_parameter_inputs')->upsert(
                    $chunk,
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
                        'lock_version',
                        'updated_at',
                    ]
                );
            }

            if ($tableKeys === []) {
                return;
            }

            $tableValues = IkssParameterInput::query()
                ->whereIn('parameter_id', collect($tableKeys)->pluck('parameter_id')->unique()->all())
                ->whereIn('satker_id', collect($tableKeys)->pluck('satker_id')->unique()->all())
                ->where('year', $year)
                ->where('quarter', $quarter)
                ->get()
                ->filter(fn (IkssParameterInput $value) => isset($tableKeys[$this->valueKey(
                    (string) $value->satker_id,
                    (int) $value->parameter_id,
                    (int) $value->month
                )]));

            if ($replace) {
                DB::table('ikss_parameter_value_items')
                    ->whereIn('parameter_value_id', $tableValues->pluck('id')->all())
                    ->delete();
            }

            $items = [];
            foreach ($tableValues as $value) {
                $key = $this->valueKey((string) $value->satker_id, (int) $value->parameter_id, (int) $value->month);

                for ($index = 1; $index <= 3; $index++) {
                    $items[] = [
                        'parameter_value_id' => $value->id,
                        'item_key' => 'simulation_'.$index,
                        'item_label' => 'Rincian data uji '.$index,
                        'value_decimal' => $tableKeys[$key]['value_type'] === 'currency' ? 100000000 : 100,
                        'value_text' => 'Data uji lengkap',
                        'sort_order' => $index,
                        'metadata' => json_encode(['simulation' => true]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            DB::table('ikss_parameter_value_items')->upsert(
                $items,
                ['parameter_value_id', 'item_key'],
                ['item_label', 'value_decimal', 'value_text', 'sort_order', 'metadata', 'updated_at']
            );
        });

        foreach ($satkerIds as $satkerId) {
            $this->parameters->recalculateSatker($satkerId, $year, $quarter, 'region_full_entry_simulation');
        }
        $regionalResult = $this->parameters->recalculateKejati(
            $idKejati,
            $year,
            $quarter,
            'region_full_entry_simulation'
        );

        $filledKeys = IkssParameterInput::query()
            ->with('items')
            ->whereIn('satker_id', $satkerIds)
            ->whereIn('parameter_id', $parameterIds)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->get()
            ->filter(fn (IkssParameterInput $value) => $this->hasValue($value))
            ->mapWithKeys(fn (IkssParameterInput $value) => [
                $this->valueKey((string) $value->satker_id, (int) $value->parameter_id, (int) $value->month) => true,
            ]);
        $missing = collect(array_keys($expectedKeys))->reject(fn (string $key) => $filledKeys->has($key))->values();

        return [
            'id_kejati' => $idKejati,
            'kejati_satker_id' => (string) $kejati->id_satker,
            'year' => $year,
            'quarter' => $quarter,
            'satkers' => $satkers->count(),
            'kejari' => $satkers->where('id_sakip_level', 3)->count(),
            'cabjari' => $satkers->where('id_sakip_level', 4)->count(),
            'manual_parameters' => $parameters->count(),
            'expected_input_rows' => count($expectedKeys),
            'written_input_rows' => count($rows),
            'preserved_input_rows' => $preserved,
            'locked_empty_rows' => $lockedEmpty,
            'missing_input_rows' => $missing->count(),
            'regional_result' => $regionalResult,
            'result_statuses' => $this->resultStatuses($satkerIds, (string) $kejati->id_satker, $year, $quarter),
        ];
    }

    private function simulationRow(
        IkssParameter $parameter,
        object $satker,
        int $satkerIndex,
        string $idKejati,
        int $year,
        int $quarter,
        int $month,
        mixed $now
    ): array {
        $isText = $parameter->value_type === 'text';
        $value = $parameter->value_type === 'currency'
            ? 100000000 + (($satkerIndex + 1) * 1000000)
            : 100;

        return [
            'parameter_id' => $parameter->id,
            'satker_id' => (string) $satker->id_satker,
            'year' => $year,
            'quarter' => $quarter,
            'month' => $month,
            'value_decimal' => $isText ? null : $value,
            'value_text' => $isText
                ? sprintf(
                    'Data uji lengkap %s Tahun %d Triwulan %d.',
                    str_replace('_', ' ', (string) $satker->satkernama),
                    $year,
                    $quarter
                )
                : null,
            'source_type' => 'manual',
            'status' => 'submitted',
            'source_count' => 1,
            'completeness' => 100,
            'metadata' => json_encode([
                'simulation' => 'region_full_entry',
                'id_kejati' => $idKejati,
                'generated_for' => "{$year}-Q{$quarter}",
            ]),
            'entered_by' => self::ACTOR,
            'verified_by' => null,
            'verified_at' => null,
            'calculated_at' => null,
            'lock_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function monthsFor(IkssParameter $parameter, int $quarter): array
    {
        return $parameter->period_type === 'monthly'
            ? range((($quarter - 1) * 3) + 1, $quarter * 3)
            : [0];
    }

    private function hasValue(IkssParameterInput $value): bool
    {
        return $value->value_decimal !== null
            || trim((string) $value->value_text) !== ''
            || $value->items->isNotEmpty();
    }

    private function valueKey(string $satkerId, int $parameterId, int $month): string
    {
        return "{$satkerId}|{$parameterId}|{$month}";
    }

    private function resultStatuses(array $satkerIds, string $kejatiSatkerId, int $year, int $quarter): array
    {
        $statuses = fn (Collection $ids) => DB::table('ikss_results')
            ->whereIn('satker_id', $ids->all())
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        return [
            'satkers' => $statuses(collect($satkerIds)),
            'kejati' => $statuses(collect([$kejatiSatkerId])),
        ];
    }
}
