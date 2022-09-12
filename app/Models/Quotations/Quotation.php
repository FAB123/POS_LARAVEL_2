<?php

namespace App\Models\Quotations;

use App\Models\Customer\Customer;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $primaryKey = 'quotation_id';

    public function customer()
    {
        return $this->hasMany(Customer::class, 'customer_id', 'customer_id');
    }

    public function employee()
    {
        return $this->hasMany(Employee::class, 'employee_id', 'employee_id');
    }
}
