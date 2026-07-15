<?php

return [
    'session_timeout_minutes' => (int) env('KFS_AUTH_SESSION_TIMEOUT_MINUTES', 30),
    'two_factor_enabled' => (bool) env('KFS_AUTH_TWO_FACTOR_ENABLED', false),
    'default_ess_password' => env('KFS_DEFAULT_ESS_PASSWORD', 'KfsEss@2026'),
    'ess_email_domain' => env('KFS_ESS_EMAIL_DOMAIN', 'kenyaforestservice.org'),
    'allowed_login_roles' => array_filter(array_map('trim', explode(',', env(
        'KFS_AUTH_ALLOWED_LOGIN_ROLES',
        'super-admin,hr-admin,hr-manager,hr-payroll-operator,hr-director,hr-officer,payroll-manager,station-manager,employee'
    )))),
    'post_login_routes' => [
        'super-admin' => 'dashboard',
        'hr-admin' => 'dashboard',
        'hr-manager' => 'dashboard',
        'hr-payroll-operator' => 'dashboard',
        'hr-director' => 'dashboard',
        'hr-officer' => 'dashboard',
        'payroll-manager' => 'dashboard',
        'station-manager' => 'dashboard',
        'employee' => 'ess.dashboard',
    ],
];
