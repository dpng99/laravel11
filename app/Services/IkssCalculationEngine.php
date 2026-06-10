<?php

namespace App\Services;

use InvalidArgumentException;

class IkssCalculationEngine
{
    public function calculate(string $method, array $inputs, array $config = [], int $decimalPlaces = 6): ?float
    {
        $items = collect($inputs)
            ->map(fn ($item) => is_array($item) ? $item : ['value' => $item])
            ->filter(fn ($item) => array_key_exists('value', $item) && is_numeric($item['value']))
            ->map(fn ($item) => [
                'value' => (float) $item['value'],
                'weight' => isset($item['weight']) && is_numeric($item['weight']) ? (float) $item['weight'] : 1.0,
                'role' => (string) ($item['role'] ?? 'component'),
            ])
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        $value = match ($method) {
            'input', 'latest' => $items->last()['value'],
            'sum' => $items->sum('value'),
            'average' => $items->avg('value'),
            'weighted_average' => $this->weightedAverage($items->all()),
            'ratio', 'percentage' => $this->ratio($items->all(), (float) ($config['multiplier'] ?? 100)),
            'min' => $items->min('value'),
            'max' => $items->max('value'),
            default => throw new InvalidArgumentException("Metode kalkulasi IKSS tidak didukung: {$method}"),
        };

        return $value === null ? null : round($value, $decimalPlaces);
    }

    private function weightedAverage(array $items): ?float
    {
        $weight = array_sum(array_column($items, 'weight'));

        if ($weight == 0.0) {
            return null;
        }

        return array_sum(array_map(
            fn ($item) => $item['value'] * $item['weight'],
            $items
        )) / $weight;
    }

    private function ratio(array $items, float $multiplier): ?float
    {
        $numerators = array_filter($items, fn ($item) => $item['role'] === 'numerator');
        $denominators = array_filter($items, fn ($item) => $item['role'] === 'denominator');

        if ($numerators === [] || $denominators === []) {
            return null;
        }

        $numerator = array_sum(array_column($numerators, 'value'));
        $denominator = array_sum(array_column($denominators, 'value'));

        if ($denominator == 0.0) {
            return null;
        }

        return ($numerator / $denominator) * $multiplier;
    }
}
