<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeIdentification extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','id_type','id_number','issued_at','expires_at']; protected function casts(): array { return ['issued_at'=>'date','expires_at'=>'date']; } }
