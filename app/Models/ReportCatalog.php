<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ReportCatalog extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['code','name','module','parameters_schema','is_active','is_schedulable','schedule_frequency','schedule_recipients','next_run_at']; protected function casts(): array { return ['parameters_schema'=>'array','is_active'=>'boolean','is_schedulable'=>'boolean','schedule_recipients'=>'array','next_run_at'=>'datetime']; } public function getRouteKeyName(): string { return 'code'; } }
