<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MeasurementPrecisionService
{
    /**
     * Evaluasi performa berdasarkan konfigurasi threshold dari database
     * Menggantikan hard-coded status() dengan fleksibilitas dinamis
     */
    public function evaluatePerformance(
        string $indicatorId,
        float $value,
        ?string $satkerCode = null,
        ?int $level = null
    ): array {
        // Ambil konfigurasi threshold
        $config = $this->getThresholdConfig($indicatorId, $satkerCode, $level);

        if (!$config) {
            // Fallback ke default jika tidak ada konfigurasi
            return $this->defaultEvaluation($value);
        }

        // Evaluasi berdasarkan thresholds
        $evaluation = match (true) {
            $value >= $config['excellent_min'] => [
                'status' => $config['status_excellent'] ?? 'Sempurna',
                'level' => 'excellent',
                'score' => 4,
                'percentage' => $value,
                'threshold_category' => 'excellent'
            ],
            $value >= $config['good_min'] => [
                'status' => $config['status_good'] ?? 'Baik',
                'level' => 'good',
                'score' => 3,
                'percentage' => $value,
                'threshold_category' => 'good'
            ],
            $value >= $config['fair_min'] => [
                'status' => $config['status_fair'] ?? 'Cukup',
                'level' => 'fair',
                'score' => 2,
                'percentage' => $value,
                'threshold_category' => 'fair'
            ],
            default => [
                'status' => $config['status_poor'] ?? 'Perlu Perhatian',
                'level' => 'poor',
                'score' => 1,
                'percentage' => $value,
                'threshold_category' => 'poor'
            ]
        };

        $evaluation['weight'] = $config['weight'] ?? 1.0;
        $evaluation['indicator_type'] = $config['indikator_type'] ?? 'kinerja';

        return $evaluation;
    }

    /**
     * Ambil konfigurasi threshold dari database
     */
    public function getThresholdConfig(
        string $indicatorId,
        ?string $satkerCode = null,
        ?int $level = null
    ): ?array {
        $query = DB::table('measurement_thresholds')
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('deprecated_date')
                  ->orWhere('deprecated_date', '>', now());
            });

        // Prioritas: satker-specific > level-specific > global
        if ($satkerCode) {
            $config = $query->where('satker_id', $satkerCode)
                ->where('indikator_id', $indicatorId)
                ->first();
            if ($config) return (array)$config;
        }

        if ($level) {
            $config = $query->where('level_code', $level)
                ->where('satker_id', null)
                ->where('indikator_id', $indicatorId)
                ->first();
            if ($config) return (array)$config;
        }

        // Global default
        $config = $query->whereNull('level_code')
            ->whereNull('satker_id')
            ->where('indikator_id', $indicatorId)
            ->first();

        return $config ? (array)$config : null;
    }

    /**
     * Evaluasi default jika tidak ada konfigurasi
     */
    private function defaultEvaluation(float $value): array
    {
        return match (true) {
            $value >= 100 => [
                'status' => 'Target Tercapai',
                'level' => 'excellent',
                'score' => 4,
                'percentage' => $value,
                'threshold_category' => 'excellent',
                'weight' => 1.0,
                'indicator_type' => 'kinerja'
            ],
            $value >= 80 => [
                'status' => 'Perlu Optimalisasi',
                'level' => 'good',
                'score' => 3,
                'percentage' => $value,
                'threshold_category' => 'good',
                'weight' => 1.0,
                'indicator_type' => 'kinerja'
            ],
            default => [
                'status' => 'Perlu Perhatian',
                'level' => 'poor',
                'score' => 1,
                'percentage' => $value,
                'threshold_category' => 'poor',
                'weight' => 1.0,
                'indicator_type' => 'kinerja'
            ]
        };
    }

    /**
     * Hitung weighted average dari multiple hasil evaluasi
     */
    public function calculateWeightedAverage(array $results): array
    {
        if (empty($results)) {
            return [
                'weighted_average' => 0,
                'total_weight' => 0,
                'count' => 0,
                'excellence_count' => 0,
                'good_count' => 0,
                'fair_count' => 0,
                'poor_count' => 0
            ];
        }

        $totalWeight = 0;
        $weightedSum = 0;
        $scoreSum = 0;
        $levelCounts = [
            'excellent' => 0,
            'good' => 0,
            'fair' => 0,
            'poor' => 0
        ];

        foreach ($results as $result) {
            $weight = $result['weight'] ?? 1.0;
            $score = $result['score'] ?? 0;
            $level = $result['level'] ?? 'poor';

            $weightedSum += $score * $weight;
            $totalWeight += $weight;
            $levelCounts[$level]++;
        }

        $weightedAverage = $totalWeight > 0 ? $weightedSum / $totalWeight : 0;

        return [
            'weighted_average' => round($weightedAverage, 2),
            'total_weight' => $totalWeight,
            'count' => count($results),
            'excellence_count' => $levelCounts['excellent'],
            'good_count' => $levelCounts['good'],
            'fair_count' => $levelCounts['fair'],
            'poor_count' => $levelCounts['poor'],
            'achievement_rate' => round(
                ($levelCounts['excellent'] + $levelCounts['good']) / count($results) * 100,
                2
            )
        ];
    }

    /**
     * Record measurement ke audit log untuk audit trail
     */
    public function recordMeasurement(
        string $satkerCode,
        int $period,
        string $indicatorId,
        float $newValue,
        ?float $oldValue = null,
        ?string $reason = null,
        array $evaluation = []
    ): int {
        $auditLog = [
            'id_satker' => $satkerCode,
            'id_periode' => $period,
            'id_indikator' => $indicatorId,
            'nilai_lama' => $oldValue,
            'nilai_baru' => $newValue,
            'status_lama' => $this->getLastStatus($satkerCode, $period, $indicatorId),
            'status_baru' => $evaluation['status'] ?? null,
            'perubahan_reason' => $reason,
            'changed_by' => auth()?->id() ?? 'system',
            'changed_at' => now()
        ];

        return DB::table('measurement_audit_log')->insertGetId($auditLog);
    }

    /**
     * Ambil status terakhir untuk comparison
     */
    private function getLastStatus(string $satkerCode, int $period, string $indicatorId): ?string
    {
        $last = DB::table('measurement_audit_log')
            ->where('id_satker', $satkerCode)
            ->where('id_periode', $period)
            ->where('id_indikator', $indicatorId)
            ->orderByDesc('changed_at')
            ->first();

        return $last?->status_baru;
    }

    /**
     * Get measurement history untuk suatu indikator
     */
    public function getMeasurementHistory(
        string $satkerCode,
        string $indicatorId,
        ?int $periods = 12
    ): array {
        return DB::table('measurement_audit_log')
            ->where('id_satker', $satkerCode)
            ->where('id_indikator', $indicatorId)
            ->orderByDesc('changed_at')
            ->limit($periods)
            ->get()
            ->toArray();
    }

    /**
     * Validate measurement configuration
     */
    public function validateConfiguration(array $config): array
    {
        $errors = [];

        if (!isset($config['excellent_min'])) {
            $errors[] = 'excellent_min harus didefinisikan';
        }

        if (!isset($config['good_min'])) {
            $errors[] = 'good_min harus didefinisikan';
        }

        if (!isset($config['fair_min'])) {
            $errors[] = 'fair_min harus didefinisikan';
        }

        if (!isset($config['poor_max'])) {
            $errors[] = 'poor_max harus didefinisikan';
        }

        // Validasi urutan
        if ($config['excellent_min'] < $config['good_min']) {
            $errors[] = 'excellent_min harus >= good_min';
        }

        if ($config['good_min'] < $config['fair_min']) {
            $errors[] = 'good_min harus >= fair_min';
        }

        if ($config['fair_min'] < $config['poor_max']) {
            $errors[] = 'fair_min harus >= poor_max';
        }

        // Validasi weights
        if (($config['weight'] ?? 1.0) <= 0) {
            $errors[] = 'weight harus lebih besar dari 0';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get comparison antara dua periode
     */
    public function getPeriodComparison(
        string $satkerCode,
        string $indicatorId,
        int $currentPeriod,
        int $previousPeriod
    ): array {
        $current = DB::table('measurement_audit_log')
            ->where('id_satker', $satkerCode)
            ->where('id_periode', $currentPeriod)
            ->where('id_indikator', $indicatorId)
            ->orderByDesc('changed_at')
            ->first();

        $previous = DB::table('measurement_audit_log')
            ->where('id_satker', $satkerCode)
            ->where('id_periode', $previousPeriod)
            ->where('id_indikator', $indicatorId)
            ->orderByDesc('changed_at')
            ->first();

        $trend = 'stable';
        $difference = 0;

        if ($current && $previous) {
            $difference = $current->nilai_baru - $previous->nilai_baru;
            $trend = $difference > 0 ? 'increase' : ($difference < 0 ? 'decrease' : 'stable');
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'difference' => $difference,
            'trend' => $trend,
            'percentage_change' => $previous && $previous->nilai_baru > 0
                ? round(($difference / $previous->nilai_baru) * 100, 2)
                : null
        ];
    }
}
