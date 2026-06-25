<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LkeEvidenceScoringService;
use Illuminate\Http\Request;

class LkeEvaluationController extends Controller
{
    public function __construct(private LkeEvidenceScoringService $service) {}

    /**
     * GET /api/lke/evidence/{bukti_id}
     * Score satu bukti dukung
     */
    public function scoreEvidence($buktiId)
    {
        $evidence = DB::table('bukti_dukung')
            ->where('id', $buktiId)
            ->first();

        if (!$evidence) {
            return response()->json(['error' => 'Bukti dukung tidak ditemukan'], 404);
        }

        $score = $this->service->scoreEvidence((array)$evidence);

        return response()->json([
            'bukti_id' => $buktiId,
            'score' => $score
        ]);
    }

    /**
     * GET /api/lke/criteria/{criteria_code}/satker/{satker_code}
     * Score compliance untuk satu kriteria
     */
    public function criteriaCompliance($criteriaCode, $satkerCode)
    {
        $compliance = $this->service->criteriaComplianceScore($criteriaCode, $satkerCode);

        return response()->json([
            'criteria_code' => $criteriaCode,
            'satker_code' => $satkerCode,
            'compliance' => $compliance
        ]);
    }

    /**
     * GET /api/lke/subkomponen/{subkomponen_code}/satker/{satker_code}
     * Score compliance untuk sub-komponen
     */
    public function subkomponenCompliance($subkomponenCode, $satkerCode)
    {
        $compliance = $this->service->subkomponenComplianceScore($subkomponenCode, $satkerCode);

        return response()->json([
            'subkomponen_code' => $subkomponenCode,
            'satker_code' => $satkerCode,
            'compliance' => $compliance
        ]);
    }

    /**
     * GET /api/lke/komponen/{komponen_id}/satker/{satker_code}
     * Score compliance untuk komponen
     */
    public function komponenCompliance($komponenId, $satkerCode)
    {
        $compliance = $this->service->komponenComplianceScore($komponenId, $satkerCode);

        return response()->json([
            'komponen_id' => $komponenId,
            'satker_code' => $satkerCode,
            'compliance' => $compliance
        ]);
    }

    /**
     * GET /api/lke/report/satker/{satker_code}
     * Generate comprehensive LKE compliance report
     */
    public function generateReport($satkerCode)
    {
        $report = $this->service->generateComplianceReport($satkerCode);

        return response()->json([
            'satker_code' => $satkerCode,
            'report' => $report
        ]);
    }

    /**
     * POST /api/lke/verify
     * Record bukti verification
     */
    public function recordVerification(Request $request)
    {
        $validated = $request->validate([
            'satker_code' => 'required|string',
            'bukti_id' => 'required|integer',
            'criteria_code' => 'required|string',
            'is_verified' => 'required|boolean',
            'verification_notes' => 'nullable|string'
        ]);

        $logId = $this->service->recordVerification(
            $validated['satker_code'],
            $validated['bukti_id'],
            $validated['criteria_code'],
            $validated['is_verified'],
            $validated['verification_notes'] ?? null
        );

        return response()->json([
            'message' => 'Verifikasi bukti berhasil dicatat',
            'log_id' => $logId
        ], 201);
    }

    /**
     * GET /api/lke/audit-log/satker/{satker_code}
     * Get audit log untuk verifikasi
     */
    public function getAuditLog($satkerCode, Request $request)
    {
        $limit = $request->input('limit', 50);

        $logs = DB::table('lke_verification_log')
            ->where('id_satker', $satkerCode)
            ->orderByDesc('verified_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'satker_code' => $satkerCode,
            'audit_logs' => $logs,
            'count' => $logs->count()
        ]);
    }

    /**
     * GET /api/lke/summary
     * Summary LKE compliance untuk semua satker
     */
    public function summaryAll()
    {
        $satkers = DB::table('sinori_sakip_satker')
            ->select('id_satker', 'satkernama')
            ->where('hide', 0)
            ->get();

        $summary = [];

        foreach ($satkers as $satker) {
            $report = $this->service->generateComplianceReport($satker->id_satker);
            $summary[$satker->id_satker] = [
                'satker_name' => $satker->satkernama,
                'overall_score' => $report['overall_score'],
                'level' => $report['overall_level']
            ];
        }

        return response()->json([
            'summary' => $summary,
            'count' => count($summary)
        ]);
    }
}
