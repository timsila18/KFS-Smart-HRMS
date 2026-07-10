<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeNextOfKin extends Model { use HasAuditColumns, SoftDeletes; protected $table = 'employee_next_of_kin'; protected $fillable = ['employee_id','full_name','relationship','phone','email','address','is_primary']; protected function casts(): array { return ['is_primary'=>'boolean']; } }
