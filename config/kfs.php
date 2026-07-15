<?php

return [
    'employers' => [
        'KFS',
        'GZDSP PHASE II',
        'GLOBAL ENVIRONMENT FACILITY (GEF-7)',
        'NTGRP',
    ],

    'payroll_memos' => [
        'from' => env('KFS_PAYROLL_MEMO_FROM', 'DEPUTY DIRECTOR, HRM & DEVELOPMENT'),
        'prepared_by' => env('KFS_PAYROLL_MEMO_PREPARED_BY', 'P.L TIALAL'),
        'initials' => env('KFS_PAYROLL_MEMO_INITIALS', 'PK/vvm'),
        'employer_nssf_monthly' => 1080,
        'employer_housing_levy_rate' => 0.015,
        'employer_nita_monthly' => 50,
        'employers' => [
            'KFS' => [
                'ref_no' => 'HRA/4/KFS',
                'through' => null,
                'description' => 'contractual employees',
                'subject_suffix' => 'CONTRACT',
                'enclosure' => 'Bank details',
            ],
            'GZDSP PHASE II' => [
                'ref_no' => 'GZDSP/KFS',
                'through' => 'PROJECT MANAGER, GZDSP PHASE II',
                'description' => 'contractual employees attached to GZDSP II Project',
                'subject_suffix' => 'CONTRACT AT GZDSP II',
                'enclosure' => null,
            ],
            'GLOBAL ENVIRONMENT FACILITY (GEF-7)' => [
                'ref_no' => 'GEF/7/KFS',
                'through' => 'PROJECT MANAGER, GLOBAL ENVIRONMENT FACILITY (GEF-7)',
                'description' => 'officers attached to Global Environment Facility (GEF-7)',
                'subject_suffix' => 'CONTRACT',
                'enclosure' => null,
            ],
            'NTGRP' => [
                'ref_no' => 'NTGRP/KFS',
                'through' => 'PROJECT MANAGER, NTGRP',
                'description' => 'officers attached to National Tree Growing and Restoration Programme (NTGRP) Project',
                'subject_suffix' => 'CONTRACT',
                'enclosure' => null,
            ],
        ],
    ],
];
