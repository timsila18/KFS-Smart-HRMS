<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Station extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['parent_id','code','name','station_type','county','region','is_active']; protected function casts(): array { return ['is_active'=>'boolean']; } }
