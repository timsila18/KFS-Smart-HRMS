<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ContractType extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['code','name','default_months','requires_end_date']; protected function casts(): array { return ['requires_end_date'=>'boolean']; } }
