<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LkeEvidenceScoringService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LkeEvidenceScoringServiceTest extends TestCase
{
    private LkeEvidenceScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LkeEvidenceScoringService();

        $this->artisan('migrate --env=testing');
        $this->artisan('db:seed --class=MeasurementPrecisionSeeder --env=testing');
    }

    /**
     * Test scoreEvidence dengan bukti lengkap
     */
    public function test_score_evidence_complete()
    {
        $evidence = [
            'id' => 1,
            'id_satker' => 'SATKER_001',
            'nama_dokumen' => 'LKJIP 2024',
            'file_path' => '/storage/documents/lkjip_2024.pdf',
            'is_digital' => true,
            'is_verified' => true,
            'verified_by' => 'admin',
            'verified_at' => Carbon::now(),
            'quality_score' => 90,
            'created_at' => Carbon::now(),
        ];

        $score = $this->service->scoreEvidence($evidence);

        $this->assertIsArray($score);
        $this->assertArrayHasKey('total_score', $score);
        $this->assertArrayHasKey('dimensions', $score);
        $this->assertGreaterThan(0, $score['total_score']);
    }

    /**
     * Test scoreEvidence dengan bukti minimal
     */
    public function test_score_evidence_minimal()
    {
        $evidence = [
            'id' => 2,
            'id_satker' => 'SATKER_002',
            'nama_dokumen' => 'Bukti Dukung',
            'file_path' => null,
            'is_digital' => false,
            'is_verified' => false,
            'quality_score' => 0,
        ];

        $score = $this->service->scoreEvidence($evidence);

        $this->assertIsArray($score);
        $this->assertArrayHasKey('total_score', $score);
        $this->assertLessThan(50, $score['total_score']);
    }

    /**
     * Test criteriaComplianceScore
     */
    public function test_criteria_compliance_score()
    {
        $compliance = $this->service->criteriaComplianceScore('CRITERIA_001', 'SATKER_001');

        $this->assertIsArray($compliance);
        $this->assertArrayHasKey('overall_score', $compliance);
        $this->assertArrayHasKey('compliance_status', $compliance);
    }

    /**
     * Test subkomponenComplianceScore
     */
    public function test_subkomponen_compliance_score()
    {
        $compliance = $this->service->subkomponenComplianceScore('SUBKOMP_001', 'SATKER_001');

        $this->assertIsArray($compliance);
        $this->assertArrayHasKey('overall_score', $compliance);
        $this->assertArrayHasKey('criteria_scores', $compliance);
    }

    /**
     * Test komponenComplianceScore
     */
    public function test_komponen_compliance_score()
    {
        $compliance = $this->service->komponenComplianceScore(1, 'SATKER_001');

        $this->assertIsArray($compliance);
        $this->assertArrayHasKey('overall_score', $compliance);
        $this->assertArrayHasKey('subkomponen_scores', $compliance);
    }

    /**
     * Test generateComplianceReport
     */
    public function test_generate_compliance_report()
    {
        $report = $this->service->generateComplianceReport('SATKER_001');

        $this->assertIsArray($report);
        $this->assertArrayHasKey('overall_score', $report);
        $this->assertArrayHasKey('overall_level', $report);
        $this->assertArrayHasKey('component_scores', $report);
        $this->assertArrayHasKey('recommendations', $report);
    }

    /**
     * Test recordVerification
     */
    public function test_record_verification()
    {
        $logId = $this->service->recordVerification(
            'SATKER_001',
            1,
            'CRITERIA_001',
            true,
            'Dokumen verified oleh admin'
        );

        $this->assertGreaterThan(0, $logId);

        $log = DB::table('lke_verification_log')
            ->where('id', $logId)
            ->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->is_verified);
    }

    /**
     * Test compliance score range
     */
    public function test_compliance_score_range()
    {
        $compliance = $this->service->criteriaComplianceScore('CRITERIA_001', 'SATKER_001');

        $this->assertGreaterThanOrEqual(0, $compliance['overall_score']);
        $this->assertLessThanOrEqual(100, $compliance['overall_score']);
    }

    /**
     * Test compliance level determination
     */
    public function test_compliance_level_determination()
    {
        $report = $this->service->generateComplianceReport('SATKER_001');

        $validLevels = ['Excellent', 'Good', 'Fair', 'Poor'];
        $this->assertContains($report['overall_level'], $validLevels);
    }

    /**
     * Test evidence scoring dimensions
     */
    public function test_evidence_scoring_dimensions()
    {
        $evidence = [
            'id' => 1,
            'nama_dokumen' => 'Test Document',
            'is_verified' => true,
            'quality_score' => 80,
            'is_digital' => true,
        ];

        $score = $this->service->scoreEvidence($evidence);

        $this->assertArrayHasKey('dimensions', $score);
        $dimensions = $score['dimensions'];

        // Check all 5 dimensions
        $expectedDimensions = ['availability', 'verification', 'recency', 'completeness', 'digital_format'];
        foreach ($expectedDimensions as $dim) {
            $this->assertArrayHasKey($dim, $dimensions);
            $this->assertGreaterThanOrEqual(0, $dimensions[$dim]);
            $this->assertLessThanOrEqual(100, $dimensions[$dim]);
        }
    }

    /**
     * Test multiple satker comparison
     */
    public function test_multiple_satker_comparison()
    {
        $satkers = ['SATKER_001', 'SATKER_002', 'SATKER_003'];
        $scores = [];

        foreach ($satkers as $satker) {
            $report = $this->service->generateComplianceReport($satker);
            $scores[$satker] = $report['overall_score'];
        }

        $this->assertEquals(3, count($scores));
        foreach ($scores as $score) {
            $this->assertGreaterThanOrEqual(0, $score);
            $this->assertLessThanOrEqual(100, $score);
        }
    }
}
