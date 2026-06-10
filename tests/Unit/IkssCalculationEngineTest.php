<?php

namespace Tests\Unit;

use App\Services\IkssCalculationEngine;
use PHPUnit\Framework\TestCase;

class IkssCalculationEngineTest extends TestCase
{
    private IkssCalculationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new IkssCalculationEngine;
    }

    public function test_it_calculates_supported_aggregations(): void
    {
        $this->assertSame(60.0, $this->engine->calculate('sum', [10, 20, 30]));
        $this->assertSame(20.0, $this->engine->calculate('average', [10, 20, 30]));
        $this->assertSame(10.0, $this->engine->calculate('min', [10, 20, 30]));
        $this->assertSame(30.0, $this->engine->calculate('max', [10, 20, 30]));
        $this->assertSame(30.0, $this->engine->calculate('latest', [10, 20, 30]));
    }

    public function test_it_calculates_weighted_average(): void
    {
        $value = $this->engine->calculate('weighted_average', [
            ['value' => 80, 'weight' => 1],
            ['value' => 100, 'weight' => 3],
        ]);

        $this->assertSame(95.0, $value);
    }

    public function test_it_calculates_ratio_by_dependency_role(): void
    {
        $value = $this->engine->calculate('ratio', [
            ['value' => 8, 'role' => 'numerator'],
            ['value' => 10, 'role' => 'denominator'],
        ]);

        $this->assertSame(80.0, $value);
    }

    public function test_it_calculates_regional_ratio_from_summed_components(): void
    {
        $value = $this->engine->calculate('ratio', [
            ['value' => 80, 'role' => 'numerator'],
            ['value' => 45, 'role' => 'numerator'],
            ['value' => 100, 'role' => 'denominator'],
            ['value' => 50, 'role' => 'denominator'],
        ], [], 2);

        $this->assertSame(83.33, $value);
    }

    public function test_it_returns_null_for_missing_values_or_zero_denominator(): void
    {
        $this->assertNull($this->engine->calculate('sum', []));
        $this->assertNull($this->engine->calculate('ratio', [
            ['value' => 10, 'role' => 'denominator'],
        ]));
        $this->assertNull($this->engine->calculate('ratio', [
            ['value' => 8, 'role' => 'numerator'],
        ]));
        $this->assertNull($this->engine->calculate('ratio', [
            ['value' => 8, 'role' => 'numerator'],
            ['value' => 0, 'role' => 'denominator'],
        ]));
    }
}
