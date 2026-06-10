<?php

namespace App\Services;

use App\Models\IkssParameter;
use App\Models\IkssParameterGroup;
use App\Models\IkssParameterValue;
use App\Models\LkjipTemplateBinding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IkssReportDataService
{
    public function build(string $satkerId, int $year, int $quarter, int $level): array
    {
        $satker = DB::table('sinori_login')
            ->where('id_satker', $satkerId)
            ->first(['id_satker', 'satkernama', 'id_kejati', 'id_kejari', 'id_sakip_level']);
        $parameters = IkssParameter::query()
            ->with('group')
            ->where('is_active', true)
            ->where('include_in_report', true)
            ->where(fn ($query) => $query->whereNull('valid_from_year')->orWhere('valid_from_year', '<=', $year))
            ->where(fn ($query) => $query->whereNull('valid_until_year')->orWhere('valid_until_year', '>=', $year))
            ->orderBy('sort_order')
            ->get();
        $values = IkssParameterValue::query()
            ->with('items')
            ->where('satker_id', $satkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->where('month', 0)
            ->get()
            ->keyBy('parameter_id');
        $valuesByCode = $parameters
            ->mapWithKeys(fn (IkssParameter $parameter) => [$parameter->code => $values->get($parameter->id)]);
        $bindings = LkjipTemplateBinding::query()
            ->where('template_level', $level)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $groups = IkssParameterGroup::query()
            ->with(['parameters' => fn ($query) => $query
                ->where('is_active', true)
                ->where(fn ($active) => $active->whereNull('valid_from_year')->orWhere('valid_from_year', '<=', $year))
                ->where(fn ($active) => $active->whereNull('valid_until_year')->orWhere('valid_until_year', '>=', $year))
                ->orderBy('sort_order')])
            ->whereIn('code', $bindings->where('binding_type', 'table')->pluck('source_key')->unique())
            ->get()
            ->keyBy('code');

        $scalars = [
            'sys:satker_id' => $satkerId,
            'sys:satker_name' => $this->satkerName($satker?->satkernama ?? $satkerId),
            'sys:year' => (string) $year,
            'sys:quarter' => (string) $quarter,
            'sys:quarter_roman' => $this->romanQuarter($quarter),
        ];

        foreach ($parameters as $parameter) {
            $scalars['param:'.$parameter->code] = $this->formatValue(
                $values->get($parameter->id)?->value_decimal,
                $values->get($parameter->id)?->value_text,
                $parameter->value_type,
                $parameter->decimal_places
            );
        }

        $results = DB::table('ikss_results')
            ->where('satker_id', $satkerId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->get();

        foreach ($results as $result) {
            $scalars['result:'.$result->ikss_id.':target'] = $this->number($result->target, 2);
            $scalars['result:'.$result->ikss_id.':capaian'] = $this->number($result->capaian, 2);
            $scalars['result:'.$result->ikss_id.':achievement'] = $this->percentage($result->achievement, 2);
        }

        $anchoredScalars = [];
        foreach ($bindings->where('binding_type', 'scalar') as $binding) {
            $key = $this->markerKey($binding->marker);
            $rawValue = match ($binding->source_type) {
                'parameter' => $valuesByCode->get($binding->source_key)?->value_decimal
                    ?? $valuesByCode->get($binding->source_key)?->value_text,
                'system' => $scalars['sys:'.$binding->source_key] ?? null,
                default => null,
            };

            if ($rawValue !== null) {
                $formatted = $this->formatByName($rawValue, $binding->formatter);
                $scalars[$key] = $formatted;

                if (($binding->options['anchors'] ?? []) !== []) {
                    $anchoredScalars[$key] = [
                        'value' => $formatted,
                        'anchors' => $binding->options['anchors'],
                        'minimum_dots' => (int) ($binding->options['minimum_dots'] ?? 3),
                        'prefix' => (string) ($binding->options['prefix'] ?? ''),
                        'after_text' => $binding->options['after_text'] ?? null,
                    ];
                }
            }
        }

        $tables = [];
        foreach ($bindings->where('binding_type', 'table') as $binding) {
            $group = $groups->get($binding->source_key);

            if (! $group) {
                continue;
            }

            $table = $this->groupTable(
                $group,
                $parameters,
                $values,
                $satker,
                $year,
                $quarter
            );
            if (! ($table['has_data'] ?? false)) {
                continue;
            }
            $table['anchors'] = $binding->options['anchors'] ?? [];
            $tables[$this->markerKey($binding->marker)] = $table;
        }

        return [
            'scalars' => $scalars,
            'anchored_scalars' => $anchoredScalars,
            'tables' => $tables,
            'parameter_count' => $parameters->count(),
            'binding_count' => $bindings->count(),
        ];
    }

    private function groupTable(
        IkssParameterGroup $group,
        Collection $parameters,
        Collection $values,
        ?object $satker,
        int $year,
        int $quarter
    ): array {
        $settings = $group->settings ?? [];
        $columns = $settings['columns'] ?? ['No', 'Parameter', 'Nilai'];
        $rowSource = $settings['row_source'] ?? 'fixed_parameters';

        return match ($rowSource) {
            'parameter_items' => $this->itemTable($group, $parameters, $values, $columns),
            'regional_satkers' => $this->regionalTable($group, $parameters, $satker, $year, $quarter, $columns),
            'formula' => $this->formulaTable($group, $parameters, $values, $columns),
            'budget_allocations' => $this->budgetTable($parameters, $values, $columns, false),
            'budget_realization' => $this->budgetTable($parameters, $values, $columns, true),
            default => $this->fixedParameterTable($group, $values, $columns),
        };
    }

    private function budgetTable(
        Collection $parameters,
        Collection $values,
        array $columns,
        bool $includeRealization
    ): array {
        $value = function (string $code) use ($parameters, $values): mixed {
            $parameter = $parameters->firstWhere('code', $code);

            return $parameter ? $values->get($parameter->id)?->value_decimal : null;
        };
        $currency = fn (mixed $amount): string => $amount === null
            ? '-'
            : 'Rp'.number_format((float) $amount, 0, ',', '.');
        $percentage = fn (mixed $realization, mixed $budget): string => $realization === null || ! is_numeric($budget) || (float) $budget <= 0
            ? '-'
            : $this->percentage(((float) $realization / (float) $budget) * 100, 2);
        $definitions = [
            ['Program Dukungan Manajemen', 'rb.anggaran.dukungan_pagu', 'rb.anggaran.dukungan_realisasi'],
            ['Program Penegakan dan Pelayanan Hukum', 'rb.anggaran.penegakan_pagu', 'rb.anggaran.penegakan_realisasi'],
            ['Total', 'rb.anggaran.total_pagu', 'rb.anggaran.total_realisasi'],
        ];
        $rows = collect($definitions)->map(function (array $definition, int $index) use (
            $value,
            $currency,
            $percentage,
            $includeRealization
        ) {
            [$label, $budgetCode, $realizationCode] = $definition;
            $budget = $value($budgetCode);
            $realization = $value($realizationCode);
            $number = $label === 'Total' ? '' : (string) ($index + 1);
            $row = [$number, $label, $currency($budget)];

            if ($includeRealization) {
                $row[] = $currency($realization);
                $row[] = $percentage($realization, $budget);
            }

            return $row;
        })->all();

        return [
            'columns' => $columns,
            'rows' => $rows,
            'has_data' => collect($definitions)->contains(fn (array $definition) => $value($definition[1]) !== null),
        ];
    }

    private function fixedParameterTable(IkssParameterGroup $group, Collection $values, array $columns): array
    {
        $rows = $group->parameters->values()->map(function (IkssParameter $parameter, int $index) use ($values) {
            $value = $values->get($parameter->id);

            return [
                (string) ($index + 1),
                $parameter->name,
                $this->formatValue($value?->value_decimal, $value?->value_text, $parameter->value_type, $parameter->decimal_places),
            ];
        })->all();

        return [
            'columns' => $columns,
            'rows' => $rows,
            'has_data' => $group->parameters->contains(fn ($parameter) => (
                $values->get($parameter->id)?->value_decimal !== null
                || filled($values->get($parameter->id)?->value_text)
            )),
        ];
    }

    private function itemTable(
        IkssParameterGroup $group,
        Collection $parameters,
        Collection $values,
        array $columns
    ): array {
        $parameterCode = $group->settings['parameter_code'] ?? null;
        $parameter = $parameters->firstWhere('code', $parameterCode);
        $value = $parameter ? $values->get($parameter->id) : null;
        $rows = collect($value?->items ?? [])
            ->values()
            ->map(fn ($item, int $index) => [
                (string) ($index + 1),
                $item->item_label,
                $this->formatValue($item->value_decimal, $item->value_text, $parameter?->value_type, $parameter?->decimal_places ?? 2),
            ])
            ->all();

        if (($group->settings['include_average'] ?? false) && $value?->value_decimal !== null) {
            $rows[] = ['', 'Rata-rata', $this->number($value->value_decimal, $parameter?->decimal_places ?? 2)];
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'has_data' => $value?->value_decimal !== null || collect($value?->items ?? [])->isNotEmpty(),
        ];
    }

    private function regionalTable(
        IkssParameterGroup $group,
        Collection $parameters,
        ?object $satker,
        int $year,
        int $quarter,
        array $columns
    ): array {
        $parameterCode = $group->settings['parameter_code'] ?? null;
        $parameter = $parameters->firstWhere('code', $parameterCode);

        if (! $parameter || ! $satker?->id_kejati) {
            return ['columns' => $columns, 'rows' => [], 'has_data' => false];
        }

        $regionalSatkers = DB::table('sinori_login')
            ->where('id_kejati', $satker->id_kejati)
            ->whereIn('id_sakip_level', [3, 4])
            ->orderBy('id_sakip_level')
            ->orderBy('satkernama')
            ->get(['id_satker', 'satkernama']);
        $regionalValues = IkssParameterValue::query()
            ->where('parameter_id', $parameter->id)
            ->whereIn('satker_id', $regionalSatkers->pluck('id_satker'))
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->where('month', 0)
            ->get()
            ->keyBy('satker_id');
        $rows = $regionalSatkers->values()->map(function ($regionalSatker, int $index) use ($regionalValues, $parameter) {
            $value = $regionalValues->get((string) $regionalSatker->id_satker);

            return [
                (string) ($index + 1),
                $this->satkerName($regionalSatker->satkernama),
                $this->formatValue($value?->value_decimal, $value?->value_text, $parameter->value_type, $parameter->decimal_places),
            ];
        })->all();
        $average = $regionalValues->pluck('value_decimal')->filter(fn ($value) => $value !== null)->avg();

        if (($group->settings['include_average'] ?? false) && $average !== null) {
            $rows[] = ['', 'Rata-rata wilayah', $this->number($average, $parameter->decimal_places)];
        }

        return ['columns' => $columns, 'rows' => $rows, 'has_data' => $regionalValues->isNotEmpty()];
    }

    private function formulaTable(
        IkssParameterGroup $group,
        Collection $parameters,
        Collection $values,
        array $columns
    ): array {
        $parameterCodes = $group->settings['parameter_codes'] ?? [];
        $selected = $parameters
            ->whereIn('code', $parameterCodes)
            ->sortBy(fn ($parameter) => array_search($parameter->code, $parameterCodes, true))
            ->values();
        $rows = $selected->map(function (IkssParameter $parameter, int $index) use ($values) {
            $value = $values->get($parameter->id);

            return [
                (string) ($index + 1),
                $parameter->name,
                $this->formatValue($value?->value_decimal, $value?->value_text, $parameter->value_type, $parameter->decimal_places),
            ];
        })->all();

        return [
            'columns' => $columns,
            'rows' => $rows,
            'has_data' => $selected->contains(fn ($parameter) => (
                $values->get($parameter->id)?->value_decimal !== null
                || filled($values->get($parameter->id)?->value_text)
            )),
        ];
    }

    private function markerKey(string $marker): string
    {
        return str_starts_with($marker, '${') && str_ends_with($marker, '}')
            ? substr($marker, 2, -1)
            : $marker;
    }

    private function formatByName(mixed $value, ?string $formatter): string
    {
        return match ($formatter) {
            'percentage_2' => $this->percentage($value, 2),
            'currency' => 'Rp'.number_format((float) $value, 0, ',', '.'),
            'integer' => number_format((float) $value, 0, ',', '.'),
            default => $this->number($value, 2),
        };
    }

    private function formatValue(mixed $decimal, mixed $text, ?string $type, int $decimalPlaces): string
    {
        if ($decimal === null) {
            return $text === null || $text === '' ? '-' : (string) $text;
        }

        return match ($type) {
            'percentage' => $this->percentage($decimal, $decimalPlaces),
            'currency' => 'Rp'.number_format((float) $decimal, $decimalPlaces, ',', '.'),
            'integer' => number_format((float) $decimal, 0, ',', '.'),
            default => $this->number($decimal, $decimalPlaces),
        };
    }

    private function number(mixed $value, int $decimalPlaces): string
    {
        return $value === null ? '-' : number_format((float) $value, $decimalPlaces, ',', '.');
    }

    private function percentage(mixed $value, int $decimalPlaces): string
    {
        return $value === null ? '-' : $this->number($value, $decimalPlaces).'%';
    }

    private function satkerName(string $name): string
    {
        return str_replace('_', ' ', $name);
    }

    private function romanQuarter(int $quarter): string
    {
        return [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'][$quarter] ?? '';
    }
}
