<?php

namespace App\Http\Controllers;

use App\Models\Customer\Customer;
use App\Models\Employee\Employee;
use App\Models\Item\Item;
use App\Models\Supplier\Supplier;

class HomeController extends Controller
{
    public function get_basic_info()
    {
        $data['total_items'] = Item::normalitems()->count();
        $data['total_customer'] = Customer::count();
        $data['total_supplier'] = Supplier::count();
        $data['total_employee'] = Employee::count();
        return response()->json([
            'data' => $data,
        ], 200);
    }
}
