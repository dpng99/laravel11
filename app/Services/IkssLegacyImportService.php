<?php

namespace App\Services;

use App\Models\IkssParameter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IkssLegacyImportService
{
    public function __construct(
        private readonly BusinessIntelligenceService $businessIntelligence,
        private readonly IkssParameterService $parameters
    ) {}

    public function import(int $year, int $quarter, array $satkerIds = []): array
    {
        $satkers = DB::table('sinori_login')
            ->whereIn('id_sakip_level', [3, 4])
            ->when($satkerIds !== [], fn ($query) => $query->whereIn('id_satker', $satkerIds))
            ->get(['id_satker', 'id_kejati']);
        $parameterCache = [];
        $importedValues = 0;
        $now = now();

        foreach ($satkers as $satker) {
            $rows = $this->businessIntelligence->satkerIkssParameterRows(
                (string) $year,
                (string) $satker->id_satker,
                $quarter
            );
            $values = [];

            foreach ($rows as $row) {
                $key = $row['ikss_id'].'|'.$row['measurement_id'];

                if (! isset($parameterCache[$key])) {
                    $parameterCache[$key] = IkssParameter::query()->updateOrCreate(
                        ['ikss_id' => $row['ikss_id'], 'code' => 'legacy_'.$row['measurement_id']],
                        [
                            'name' => $row['name'],
                            'description' => 'Parameter hasil sinkronisasi dari data Pelaporan lama.',
                            'parameter_role' => 'result',
                            'input_mode' => 'scalar',
                            'source_type' => 'legacy',
                            'legacy_indicator_id' => $row['measurement_id'],
                            'value_type' => 'percentage',
                            'unit' => '%',
                            'period_type' => 'quarterly',
                            'calculation_method' => 'input',
                            'aggregation_method' => 'average',
                            'entry_levels' => [3, 4],
                            'aggregate_to_levels' => [2],
                            'decimal_places' => 2,
                            'is_result' => true,
                            'is_required' => false,
                            'is_active' => true,
                            'valid_from_year' => $year,
                        ]
                    );
                }

                if ($row['capaian'] === null) {
                    continue;
                }

                $parameter = $parameterCache[$key];
                $values[] = [
                    'parameter_id' => $parameter->id,
                    'satker_id' => (string) $satker->id_satker,
                    'year' => $year,
                    'quarter' => $quarter,
                    'month' => 0,
                    'value_decimal' => $row['capaian'],
                    'value_text' => null,
                    'source_type' => 'legacy_import',
                    'status' => 'calculated',
                    'source_count' => 1,
                    'completeness' => 100,
                    'metadata' => json_encode(['legacy_target' => $row['target']]),
                    'calculated_at' => $now,
                    'lock_version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($values !== []) {
                DB::table('ikss_parameter_values')->upsert(
                    $values,
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
                $importedValues += count($values);
            }

            $this->parameters->recalculateSatker((string) $satker->id_satker, $year, $quarter, 'legacy_import');
        }

        foreach ($satkers->pluck('id_kejati')->filter()->unique() as $idKejati) {
            $this->parameters->recalculateKejati((string) $idKejati, $year, $quarter, 'legacy_import');
        }

        Cache::store('file')->put(
            'ikss-parameter-catalog-version',
            (int) Cache::store('file')->get('ikss-parameter-catalog-version', 1) + 1
        );

        return [
            'satkers' => $satkers->count(),
            'parameters' => count($parameterCache),
            'values' => $importedValues,
            'kejati_rollups' => $satkers->pluck('id_kejati')->filter()->unique()->count(),
        ];
    }
}
