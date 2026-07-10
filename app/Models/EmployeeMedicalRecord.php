<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeMedicalRecord extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','blood_group','medical_scheme','medical_membership_number','allergies','conditions','disabilities','last_medical_exam_on','next_medical_exam_on','metadata']; protected function casts(): array { return ['last_medical_exam_on'=>'date','next_medical_exam_on'=>'date','metadata'=>'array']; } }
