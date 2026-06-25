<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MeasurementPrecisionService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MeasurementPrecisionServiceTest extends TestCase
{
    private MeasurementPrecisionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MeasurementPrecisionService();

        // Jalankan migration untuk test
        $this->artisan('migrate --env=testing');
        $this->artisan('db:seed --class=MeasurementPrecisionSeeder --env=testing');
    }

    /**
     * Test evaluatePerformance dengan nilai excellent
     */
    public function test_evaluate_performance_excellent()
    {
        $result = $this->service->evaluatePerformance(
            'IND_001',
            100,
            2024,
            null,
            2,
            'test_user',
            'Test evaluation'
        );

        $this->assertEquals('Excellent', $result['status']);
        $this->assertEquals(100, $result['score']);
        $this->assertGreaterThan(0, $result['message']);
    }

    /**
     * Test evaluatePerformance dengan nilai good
     */
    public function test_evaluate_performance_good()
    {
        $result = $this->service->evaluatePerformance(
            'IND_001',
            85,
            2024,
            null,
            2,
            'test_user',
            'Test evaluation'
        );

        $this->assertEquals('Good', $result['status']);
        $this->assertGreaterThan(0, $result['score']);
    }

    /**
     * Test evaluatePerformance dengan nilai poor
     */
    public function test_evaluate_performance_poor()
    {
        $result = $this->service->evaluatePerformance(
            'IND_001',
            30,
            2024,
            null,
            2,
            'test_user',
            'Test evaluation'
        );

        $this->assertEquals('Poor', $result['status']);
        $this->assertLessThan(0, $result['score']);
    }

    /**
     * Test calculateWeightedAverage
     */
    public function test_calculate_weighted_average()
    {
        $indicators = [
            ['id' => 'IND_001', 'value' => 100],
            ['id' => 'IND_002', 'value' => 80],
            ['id' => 'IND_003', 'value' => 90],
        ];

        $result = $this->service->calculateWeightedAverage($indicators, 2);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('average', $result);
        $this->assertArrayHasKey('total_weight', $result);
        $this->assertGreaterThan(0, $result['average']);
    }

    /**
     * Test recordMeasurement dengan audit logging
     */
    public function test_record_measurement_creates_audit_log()
    {
        $this->service->recordMeasurement(
            'IND_001',
            95,
            'Excellent',
            2024,
            null,
            2,
            'test_user',
            'Test measurement'
        );

        $auditLog = DB::table('measurement_audit_log')
            ->where('indikator_id', 'IND_001')
            ->where('measurement_period', 2024)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('Excellent', $auditLog->new_status);
    }

    /**
     * Test getPeriodComparison
     */
    public function test_get_period_comparison()
    {
        // Record measurements untuk beberapa periode
        $this->service->recordMeasurement(
            'IND_001', 95, 'Excellent', 2024, null, 2, 'test', 'Test'
        );
        $this->service->recordMeasurement(
            'IND_001', 85, 'Good', 2023, null, 2, 'test', 'Test'
        );

        $comparison = $this->service->getPeriodComparison(
            'IND_001',
            2024,
            null,
            [2023, 2022, 2021]
        );

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('current_value', $comparison);
        $this->assertArrayHasKey('history', $comparison);
    }

    /**
     * Test validateConfiguration
     */
    public function test_validate_configuration_valid()
    {
        $config = [
            'excellent_min' => 100,
            'good_min' => 80,
            'fair_min' => 60,
            'poor_max' => 60,
        ];

        $validation = $this->service->validateConfiguration($config);

        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
    }

    /**
     * Test validateConfiguration dengan config invalid
     */
    public function test_validate_configuration_invalid()
    {
        $config = [
            'excellent_min' => 80,  // Harus lebih tinggi dari good_min
            'good_min' => 100,      // Harus lebih rendah dari excellent_min
            'fair_min' => 60,
            'poor_max' => 60,
        ];

        $validation = $this->service->validateConfiguration($config);

        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }

    /**
     * Test getThresholdConfig dengan satker-specific override
     */
    public function test_get_threshold_config_satker_priority()
    {
        // Insert satker-specific threshold
        DB::table('measurement_thresholds')->insert([
            'level_code' => '2',
            'satker_id' => 'SATKER_001',
            'indikator_id' => 'IND_001',
            'indikator_type' => 'kinerja',
            'excellent_min' => 95,
            'good_min' => 85,
            'fair_min' => 70,
            'poor_max' => 70,
            'weight' => 1.2,
            'status_excellent' => 'Excellent',
            'status_good' => 'Good',
            'status_fair' => 'Fair',
            'status_poor' => 'Poor',
            'effective_date' => Carbon::now(),
            'created_by' => 'test',
            'active' => true,
        ]);

        $config = $this->service->getThresholdConfig('IND_001', 2, 'SATKER_001');

        $this->assertEquals(95, $config['excellent_min']);
        $this->assertEquals('SATKER_001', $config['satker_id']);
    }

    /**
     * Test getMeasurementHistory
     */
    public function test_get_measurement_history()
    {
        // Insert some historical data
        DB::table('measurement_audit_log')->insert([
            'indikator_id' => 'IND_001',
            'action' => 'evaluated',
            'old_value' => 85,
            'new_value' => 90,
            'old_status' => 'Good',
            'new_status' => 'Excellent',
            'score' => 95,
            'level' => 2,
            'satker_id' => null,
            'measurement_period' => 2024,
            'reason' => 'Test',
            'created_by' => 'test',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $history = $this->service->getMeasurementHistory('IND_001', 12);

        $this->assertGreaterThan(0, count($history));
    }
}
