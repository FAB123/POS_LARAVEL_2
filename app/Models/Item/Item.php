<?php

namespace App\Models\Item;

use App\Models\Account\AccountOpeningBalance;
use App\Models\Configurations\StoreUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;
    protected $hidden = ['item_id', 'created_at', 'updated_at', 'deleted_at'];
    protected $primaryKey = 'item_id';
    protected $guarded = [];

    public function vat_list()
    {
        return $this->hasMany(ItemsTax::class, 'item_id', 'item_id');
    }

    public function boxed_items()
    {
        return $this->hasMany(BoxedItem::class, 'boxed_item_id', 'item_id');
    }

    public function item_quantity()
    {
        return $this->hasOne(ItemsQuantity::class, 'item_id', 'item_id');
    }

    public function item_unit()
    {
        return $this->hasOne(StoreUnit::class, 'unit_id', 'unit_type');
    }

    public function opening_balance()
    {
        return $this->hasOne(AccountOpeningBalance::class, 'account_sub_id', 'item_id')->where('account_id', 211)->where('year', date('Y'));
    }

    public function getEncryptedItemAttribute()
    {
        return encrypt($this->item_id);
    }

    //find boxed item
    public function scopeBoxedItems($query)
    {
        return $query->where('is_boxed', 1);
    }

    //find normal item
    public function scopeNormalItems($query)
    {
        return $query->where('is_boxed', 0);
    }

    protected $appends = ['encrypted_item'];
}
