<?php

namespace App\Models\Account;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountsTransaction extends Model
{
    protected $primaryKey = 'transaction_id';
    protected $guarded = [];
    use HasFactory;
}
