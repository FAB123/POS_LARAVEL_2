<?php

namespace App\Models\Quotations;

use App\Models\Item\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationsItem extends Model
{
    use HasFactory;
    protected $guarded = [];
    //public $timestamps = false;
    protected $primaryKey = 'track_id';
    // protected $primaryKey = ['quotation_id','item_id'];

    public function details()
    {
        return $this->hasOne(Item::class, 'item_id', 'item_id');
    }

    public function tax_details()
    {
        return $this->hasMany(QuotationsItemsTax::class, 'item_id', 'item_id');
    }

}
