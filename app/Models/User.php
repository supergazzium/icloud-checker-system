<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
    use Notifiable;
    protected $fillable = ['name','email','password','role','balance','locale','is_active','provider','provider_id','must_change_password'];
    protected $hidden   = ['password','remember_token','two_factor_secret','two_factor_recovery_codes'];
    protected $casts    = ['balance' => 'decimal:2', 'is_active' => 'boolean', 'must_change_password' => 'boolean'];

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isReseller(): bool { return $this->role === 'reseller'; }
    public function orders()             { return $this->hasMany(Order::class); }
    public function creditTransactions() { return $this->hasMany(CreditTransaction::class); }
}
