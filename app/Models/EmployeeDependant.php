<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeDependant extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','full_name','relationship','date_of_birth','is_beneficiary']; protected function casts(): array { return ['date_of_birth'=>'date','is_beneficiary'=>'boolean']; } }
