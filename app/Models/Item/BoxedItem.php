<?php

namespace App\Models\Item;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoxedItem extends Model
{
    use HasFactory;
    protected $hidden = ['boxed_item_id', 'created_at', 'updated_at'];
    protected $guarded = [];
    protected $primaryKey = 'boxed_item_id';

    public function details()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    protected $with = ['details'];
}
