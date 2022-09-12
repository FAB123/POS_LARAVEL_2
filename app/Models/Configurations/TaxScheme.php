<?php

namespace App\Models\Configurations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxScheme extends Model
{
    use HasFactory;
    protected $hidden = ['deleted_at'];
    public $timestamps = false;
    protected $guarded = [];
    protected $primaryKey = 'item_id';
}
