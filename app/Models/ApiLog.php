<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model {
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    protected $fillable = ['order_id','service_id','imei_serial','http_code','success','error_msg','duration_ms'];
}
