<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class JobGrade extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['code','name','rank_order','is_active']; protected function casts(): array { return ['is_active'=>'boolean']; } }
