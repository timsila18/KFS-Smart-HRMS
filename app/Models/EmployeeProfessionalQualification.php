<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeProfessionalQualification extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','body_name','membership_number','qualification','expires_at']; protected function casts(): array { return ['expires_at'=>'date']; } }
