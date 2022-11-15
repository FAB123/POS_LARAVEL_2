<?php

namespace App\Http\Controllers;

use App\Models\Account\AccountOpeningBalance;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function get_all_customers($page, $size = 10, $keyword, $sortitem, $sortdir)
    {
        $result = Customer::select('customers.customer_id as customer_id', 'customers.*', 'stock_locations.location_name_en', 'stock_locations.location_name_ar', 'customer_details.comments')
            ->join('customer_details', 'customers.customer_id', 'customer_details.customer_id')
            ->join('stock_locations', 'customers.location_id', 'stock_locations.location_id')
            ->when($keyword != 'null', function ($query) use ($keyword) {
                $query->whereRaw("name LIKE '%" . $keyword . "%'")
                    ->orWhereRaw("address_line_1 LIKE '%" . $keyword . "%'")
                    ->orWhereRaw("email LIKE '%" . $keyword . "%'")
                    ->orWhereRaw("city LIKE '%" . $keyword . "%'")
                    ->orWhereRaw("company_name LIKE '%" . $keyword . "%'")
                    ->orWhereRaw("account_number LIKE '%" . $keyword . "%'")
                    ->orWhereRaw("vat_number LIKE '%" . $keyword . "%'")
                    ->orWhereRaw("mobile LIKE '%" . $keyword . "%'");
            })->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
            $query->orderBy($sortitem, $sortdir);
        }
        )->paginate($size, ['*'], 'page', $page);

        return response()->json([
            'data' => $result,
        ], 200);
    }

    public function get_all_customers_list(Request $request)
    {
        $result = Customer::select('name', 'email', 'customer_id as account_id')->get()->makeVisible('customer_id')->toArray();

        return response()->json([
            'data' => $result,
        ], 200);
    }

    //save or update customer
    public function save_customer(Request $request)
    {
        try {
            DB::beginTransaction();
            $location_id = $request->header('Store');
            $saved_customer = Customer::updateOrCreate([
                'customer_id' => $request->input('customerId') ? decrypt($request->input('customerId')) : null,
            ],
                [
                    'name' => $request->input('name'),
                    'mobile' => $request->input('mobile'),
                    'email' => $request->input('email'),
                    'company_name' => $request->input('company_name'),
                    'identity_type' => $request->input('identity_type'),
                    'party_id' => $request->input('party_id'),
                    'customer_type' => $request->input('customer_type'),
                    'location_id' => $location_id,
                    'status' => 1,
                ]);

            //update or insert customere details
            CustomerDetail::updateOrCreate([
                'customer_id' => $saved_customer->customer_id,
            ], [
                'city' => $request->input('city'),
                'city_sub_division' => $request->input('city_sub_division'),
                'street' => $request->input('street'),
                'additional_street' => $request->input('additional_street'),
                'building_number' => $request->input('building_number'),
                'plot_identification' => $request->input('plot_identification'),
                // 'state' => $request->input('state'),
                'zip' => $request->input('zip'),
                'comments' => $request->input('comments'),
                'account_number' => $request->input('account_number'),
            ]);

            //update or insert customere opening balance details
            if (AccountOpeningBalance::where('account_sub_id', $saved_customer->customer_id)->find(241)) {
                AccountOpeningBalance::where('account_sub_id', $saved_customer->customer_id)
                    ->where('account_id', 241)
                    ->where('year', date('Y'))
                    ->where('location_id', $location_id)
                    ->update(['amount' => $request->input('opening_balance'), 'inserted_by' => decrypt(auth()->user()->encrypted_employee)]);
            } else {
                AccountOpeningBalance::insert([
                    'account_id' => 241,
                    'account_sub_id' => $saved_customer->customer_id,
                    'amount' => $request->input('opening_balance'),
                    'location_id' => $location_id,
                    'inserted_by' => decrypt(auth()->user()->encrypted_employee),
                    'year' => date('Y'),
                ]);
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'customer_id' => $saved_customer->encrypted_customer,
                'message' => "customers.new_customer_or_update",
            ], 200);
        } catch (\Exception$e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => "customers.error_new_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //delete customer from array
    public function delete_customer(Request $request)
    {
        foreach ($request->input() as $item) {
            try {
                Customer::find(decrypt($item))->delete();
            } catch (\Exception$e) {
                return response()->json([
                    'status' => false,
                    'message' => $e,
                ], 200);
            }
        }
        return response()->json([
            'status' => true,
            'message' => "customers.delete",
        ], 200);
    }

    //get customer by id
    public function get_customer_by_id(Request $request, $customer_id)
    {
        $customer = Customer::join('customer_details', 'customers.customer_id', 'customer_details.customer_id')
            ->join('stock_locations', 'customers.location_id', 'stock_locations.location_id')
            ->with('opening_balance')->find(decrypt($customer_id))->makeVisible('customer_id');
        // $customer = Customer::with('details', 'opening_balance')->find(decrypt($customer_id))->makeVisible('customer_id');
        return response()->json([
            'auth' => true,
            'data' => $customer,
        ], 200);
    }

    //search_customers by item name
    public function search_customers(Request $request, $keyword)
    {
        $query = Customer::query();
        $query->whereRaw("name LIKE '%" . $keyword . "%'")
            ->orWhereRaw("mobile LIKE '%" . $keyword . "%'")
            ->orWhereRaw("email LIKE '%" . $keyword . "%'")
            ->orWhereRaw("company_name LIKE '%" . $keyword . "%'");

        $result = $query->get();
        $result->makeVisible('customer_id');

        return response()->json([
            'data' => $result,
        ], 200);
    }

    //insert customers from excel
    public function bulk_insert(Request $request)
    {
        $failed_data = array();
        $location_id = $request->header('Store');
        foreach ($request->input() as $data) {
            try {
                $saved_customer = Customer::Create([
                    'name' => isset($data['name']) ? $data['name'] : null,
                    'mobile' => isset($data['mobile']) ? $data['mobile'] : null,
                    'email' => isset($data['email']) ? $data['email'] : null,
                    'company_name' => isset($data['company_name']) ? $data['company_name'] : null,
                    'vat_number' => isset($data['vat']) ? $data['vat'] : null,
                    'payment_type' => isset($data['payment_type']) ? $data['payment_type'] : null,
                    'customer_type' => isset($data['customer_type']) ? $data['customer_type'] : 0,
                    'status' => 1,
                    'taxable' => isset($data['taxable']) ? $data['taxable'] : 1,
                ]);

                if ($saved_customer->customer_id) {
                    CustomerDetail::updateOrCreate([
                        'customer_id' => $saved_customer->customer_id,
                    ], [
                        'city' => isset($data['city']) ? $data['city'] : null,
                        'state' => isset($data['state']) ? $data['state'] : null,
                        'zip' => isset($data['zip']) ? $data['zip'] : null,
                        'address_line_1' => isset($data['address_line_1']) ? $data['address_line_1'] : null,
                        'comments' => isset($data['comments']) ? $data['comments'] : null,
                        'account_number' => isset($data['account_number']) ? $data['account_number'] : null,
                    ]);

                    AccountOpeningBalance::insert([
                        'account_id' => 241,
                        'account_sub_id' => $saved_customer->customer_id,
                        'amount' => 0,
                        'location_id' => $location_id,
                        'inserted_by' => decrypt(auth()->user()->encrypted_employee),
                        'year' => date('Y'),
                    ]);
                }
            } catch (\Exception$e) {
                info($e);
                $failed_data[] = $data;
            }
        }
        return response()->json([
            'failed' => $failed_data,
        ], 200);
    }
}
