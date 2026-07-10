<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PayGroup extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['code','name','frequency']; }
