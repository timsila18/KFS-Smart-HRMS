<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeEducation extends Model { use HasAuditColumns, SoftDeletes; protected $table = 'employee_education'; protected $fillable = ['employee_id','institution','qualification','level','started_on','completed_on']; protected function casts(): array { return ['started_on'=>'date','completed_on'=>'date']; } }
