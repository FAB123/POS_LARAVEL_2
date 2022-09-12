<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'customer_id';
    protected $guarded = [];
}
