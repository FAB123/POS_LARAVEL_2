<?php

namespace App\Http\Controllers;

use App\Jobs\NewCustomer;
use App\Models\Customer\Customer;
use App\Models\Employee\Employee;
use App\Models\Item\Item;
use App\Models\Supplier\Supplier;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function get_basic_info()
    {
        $data['total_items'] = Item::normalitems()->count();
        $data['total_customer'] = Customer::count();
        $data['total_supplier'] = Supplier::count();
        $data['total_employee'] = Employee::count();

        $data['sales_data'] = $this->get_sales_data();
        $data['purchase_data'] = $this->get_purchase_data();
        return response()->json([
            'data' => $data,
        ], 200);
    }

    public function send_mail()
    {
        $customer = [
            'name' => 'Fysal',
        ];
        NewCustomer::dispatch($customer);
        return response()->json([
            'data' => 'mail send success',
        ], 200);
    }

    private function get_sales_data()
    {
        $sales = DB::table('sales')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->selectRaw('sum(total) as total, DATE(created_at) as date')
            ->groupBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    "x" => date('d-m-Y', strtotime($item->date)),
                    "y" => $item->total,
                ];
            });

        return $sales;
    }

    private function get_purchase_data()
    {
        $sales = DB::table('purchases')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->selectRaw('sum(total) as total, DATE(created_at) as date')
            ->groupBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    "x" => date('d-m-Y', strtotime($item->date)),
                    "y" => $item->total,
                ];
            });

        return $sales;
    }
}
