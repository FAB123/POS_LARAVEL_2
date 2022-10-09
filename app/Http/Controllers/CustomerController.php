<?php

namespace App\Http\Controllers;

use App\Models\Account\AccountOpeningBalance;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function getAll(Request $request)
    {
        $query = Customer::query();
        if ($request->input('keyword') != 'null') {
            $keyword = $request->input('keyword');
            $query->whereRaw("name LIKE '%" . $keyword . "%'")
                ->orWhereRaw("address_line_1 LIKE '%" . $keyword . "%'")
                ->orWhereRaw("email LIKE '%" . $keyword . "%'")
                ->orWhereRaw("city LIKE '%" . $keyword . "%'")
                ->orWhereRaw("company_name LIKE '%" . $keyword . "%'")
                ->orWhereRaw("account_number LIKE '%" . $keyword . "%'")
                ->orWhereRaw("vat_number LIKE '%" . $keyword . "%'")
                ->orWhereRaw("mobile LIKE '%" . $keyword . "%'");
        }

        if ($request->input('sortitem') != 'null') {
            $query->orderBy($request->input('sortitem'), $request->input('sortdir'));
        }

        $page = $request->input('page', 1);
        $per_page = $request->input('size') ? $request->input('size') : 10;

        // $total = $query->count();
        $result = $query->with(['details'])->paginate($per_page, ['*'], 'page', $page);

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
                    'vat_number' => $request->input('vat_number'),
                    'payment_type' => $request->input('payment_type'),
                    'customer_type' => $request->input('customer_type'),
                    'status' => 1,
                    'taxable' => $request->input('taxable'),
                ]);

            //update or insert customere details
            CustomerDetail::updateOrCreate([
                'customer_id' => $saved_customer->customer_id,
            ], [
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'zip' => $request->input('zip'),
                'address_line_1' => $request->input('address_line_1'),
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
    public function get_customer_by_id(Request $request)
    {
        // $customer = DB::table('customers')
        //     ->select('customers.customer_id as customer_id', 'customers.name', 'customers.mobile',
        //         'customers.email', 'customers.company_name', 'customers.vat_number',
        //         'customers.payment_type', 'customers.customer_type', 'customers.taxable',
        //         'customers.status', 'customers.created_at', 'customers.updated_at')
        //     ->leftJoin('customer_details', 'customers.customer_id', '=', 'customer_details.customer_id')
        //     ->leftJoin('account_opening_balances', 'customers.customer_id', '=', 'account_opening_balances.account_sub_id')
        //     ->where('customer_id', decrypt($request->input('customer')))
        //     ->get()
        //     ->makeVisible('customer_id');

        $customer = Customer::with('details', 'opening_balance')->find(decrypt($request->input('customer')))->makeVisible('customer_id');
        return response()->json([
            'auth' => true,
            'data' => $customer,
        ], 200);
    }

    //search_customers by item name
    public function search_customers(Request $request)
    {
        $query = Customer::query();
        $keyword = $request->input('query');
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
        foreach ($request->input() as $data) {
            try {
                $saved_customer = Customer::Create([
                    'name' => isset($data['name']) ? $data['name'] : null,
                    'mobile' => isset($data['mobile']) ? $data['mobile'] : null,
                    'email' => isset($data['email']) ? $data['email'] : null,
                    'company_name' => isset($data['company_name']) ? $data['company_name'] : null,
                    'vat_number' => isset($data['vat_number']) ? $data['vat_number'] : null,
                    'payment_type' => isset($data['payment_type']) ? $data['payment_type'] : null,
                    'customer_type' => isset($data['customer_type']) ? $data['customer_type'] : null,
                    'status' => 1,
                    'taxable' => isset($data['taxable']) ? $data['taxable'] : null,
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
                }
            } catch (\Exception$e) {
                $failed_data[] = $data;
            }
        }
        return response()->json([
            'failed' => $failed_data,
        ], 200);
    }
}
