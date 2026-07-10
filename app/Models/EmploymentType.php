<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmploymentType extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['code','name','is_pensionable']; protected function casts(): array { return ['is_pensionable'=>'boolean']; } }
