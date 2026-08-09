<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Service extends Model {
    protected $fillable = [
        'name_th','name_en','description_th','description_en',
        'provider_service_id','provider_service_id_2','device_type','cost_price','sell_price',
        'processing_time','supports_serial','active','sort_order',
    ];

    public function getNameAttribute(): string {
        return app()->getLocale() === 'th' ? $this->name_th : $this->name_en;
    }
    public function getDescriptionAttribute(): ?string {
        return app()->getLocale() === 'th' ? $this->description_th : $this->description_en;
    }
    public function orders() { return $this->hasMany(Order::class); }
}
