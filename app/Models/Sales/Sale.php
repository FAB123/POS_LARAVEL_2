<?php

namespace App\Models\Sales;

use App\Models\Customer\Customer;
use App\Models\Employee\Employee;
use App\Models\SalesPayment;
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

    public function payment()
    {
        return $this->hasMany(SalesPayment::class, 'sale_id', 'sale_id')
            ->join('payment_options', 'sales_payments.payment_id', 'payment_options.payment_id');
    }

    public function sales_items()
    {
        return $this->hasMany(SalesItem::class, 'sale_id', 'sale_id');
    }

    public function getSaleTimeAttribute()
    {
        return $this->created_at->format('d-m-Y h:i:A');
    }

    protected $appends = ['sale_time'];
}
