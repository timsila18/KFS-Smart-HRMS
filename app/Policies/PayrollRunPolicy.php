<?php
namespace App\Policies;
use App\Models\PayrollRun;
use App\Models\User;
class PayrollRunPolicy { public function viewAny(User $user): bool { return $user->can('payroll.view'); } public function view(User $user, PayrollRun $run): bool { return $user->can('payroll.view'); } public function update(User $user, PayrollRun $run): bool { return $user->can('payroll.update'); } public function approve(User $user, PayrollRun $run): bool { return $user->can('payroll.approve'); } }
