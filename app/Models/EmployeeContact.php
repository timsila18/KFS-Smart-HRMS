<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeContact extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','contact_type','value','is_primary']; protected function casts(): array { return ['is_primary'=>'boolean']; } }
