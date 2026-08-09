<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model {
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    protected $fillable = [
        'user_id','type','amount','balance_before','balance_after',
        'description','order_id','admin_id','admin_ip','reference',
    ];
    protected $casts = ['created_at' => 'datetime'];
    public function user()  { return $this->belongsTo(User::class); }
    public function order() { return $this->belongsTo(Order::class); }
    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
}
