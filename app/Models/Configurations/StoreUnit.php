<?php

namespace App\Models\Configurations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreUnit extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $primaryKey = 'unit_id';
    public $timestamps = false;
}
