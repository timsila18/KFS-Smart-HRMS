<?php

return [
    'session_timeout_minutes' => (int) env('KFS_AUTH_SESSION_TIMEOUT_MINUTES', 30),
    'two_factor_enabled' => (bool) env('KFS_AUTH_TWO_FACTOR_ENABLED', false),
    'allowed_login_roles' => array_filter(array_map('trim', explode(',', env(
        'KFS_AUTH_ALLOWED_LOGIN_ROLES',
        'super-admin,hr-director,hr-officer,payroll-manager,station-manager,employee'
    )))),
    'post_login_routes' => [
        'super-admin' => 'dashboard',
        'hr-director' => 'dashboard',
        'hr-officer' => 'dashboard',
        'payroll-manager' => 'dashboard',
        'station-manager' => 'dashboard',
        'employee' => 'ess.dashboard',
    ],
];
