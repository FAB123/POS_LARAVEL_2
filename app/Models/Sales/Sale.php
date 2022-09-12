<?php

namespace App\Models\Sales;

use App\Models\Customer\Customer;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $primaryKey = 'sale_id';

    public function customer()
    {
        return $this->hasMany(Customer::class, 'customer_id', 'customer_id');
    }

    public function employee()
    {
        return $this->hasMany(Employee::class, 'employee_id', 'employee_id');
    }
}
