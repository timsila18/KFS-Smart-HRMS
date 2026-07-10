<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Attachment extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['attachment_type_id','attachable_type','attachable_id','file_name','file_path','mime_type','size_bytes']; }
