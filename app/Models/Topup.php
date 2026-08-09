<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Topup extends Model {
    protected $fillable = ['user_id','amount','status','link_id','payment_uri','charge_id'];

    public function user() { return $this->belongsTo(User::class); }
}
