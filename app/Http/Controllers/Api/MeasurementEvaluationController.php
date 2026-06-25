<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MeasurementPrecisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeasurementEvaluationController extends Controller
{
    public function __construct(private MeasurementPrecisionService $service) {}

    /**
     * POST /api/measurement/evaluate
     * Evaluasi performa berdasarkan indikator dan nilai aktual
     */
    public function evaluate(Request $request)
    {
        $validated = $request->validate([
            'indikator_id' => 'required|string',
            'actual_value' => 'required|numeric',
            'measurement_period' => 'required|integer',
            'satker_id' => 'nullable|string',
            'level' => 'required|integer',
            'reason' => 'nullable|string'
        ]);

        $result = $this->service->evaluatePerformance(
            $validated['indikator_id'],
            $validated['actual_value'],
            $validated['measurement_period'],
            $validated['satker_id'] ?? null,
            $validated['level'],
            auth()->id() ?? 'api',
            $validated['reason'] ?? 'API evaluation'
        );

        return response()->json([
            'indikator_id' => $validated['indikator_id'],
            'actual_value' => $validated['actual_value'],
            'status' => $result['status'],
            'score' => $result['score'],
            'message' => $result['message']
        ]);
    }

    /**
     * POST /api/measurement/batch-evaluate
     * Evaluasi multiple indikator sekaligus
     */
    public function batchEvaluate(Request $request)
    {
        $validated = $request->validate([
            'measurements' => 'required|array',
            'measurements.*.indikator_id' => 'required|string',
            'measurements.*.actual_value' => 'required|numeric',
            'measurements.*.satker_id' => 'nullable|string',
            'measurement_period' => 'required|integer',
            'level' => 'required|integer'
        ]);

        $results = [];
        $userId = auth()->id() ?? 'api';

        foreach ($validated['measurements'] as $measurement) {
            $result = $this->service->evaluatePerformance(
                $measurement['indikator_id'],
                $measurement['actual_value'],
                $validated['measurement_period'],
                $measurement['satker_id'] ?? null,
                $validated['level'],
                $userId,
                'Batch evaluation'
            );

            $results[] = [
                'indikator_id' => $measurement['indikator_id'],
                'actual_value' => $measurement['actual_value'],
                'status' => $result['status'],
                'score' => $result['score']
            ];
        }

        return response()->json([
            'processed' => count($results),
            'results' => $results
        ], 201);
    }

    /**
     * GET /api/measurement/performance/{indikator_id}
     * Ambil history performa satu indikator
     */
    public function getPerformanceHistory($indikatorId, Request $request)
    {
        $limit = $request->input('limit', 24);
        $satkerCode = $request->input('satker_code');

        $query = DB::table('measurement_audit_log')
            ->where('indikator_id', $indikatorId)
            ->where('action', 'evaluated');

        if ($satkerCode) {
            $query->where('satker_id', $satkerCode);
        }

        $history = $query->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'indikator_id' => $indikatorId,
            'satker_code' => $satkerCode,
            'history' => $history,
            'count' => $history->count()
        ]);
    }

    /**
     * GET /api/measurement/weighted-average/{level}
     * Hitung weighted average untuk satu level
     */
    public function calculateWeightedAverage($level, Request $request)
    {
        $validated = $request->validate([
            'satker_id' => 'nullable|string',
            'indicators' => 'required|array',
            'indicators.*.id' => 'required|string',
            'indicators.*.value' => 'required|numeric'
        ]);

        $result = $this->service->calculateWeightedAverage(
            $validated['indicators'],
            $level,
            $validated['satker_id'] ?? null
        );

        return response()->json([
            'level' => $level,
            'satker_id' => $validated['satker_id'],
            'weighted_average' => $result['average'],
            'total_weight' => $result['total_weight'],
            'indicators_count' => count($validated['indicators'])
        ]);
    }

    /**
     * GET /api/measurement/comparison/{indikator_id}
     * Compare performa antar periode
     */
    public function periodComparison($indikatorId, Request $request)
    {
        $validated = $request->validate([
            'satker_id' => 'nullable|string',
            'current_period' => 'required|integer',
            'previous_periods' => 'required|array'
        ]);

        $comparison = $this->service->getPeriodComparison(
            $indikatorId,
            $validated['current_period'],
            $validated['satker_id'] ?? null,
            $validated['previous_periods']
        );

        return response()->json([
            'indikator_id' => $indikatorId,
            'comparison' => $comparison
        ]);
    }

    /**
     * GET /api/measurement/audit-log/{indikator_id}
     * Detailed audit log untuk satu indikator
     */
    public function getAuditLog($indikatorId, Request $request)
    {
        $limit = $request->input('limit', 100);
        $action = $request->input('action');

        $query = DB::table('measurement_audit_log')
            ->where('indikator_id', $indikatorId);

        if ($action) {
            $query->where('action', $action);
        }

        $logs = $query->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'indikator_id' => $indikatorId,
            'audit_logs' => $logs,
            'count' => $logs->count()
        ]);
    }

    /**
     * GET /api/measurement/status/{indikator_id}
     * Get current status dari satu indikator
     */
    public function getStatus($indikatorId, Request $request)
    {
        $satkerCode = $request->input('satker_code');
        $period = $request->input('period', date('Y'));

        $measurement = DB::table('measurement_audit_log')
            ->where('indikator_id', $indikatorId)
            ->where('measurement_period', $period)
            ->where('action', 'evaluated');

        if ($satkerCode) {
            $measurement->where('satker_id', $satkerCode);
        }

        $latest = $measurement->orderByDesc('created_at')
            ->first();

        if (!$latest) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'indikator_id' => $indikatorId,
            'satker_code' => $satkerCode,
            'period' => $period,
            'current_value' => $latest->new_value,
            'current_status' => $latest->new_status,
            'score' => $latest->score,
            'last_updated' => $latest->created_at
        ]);
    }
}
