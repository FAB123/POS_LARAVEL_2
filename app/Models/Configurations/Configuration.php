<?php

namespace App\Models\Configurations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $primaryKey = 'key';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
}
