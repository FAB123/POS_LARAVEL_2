<?php

namespace App\Models\Account;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountVoucher extends Model
{
    protected $primaryKey = 'document_id';
    protected $guarded = [];
    use HasFactory;
}
