<?php

namespace App\Models\Quotations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationsItemsTax extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;
    protected $table = 'quotations_items_taxes';
    protected $primaryKey = 'trans_id';
}
