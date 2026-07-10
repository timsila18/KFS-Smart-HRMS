<?php

namespace App\Policies;

use App\Models\PayCode;
use App\Models\User;

class PayCodePolicy
{
    public function viewAny(User $user): bool { return $user->can('payroll.view'); }
    public function view(User $user, PayCode $payCode): bool { return $user->can('payroll.view'); }
    public function create(User $user): bool { return $user->can('payroll.create'); }
    public function update(User $user, PayCode $payCode): bool { return $user->can('payroll.update'); }
    public function delete(User $user, PayCode $payCode): bool { return $user->can('payroll.delete'); }
}
