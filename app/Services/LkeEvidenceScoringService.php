<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service untuk penilaian kualitas bukti dukung dalam LKE
 * Menggantikan status binary (ada/tidak ada) dengan scoring berkala
 */
class LkeEvidenceScoringService
{
    /**
     * Score bukti dukung berdasarkan multiple dimensi kualitas
     * Dimensi: availability, verification, recency, completeness, digital_format
     */
    public function scoreEvidence(array $evidence): array
    {
        $score = 0;
        $breakdown = [];

        // 1. Availability (0-40 poin)
        if (!empty($evidence['link_bukti_dukung']) || !empty($evidence['id_filename'])) {
            $score += 40;
            $breakdown['availability'] = [
                'score' => 40,
                'weight' => 0.40,
                'notes' => 'Bukti tersedia'
            ];
        } else {
            $breakdown['availability'] = [
                'score' => 0,
                'weight' => 0.40,
                'notes' => 'Bukti tidak tersedia'
            ];
        }

        // 2. Verification Status (0-30 poin)
        $verificationScore = 0;
        if (!empty($evidence['is_verified'])) {
            $verificationScore = 30;
            $breakdown['verification'] = [
                'score' => 30,
                'weight' => 0.30,
                'notes' => 'Sudah diverifikasi'
            ];
        } elseif (!empty($evidence['is_manual'])) {
            $verificationScore = 20; // Manual upload tapi belum verif
            $breakdown['verification'] = [
                'score' => 20,
                'weight' => 0.30,
                'notes' => 'Manual upload, belum verifikasi'
            ];
        } else {
            $breakdown['verification'] = [
                'score' => 0,
                'weight' => 0.30,
                'notes' => 'Belum diverifikasi'
            ];
        }
        $score += $verificationScore;

        // 3. Recency (0-15 poin) - Berapa lama dokumen di-upload
        $recencyScore = 0;
        if (!empty($evidence['id_tglupload'])) {
            $uploadDate = Carbon::parse($evidence['id_tglupload']);
            $daysOld = now()->diffInDays($uploadDate);

            if ($daysOld <= 30) {
                $recencyScore = 15;
                $breakdown['recency'] = [
                    'score' => 15,
                    'weight' => 0.15,
                    'notes' => "Sangat baru (${daysOld} hari lalu)"
                ];
            } elseif ($daysOld <= 90) {
                $recencyScore = 10;
                $breakdown['recency'] = [
                    'score' => 10,
                    'weight' => 0.15,
                    'notes' => "Cukup baru (${daysOld} hari lalu)"
                ];
            } elseif ($daysOld <= 180) {
                $recencyScore = 5;
                $breakdown['recency'] = [
                    'score' => 5,
                    'weight' => 0.15,
                    'notes' => "Mulai usang (${daysOld} hari lalu)"
                ];
            } else {
                $breakdown['recency'] = [
                    'score' => 0,
                    'weight' => 0.15,
                    'notes' => "Sangat usang (${daysOld} hari lalu)"
                ];
            }
        } else {
            $breakdown['recency'] = [
                'score' => 0,
                'weight' => 0.15,
                'notes' => 'Tanggal upload tidak diketahui'
            ];
        }
        $score += $recencyScore;

        // 4. Completeness (0-10 poin) - Apakah bukti lengkap
        $completenessScore = 0;
        if (!empty($evidence['is_complete'])) {
            $completenessScore = 10;
            $breakdown['completeness'] = [
                'score' => 10,
                'weight' => 0.10,
                'notes' => 'Bukti lengkap'
            ];
        } else {
            $breakdown['completeness'] = [
                'score' => 0,
                'weight' => 0.10,
                'notes' => 'Bukti tidak lengkap atau belum diketahui'
            ];
        }
        $score += $completenessScore;

        // 5. Digital Format (0-5 poin) - Apakah dalam format digital
        $digitalScore = 0;
        if (!empty($evidence['link_bukti_dukung'])) {
            // Jika ada link, asumsikan digital
            $digitalScore = 5;
            $breakdown['digital_format'] = [
                'score' => 5,
                'weight' => 0.05,
                'notes' => 'Format digital'
            ];
        } else {
            $breakdown['digital_format'] = [
                'score' => 0,
                'weight' => 0.05,
                'notes' => 'Format fisik atau tidak digital'
            ];
        }
        $score += $digitalScore;

        // Determine quality level
        $qualityLevel = match (true) {
            $score >= 90 => 'excellent',
            $score >= 75 => 'good',
            $score >= 60 => 'fair',
            $score >= 40 => 'poor',
            default => 'critical'
        };

        return [
            'total_score' => $score,
            'percentage' => min(100, round($score / 100 * 100, 2)),
            'quality_level' => $qualityLevel,
            'breakdown' => $breakdown,
            'recommendation' => $this->getScoreRecommendation($score, $qualityLevel)
        ];
    }

    /**
     * Recommendation berdasarkan score
     */
    private function getScoreRecommendation(int $score, string $level): string
    {
        return match ($level) {
            'excellent' => 'Bukti dukung sangat lengkap dan terdokumentasi dengan baik',
            'good' => 'Bukti dukung sudah memadai, dapat dipertahankan',
            'fair' => 'Bukti dukung perlu ditingkatkan kualitasnya',
            'poor' => 'Bukti dukung sangat kurang, perlu penambahan dokumen',
            'critical' => 'URGENT: Bukti dukung sangat kritis, segera perbaiki'
        };
    }

    /**
     * Score seluruh kriteria berdasarkan bukti-buktinya
     */
    public function criteriaComplianceScore(string $criteriaCode, string $satkerCode): array
    {
        $buktiList = DB::table('bukti_dukung')
            ->where('id_kriteria', $criteriaCode)
            ->where('id_satker', $satkerCode)
            ->get();

        if ($buktiList->isEmpty()) {
            return [
                'criteria_code' => $criteriaCode,
                'compliance_score' => 0,
                'level' => 'critical',
                'evidence_count' => 0,
                'verified_count' => 0,
                'average_evidence_quality' => 0,
                'recommendation' => 'Tidak ada bukti dukung sama sekali'
            ];
        }

        $scores = [];
        $verifiedCount = 0;

        foreach ($buktiList as $bukti) {
            $scored = $this->scoreEvidence((array)$bukti);
            $scores[] = $scored;

            if (!empty($bukti->is_verified)) {
                $verifiedCount++;
            }
        }

        $avgScore = collect($scores)->avg('total_score');
        $complianceLevel = match (true) {
            $avgScore >= 90 => 'excellent',
            $avgScore >= 75 => 'good',
            $avgScore >= 60 => 'fair',
            $avgScore >= 40 => 'poor',
            default => 'critical'
        };

        return [
            'criteria_code' => $criteriaCode,
            'compliance_score' => round($avgScore, 2),
            'level' => $complianceLevel,
            'evidence_count' => $buktiList->count(),
            'verified_count' => $verifiedCount,
            'average_evidence_quality' => round($avgScore / 100 * 100, 2) . '%',
            'evidence_scores' => $scores,
            'recommendation' => $this->getCriteriaRecommendation($avgScore)
        ];
    }

    /**
     * Recommendation untuk kriteria
     */
    private function getCriteriaRecommendation(float $avgScore): string
    {
        return match (true) {
            $avgScore >= 90 => 'Kriteria sudah terpenuhi dengan sangat baik',
            $avgScore >= 75 => 'Kriteria terpenuhi dengan baik',
            $avgScore >= 60 => 'Kriteria perlu ditingkatkan',
            $avgScore >= 40 => 'Kriteria sangat kurang terpenuhi',
            default => 'URGENT: Kriteria tidak terpenuhi, perlu tindakan segera'
        };
    }

    /**
     * Score untuk sub-komponen (gabung semua kriterianya)
     */
    public function subkomponenComplianceScore(string $subkomponenCode, string $satkerCode): array
    {
        $kriterias = DB::table('lke_kriteria')
            ->where('subkomponen_id', $subkomponenCode)
            ->pluck('kode');

        if ($kriterias->isEmpty()) {
            return [
                'subkomponen_code' => $subkomponenCode,
                'average_score' => 0,
                'count' => 0,
                'excellent_count' => 0,
                'good_count' => 0,
                'fair_count' => 0,
                'poor_count' => 0,
                'critical_count' => 0
            ];
        }

        $criteriaScores = [];
        $levelCounts = [
            'excellent' => 0,
            'good' => 0,
            'fair' => 0,
            'poor' => 0,
            'critical' => 0
        ];

        foreach ($kriterias as $kriteria) {
            $score = $this->criteriaComplianceScore($kriteria, $satkerCode);
            $criteriaScores[] = $score;
            $levelCounts[$score['level']]++;
        }

        $avgScore = collect($criteriaScores)->avg('compliance_score');

        return [
            'subkomponen_code' => $subkomponenCode,
            'average_score' => round($avgScore, 2),
            'percentage' => round($avgScore / 100 * 100, 2) . '%',
            'count' => $kriterias->count(),
            'excellent_count' => $levelCounts['excellent'],
            'good_count' => $levelCounts['good'],
            'fair_count' => $levelCounts['fair'],
            'poor_count' => $levelCounts['poor'],
            'critical_count' => $levelCounts['critical'],
            'criteria_scores' => $criteriaScores,
            'achievement_rate' => round(
                ($levelCounts['excellent'] + $levelCounts['good']) / $kriterias->count() * 100,
                2
            ) . '%'
        ];
    }

    /**
     * Score untuk seluruh komponen
     */
    public function komponenComplianceScore(int $komponenId, string $satkerCode): array
    {
        $subkomponens = DB::table('lke_subkomponen')
            ->where('komponen_id', $komponenId)
            ->pluck('kode');

        if ($subkomponens->isEmpty()) {
            return [
                'komponen_id' => $komponenId,
                'average_score' => 0,
                'count' => 0
            ];
        }

        $subkomponenScores = [];
        foreach ($subkomponens as $subkomponen) {
            $score = $this->subkomponenComplianceScore($subkomponen, $satkerCode);
            $subkomponenScores[] = $score;
        }

        $avgScore = collect($subkomponenScores)->avg('average_score');

        return [
            'komponen_id' => $komponenId,
            'average_score' => round($avgScore, 2),
            'percentage' => round($avgScore / 100 * 100, 2) . '%',
            'subkomponen_count' => $subkomponens->count(),
            'subkomponen_scores' => $subkomponenScores
        ];
    }

    /**
     * Comprehensive LKE compliance report untuk satu satker
     */
    public function generateComplianceReport(string $satkerCode): array
    {
        $komponens = DB::table('lke_komponen')->get();

        $komponenScores = [];
        $overallScores = [];

        foreach ($komponens as $komponen) {
            $score = $this->komponenComplianceScore($komponen->id, $satkerCode);
            $komponenScores[$komponen->id] = [
                'name' => $komponen->nama,
                ...$score
            ];
            $overallScores[] = $score['average_score'];
        }

        $avgOverall = collect($overallScores)->avg();

        return [
            'satker_code' => $satkerCode,
            'report_date' => now(),
            'overall_score' => round($avgOverall, 2),
            'overall_percentage' => round($avgOverall / 100 * 100, 2) . '%',
            'overall_level' => match (true) {
                $avgOverall >= 90 => 'excellent',
                $avgOverall >= 75 => 'good',
                $avgOverall >= 60 => 'fair',
                $avgOverall >= 40 => 'poor',
                default => 'critical'
            },
            'komponen_scores' => $komponenScores,
            'summary' => [
                'total_komponens' => $komponens->count(),
                'components_excellent' => collect($komponenScores)->filter(
                    fn($s) => $s['average_score'] >= 90
                )->count(),
                'components_good' => collect($komponenScores)->filter(
                    fn($s) => $s['average_score'] >= 75 && $s['average_score'] < 90
                )->count(),
                'components_fair' => collect($komponenScores)->filter(
                    fn($s) => $s['average_score'] >= 60 && $s['average_score'] < 75
                )->count(),
                'components_poor' => collect($komponenScores)->filter(
                    fn($s) => $s['average_score'] >= 40 && $s['average_score'] < 60
                )->count(),
                'components_critical' => collect($komponenScores)->filter(
                    fn($s) => $s['average_score'] < 40
                )->count(),
            ]
        ];
    }

    /**
     * Record evidence verification untuk audit trail
     */
    public function recordVerification(
        string $satkerCode,
        int $buktiId,
        string $criteriaCode,
        bool $isVerified,
        ?string $verificationNotes = null
    ): int {
        return DB::table('lke_verification_log')->insertGetId([
            'id_satker' => $satkerCode,
            'bukti_id' => $buktiId,
            'criteria_code' => $criteriaCode,
            'is_verified' => $isVerified,
            'verification_notes' => $verificationNotes,
            'verified_by' => auth()?->id() ?? 'system',
            'verified_at' => now()
        ]);
    }
}
