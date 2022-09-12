<?php

namespace App\Models\Account;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountLedgerEntry extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    use HasFactory;
}
