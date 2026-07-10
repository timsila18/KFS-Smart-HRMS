<?php

return [
    'pay_code_types' => ['earning', 'deduction', 'employer_contribution'],
    'earning_groups' => ['salary', 'allowance', 'bonus', 'arrears', 'overtime', 'other'],
    'deduction_groups' => ['statutory', 'departmental', 'pension', 'welfare', 'sacco', 'loan', 'insurance', 'bank_loan', 'other'],
    'calculation_methods' => ['fixed', 'percentage_of_basic', 'percentage_of_gross', 'formula', 'manual'],
    'statutory_defaults' => [
        'nssf_monthly_amount' => env('PAYROLL_NSSF_MONTHLY_AMOUNT', '1080.000000000000000000'),
    ],
    'institution_types' => ['sacco', 'insurance', 'bank', 'welfare', 'government', 'pension', 'other'],
    'product_types' => ['loan', 'mortgage', 'car_loan', 'emergency_loan', 'insurance', 'sacco_contribution', 'welfare_contribution', 'recovery', 'other'],
];
