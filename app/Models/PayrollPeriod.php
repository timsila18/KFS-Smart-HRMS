<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PayrollPeriod extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['code','starts_on','ends_on','pay_date','status']; protected function casts(): array { return ['starts_on'=>'date','ends_on'=>'date','pay_date'=>'date']; } public function getRouteKeyName(): string { return 'uuid'; } }
