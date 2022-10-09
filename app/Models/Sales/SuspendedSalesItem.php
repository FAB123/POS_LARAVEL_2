<?php

namespace App\Models\Sales;

use App\Models\Configurations\StoreUnit;
use App\Models\Item\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuspendedSalesItem extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];

    protected $primaryKey = 'track_id';

    public function details()
    {
        return $this->hasOne(Item::class, 'item_id', 'item_id');
    }

    public function unit_types()
    {
        return $this->hasOne(StoreUnit::class, 'unit_id', 'unit_type');
    }

    public function tax_details()
    {
        return $this->hasMany(SuspendedSalesItemsTax::class, 'item_id', 'item_id');
    }
}
