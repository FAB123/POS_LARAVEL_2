<?php

namespace App\Models\Sales;

use App\Models\Item\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesItem extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];

    protected $primaryKey = 'track_id';

    public function details()
    {
        return $this->hasOne(Item::class, 'item_id', 'item_id');
    }

    public function tax_details()
    {
        return $this->hasMany(SalesItemsTaxes::class, 'item_id', 'item_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'sale_id');
    }
}
