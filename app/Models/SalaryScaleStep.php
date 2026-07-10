<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SalaryScaleStep extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['salary_scale_id','step_number','basic_salary','house_allowance']; }
