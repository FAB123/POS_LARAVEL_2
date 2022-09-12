<?php

namespace App\Models\Account;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountHead extends Model
{
    use HasFactory, SoftDeletes;
    protected $primaryKey = 'account_id';
    protected $guarded = [];

    // public function opening_balance(){
    //     return $this->hasOne(AccountOpeningBalance::class,'account_id', 'account_id')->where('year', date('Y'));
    // }
}
