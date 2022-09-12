<?php

namespace App\Models\Supplier;

use App\Models\Account\AccountOpeningBalance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;
    protected $hidden = ['supplier_id', 'created_at', 'updated_at', 'deleted_at'];
    protected $primaryKey = 'supplier_id';
    protected $guarded = [];

    public function details()
    {
        return $this->hasOne(SupplierDetail::class, 'supplier_id', 'supplier_id');
    }

    public function opening_balance()
    {
        return $this->hasOne(AccountOpeningBalance::class, 'account_sub_id', 'supplier_id')->where('account_id', 431)->where('year', date('Y'));
    }

    public function getEncryptedSupplierAttribute()
    {
        return encrypt($this->supplier_id);
    }

    protected $appends = ['encrypted_supplier'];
}
