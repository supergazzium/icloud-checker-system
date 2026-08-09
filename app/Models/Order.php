<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Order extends Model {
    protected $fillable = [
        'user_id','service_id','imei_serial','cost_price','sell_price','status',
        'result_model','result_serial','result_imei','result_color','result_storage',
        'result_region','result_fmi','result_activation','result_blacklist',
        'result_simlock','result_mdm','result_warranty','result_purchase_date',
        'result_replaced','response_text','raw_response','error_message','processed_at',
    ];
    protected $casts = ['processed_at' => 'datetime'];

    public function user()    { return $this->belongsTo(User::class); }
    public function service() { return $this->belongsTo(Service::class); }
}
