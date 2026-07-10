<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeAddress extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','address_type','line_1','line_2','town','county','postal_code']; }
