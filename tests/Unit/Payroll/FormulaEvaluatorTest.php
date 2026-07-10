<?php

namespace Tests\Unit\Payroll;

use App\Services\Payroll\FormulaEvaluator;
use PHPUnit\Framework\TestCase;

class FormulaEvaluatorTest extends TestCase
{
    public function test_it_evaluates_configured_formula_with_variables(): void
    {
        $value = (new FormulaEvaluator())->evaluate(
            '(basic_salary + house_allowance) * rate / 100',
            ['basic_salary' => 100000, 'house_allowance' => 20000, 'rate' => 7.5]
        );

        $this->assertSame(9000.0, $value);
    }

    public function test_unknown_variables_resolve_to_zero(): void
    {
        $value = (new FormulaEvaluator())->evaluate('unknown + 100', []);

        $this->assertSame(100.0, $value);
    }

    public function test_it_does_not_round_fractional_results(): void
    {
        $value = (new FormulaEvaluator())->evaluate('1000 / 3', []);

        $this->assertGreaterThan(333.333333333, $value);
        $this->assertLessThan(333.333333334, $value);
    }
}
