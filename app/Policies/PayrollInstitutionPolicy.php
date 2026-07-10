<?php

namespace App\Policies;

use App\Models\PayrollInstitution;
use App\Models\User;

class PayrollInstitutionPolicy
{
    public function viewAny(User $user): bool { return $user->can('payroll.view'); }
    public function view(User $user, PayrollInstitution $institution): bool { return $user->can('payroll.view'); }
    public function create(User $user): bool { return $user->can('payroll.create'); }
    public function update(User $user, PayrollInstitution $institution): bool { return $user->can('payroll.update'); }
    public function delete(User $user, PayrollInstitution $institution): bool { return $user->can('payroll.delete'); }
}
