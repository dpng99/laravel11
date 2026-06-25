<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeasurementAuditController extends Controller
{
    /**
     * GET /api/audit/log
     * Get audit log dengan filter
     */
    public function getLog(Request $request)
    {
        $validated = $request->validate([
            'indikator_id' => 'nullable|string',
            'satker_id' => 'nullable|string',
            'action' => 'nullable|in:evaluated,configured,deprecated,activated',
            'period' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'limit' => 'integer|min:1|max:1000',
            'page' => 'integer|min:1'
        ]);

        $query = DB::table('measurement_audit_log');

        if ($validated['indikator_id'] ?? null) {
            $query->where('indikator_id', $validated['indikator_id']);
        }

        if ($validated['satker_id'] ?? null) {
            $query->where('satker_id', $validated['satker_id']);
        }

        if ($validated['action'] ?? null) {
            $query->where('action', $validated['action']);
        }

        if ($validated['period'] ?? null) {
            $query->where('measurement_period', $validated['period']);
        }

        if ($validated['start_date'] ?? null) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }

        if ($validated['end_date'] ?? null) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }

        $limit = $validated['limit'] ?? 50;
        $total = $query->count();

        $logs = $query->orderByDesc('created_at')
            ->paginate($limit, ['*'], 'page', $validated['page'] ?? 1);

        return response()->json([
            'total' => $total,
            'logs' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage()
            ]
        ]);
    }

    /**
     * GET /api/audit/changes/{indikator_id}
     * Trace perubahan untuk satu indikator
     */
    public function traceChanges($indikatorId, Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = DB::table('measurement_audit_log')
            ->where('indikator_id', $indikatorId)
            ->where('action', 'evaluated');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $changes = $query->orderByDesc('created_at')->get();

        // Group by period untuk analisis trend
        $grouped = $changes->groupBy('measurement_period');

        return response()->json([
            'indikator_id' => $indikatorId,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'total_changes' => $changes->count(),
            'by_period' => $grouped->map(fn($items) => [
                'count' => $items->count(),
                'changes' => $items->toArray()
            ])
        ]);
    }

    /**
     * GET /api/audit/status-transitions/{indikator_id}
     * Laporan perubahan status
     */
    public function statusTransitions($indikatorId, Request $request)
    {
        $period = $request->input('period');

        $query = DB::table('measurement_audit_log')
            ->where('indikator_id', $indikatorId)
            ->where('action', 'evaluated')
            ->whereNotNull('old_status')
            ->whereNotNull('new_status');

        if ($period) {
            $query->where('measurement_period', $period);
        }

        $transitions = $query->orderBy('created_at')->get();

        $summary = [];
        foreach ($transitions as $transition) {
            $key = "{$transition->old_status} → {$transition->new_status}";
            $summary[$key] = ($summary[$key] ?? 0) + 1;
        }

        return response()->json([
            'indikator_id' => $indikatorId,
            'period' => $period,
            'total_transitions' => $transitions->count(),
            'transition_summary' => $summary,
            'details' => $transitions->toArray()
        ]);
    }

    /**
     * GET /api/audit/user-activity
     * Activity log untuk user tertentu
     */
    public function userActivity(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|string',
            'action' => 'nullable|string',
            'limit' => 'integer|min:1|max:500'
        ]);

        $query = DB::table('measurement_audit_log')
            ->where('created_by', $validated['user_id']);

        if ($validated['action'] ?? null) {
            $query->where('action', $validated['action']);
        }

        $activity = $query->orderByDesc('created_at')
            ->limit($validated['limit'] ?? 50)
            ->get();

        return response()->json([
            'user_id' => $validated['user_id'],
            'total_actions' => $activity->count(),
            'activity' => $activity
        ]);
    }

    /**
     * GET /api/audit/compliance-report
     * Laporan kepatuhan terhadap threshold
     */
    public function complianceReport(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|integer',
            'level' => 'nullable|integer',
            'satker_id' => 'nullable|string'
        ]);

        $query = DB::table('measurement_audit_log')
            ->where('measurement_period', $validated['period'])
            ->where('action', 'evaluated');

        if ($validated['level'] ?? null) {
            $query->where('level', $validated['level']);
        }

        if ($validated['satker_id'] ?? null) {
            $query->where('satker_id', $validated['satker_id']);
        }

        $measurements = $query->get();

        $statusCounts = $measurements->groupBy('new_status')->map->count();

        return response()->json([
            'period' => $validated['period'],
            'level' => $validated['level'],
            'satker_id' => $validated['satker_id'],
            'total_measurements' => $measurements->count(),
            'status_breakdown' => $statusCounts,
            'compliance_rate' => round(
                ($statusCounts->get('Baik', 0) + $statusCounts->get('Excellent', 0)) / 
                max($measurements->count(), 1) * 100, 2
            ) . '%'
        ]);
    }

    /**
     * POST /api/audit/export
     * Export audit log dalam format CSV
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'filters' => 'nullable|array',
            'format' => 'in:csv,json'
        ]);

        $query = DB::table('measurement_audit_log');

        if ($filters = $validated['filters'] ?? null) {
            if ($filters['indikator_id'] ?? null) {
                $query->where('indikator_id', $filters['indikator_id']);
            }
            if ($filters['period'] ?? null) {
                $query->where('measurement_period', $filters['period']);
            }
        }

        $data = $query->orderByDesc('created_at')->get();

        if (($validated['format'] ?? 'json') === 'csv') {
            $csv = $this->convertToCsv($data);
            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="audit-log.csv"'
            ]);
        }

        return response()->json([
            'total_records' => $data->count(),
            'data' => $data
        ]);
    }

    /**
     * Helper untuk convert ke CSV
     */
    private function convertToCsv($data)
    {
        $csv = "ID,Indikator ID,Action,Old Value,New Value,Old Status,New Status,Period,Created By,Created At\n";

        foreach ($data as $row) {
            $csv .= implode(',', [
                $row->id,
                $row->indikator_id,
                $row->action,
                $row->old_value ?? '',
                $row->new_value ?? '',
                $row->old_status ?? '',
                $row->new_status ?? '',
                $row->measurement_period,
                $row->created_by,
                $row->created_at
            ]) . "\n";
        }

        return $csv;
    }
}
