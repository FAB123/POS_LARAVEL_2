<?php

namespace App\Models\Workorders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkordersItemsTax extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;
    protected $table = 'workorders_items_taxes';
    //protected $primaryKey = ['workorder_id','item_id','line'];
    protected $primaryKey = 'trans_id';
}
