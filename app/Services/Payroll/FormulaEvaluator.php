<?php

namespace App\Services\Payroll;

use InvalidArgumentException;

class FormulaEvaluator
{
    /**
     * @param array<string, float|int> $variables
     */
    public function evaluate(string $expression, array $variables): float
    {
        $tokens = $this->tokenize($expression);
        $output = [];
        $operators = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = (float) $token;
                continue;
            }
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $token)) {
                $output[] = (float) ($variables[$token] ?? 0);
                continue;
            }
            if (isset($precedence[$token])) {
                while ($operators !== [] && end($operators) !== '(' && $precedence[end($operators)] >= $precedence[$token]) {
                    $output[] = array_pop($operators);
                }
                $operators[] = $token;
                continue;
            }
            if ($token === '(') {
                $operators[] = $token;
                continue;
            }
            if ($token === ')') {
                while ($operators !== [] && end($operators) !== '(') {
                    $output[] = array_pop($operators);
                }
                array_pop($operators);
            }
        }

        while ($operators !== []) {
            $output[] = array_pop($operators);
        }

        return $this->resolveRpn($output);
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $expression): array
    {
        preg_match_all('/[A-Za-z_][A-Za-z0-9_]*|\d+(?:\.\d+)?|[+\-*\/()]/', $expression, $matches);

        if (implode('', $matches[0]) !== preg_replace('/\s+/', '', $expression)) {
            throw new InvalidArgumentException('Formula contains unsupported tokens.');
        }

        return $matches[0];
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function resolveRpn(array $tokens): float
    {
        $stack = [];

        foreach ($tokens as $token) {
            if (is_float($token) || is_int($token)) {
                $stack[] = (float) $token;
                continue;
            }

            $right = array_pop($stack) ?? 0;
            $left = array_pop($stack) ?? 0;
            $stack[] = match ($token) {
                '+' => $left + $right,
                '-' => $left - $right,
                '*' => $left * $right,
                '/' => $right == 0.0 ? 0.0 : $left / $right,
                default => 0.0,
            };
        }

        return (float) ($stack[0] ?? 0);
    }
}
