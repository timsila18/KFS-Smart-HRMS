<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class ReportRun extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['report_catalog_id','user_id','status','parameters','file_path','completed_at']; protected function casts(): array { return ['parameters'=>'array','completed_at'=>'datetime']; } public function report(): BelongsTo { return $this->belongsTo(ReportCatalog::class,'report_catalog_id'); } }
