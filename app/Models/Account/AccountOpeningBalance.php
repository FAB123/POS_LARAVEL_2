<?php

namespace App\Models\Account;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountOpeningBalance extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'account_id', 'account_sub_id', 'inserted_by'];
    protected $primaryKey = 'account_id';
    protected $guarded = [];

    public function account_details()
    {
        return $this->belongsTo(AccountHead::class, 'account_id');
    }
}
