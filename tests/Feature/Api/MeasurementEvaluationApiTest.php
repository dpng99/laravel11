<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeasurementEvaluationApiTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate --env=testing');
        $this->artisan('db:seed --class=MeasurementPrecisionSeeder --env=testing');
    }

    /**
     * Test POST /api/v1/measurement/evaluate
     */
    public function test_evaluate_single_indicator()
    {
        $response = $this->postJson('/api/v1/measurement/evaluate', [
            'indikator_id' => 'IND_001',
            'actual_value' => 95,
            'measurement_period' => 2024,
            'level' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'indikator_id',
            'actual_value',
            'status',
            'score',
            'message'
        ]);
        $response->assertJson([
            'status' => 'Excellent',
            'actual_value' => 95,
        ]);
    }

    /**
     * Test POST /api/v1/measurement/batch-evaluate
     */
    public function test_batch_evaluate_multiple_indicators()
    {
        $response = $this->postJson('/api/v1/measurement/batch-evaluate', [
            'measurements' => [
                ['indikator_id' => 'IND_001', 'actual_value' => 100],
                ['indikator_id' => 'IND_002', 'actual_value' => 85],
                ['indikator_id' => 'IND_003', 'actual_value' => 70],
            ],
            'measurement_period' => 2024,
            'level' => 2,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'processed',
            'results'
        ]);
        $response->assertJson(['processed' => 3]);
    }

    /**
     * Test GET /api/v1/measurement/weighted-average/{level}
     */
    public function test_calculate_weighted_average()
    {
        $response = $this->getJson('/api/v1/measurement/weighted-average/2', [
            'indicators' => [
                ['id' => 'IND_001', 'value' => 100],
                ['id' => 'IND_002', 'value' => 85],
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'level',
            'weighted_average',
            'total_weight',
            'indicators_count'
        ]);
    }

    /**
     * Test GET /api/v1/measurement/performance/{indikator_id}
     */
    public function test_get_performance_history()
    {
        $response = $this->getJson('/api/v1/measurement/performance/IND_001');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'indikator_id',
            'history',
            'count'
        ]);
    }

    /**
     * Test GET /api/v1/measurement/status/{indikator_id}
     */
    public function test_get_status()
    {
        // First, evaluate to create a status
        $this->postJson('/api/v1/measurement/evaluate', [
            'indikator_id' => 'IND_001',
            'actual_value' => 90,
            'measurement_period' => 2024,
            'level' => 2,
        ]);

        $response = $this->getJson('/api/v1/measurement/status/IND_001', [
            'period' => 2024
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'indikator_id',
            'current_value',
            'current_status',
            'score',
            'last_updated'
        ]);
    }
}
