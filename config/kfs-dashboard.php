<?php

return [
    'contract_expiry_window_days' => (int) env('KFS_DASHBOARD_CONTRACT_EXPIRY_WINDOW_DAYS', 90),
    'summary_cache_seconds' => (int) env('KFS_DASHBOARD_SUMMARY_CACHE_SECONDS', 300),
];
