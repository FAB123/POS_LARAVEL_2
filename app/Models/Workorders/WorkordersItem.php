<?php

namespace App\Models\Workorders;

use App\Models\Item\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkordersItem extends Model
{
    use HasFactory;
    protected $guarded = [];
    //public $timestamps = false;
    //protected $primaryKey = ['workorder_id','item_id'];
    protected $primaryKey = 'track_id';

    public function details()
    {
        return $this->hasOne(Item::class, 'item_id', 'item_id');
    }

    public function tax_details()
    {
        return $this->hasMany(WorkordersItemsTax::class, 'item_id', 'item_id');
    }
}
