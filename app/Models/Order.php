<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'service_id', 'imei_serial', 'cost_price', 'sell_price', 'status',
        // Product identity
        'result_model', 'result_model_desc', 'result_part_number',
        'result_part_country', 'result_part_type', 'result_thumbnail',
        // Identifiers
        'result_serial', 'result_imei', 'result_imei2',
        // Hardware attributes
        'result_color', 'result_storage', 'result_region',
        // Lock / security status
        'result_fmi', 'result_activation', 'result_blacklist',
        'result_simlock', 'result_carrier', 'result_mdm',
        // Warranty / coverage
        'result_warranty', 'result_coverage_end_date', 'result_ac_eligible',
        'result_technical_support', 'result_repair_coverage',
        // Purchase
        'result_purchase_date', 'result_purchase_country',
        // Unit state flags
        'result_replaced', 'result_replacement', 'result_refurbished',
        'result_demo_unit', 'result_loaner',
        // Raw
        'response_text', 'raw_response', 'error_message', 'processed_at',
    ];

    protected $casts = ['processed_at' => 'datetime'];

    public function user()    { return $this->belongsTo(User::class); }
    public function service() { return $this->belongsTo(Service::class); }
}
