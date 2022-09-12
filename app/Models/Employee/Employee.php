<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Model
{

    use HasFactory, HasApiTokens, SoftDeletes;

    protected $guarded = [];
    protected $primaryKey = 'employee_id';
    protected $hidden = ['password', 'remember_token', 'employee_id', 'username', 'created_at', 'updated_at', 'deleted_at'];

    public function getStatusFormatedAttribute()
    {
        return ($this->status == 0) ? 'Blocked' : 'Active';
    }

    public function getEncryptedEmployeeAttribute()
    {
        return encrypt($this->employee_id);
    }
    //protected $tabele = 'users'; //call database as users

    //creating scope for easy access status  ==1
    // public function scopeActive($query){
    //     return $query->where('status', 1);
    // }

    protected $appends = ['status_formated', 'encrypted_employee'];
}
