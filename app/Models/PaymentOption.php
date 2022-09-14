<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOption extends Model
{
    use HasFactory;
    // protected $hidden = ['created_at', 'updated_at', 'account_id', 'account_sub_id', 'inserted_by'];
    protected $primaryKey = 'payment_id';
    protected $guarded = [];
}
