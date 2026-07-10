<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeDocument extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','document_type','title','file_path','expires_at']; protected function casts(): array { return ['expires_at'=>'date']; } }
