<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeEmergencyContact extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','full_name','relationship','phone','email']; }
