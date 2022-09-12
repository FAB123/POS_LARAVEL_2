<?php

namespace App\Models\Customer;

use App\Models\Account\AccountOpeningBalance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;
    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'customer_id'];
    protected $primaryKey = 'customer_id';
    protected $guarded = [];

    public function details()
    {
        return $this->hasOne(CustomerDetail::class, 'customer_id', 'customer_id');
    }

    public function opening_balance()
    {
        return $this->hasOne(AccountOpeningBalance::class, 'account_sub_id', 'customer_id')->where('account_id', 241)->where('year', date('Y'));
    }

    public function getEncryptedCustomerAttribute()
    {
        return encrypt($this->customer_id);
    }

    protected $appends = ['encrypted_customer'];

}
