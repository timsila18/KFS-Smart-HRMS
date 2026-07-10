<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('about:kfs', function (): void {
    $this->info('Kenya Forest Service HR & Payroll Management System');
});

Schedule::command('reports:run-scheduled')->hourly()->withoutOverlapping();
