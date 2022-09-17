<?php

namespace App\Http\Controllers;

use App\Models\Account\AccountOpeningBalance;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    //get all supplier from table with pagination
    public function getAll(Request $request)
    {
        $query = Supplier::query();
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

        $result = $query->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'data' => $result,
        ], 200);
    }

    public function get_all_suppliers_list(Request $request)
    {
        $result = Supplier::select('name', 'email', 'supplier_id as account_id')->get()->makeVisible('supplier_id')->toArray();

        return response()->json([
            'data' => $result,
        ], 200);
    }

    //save or update Supplier
    public function save_supplier(Request $request)
    {
        try {
            DB::beginTransaction();
            $saved_supplier = Supplier::updateOrCreate([
                'supplier_id' => $request->input('supplierId') ? decrypt($request->input('supplierId')) : null,
            ],
                [
                    'name' => $request->input('name'),
                    'mobile' => $request->input('mobile'),
                    'email' => $request->input('email'),
                    'contact_person' => $request->input('contact_person'),
                    'vat_number' => $request->input('vat_number'),
                    'payment_type' => $request->input('payment_type'),
                    'status' => 1,
                    'taxable' => $request->input('taxable'),
                ]);

            SupplierDetail::updateOrCreate([
                'supplier_id' => $saved_supplier->supplier_id,
            ], [
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'country' => $request->input('country'),
                'zip' => $request->input('zip'),
                'address_line_1' => $request->input('address_line_1'),
                'comments' => $request->input('comments'),
                'account_number' => $request->input('account_number'),
            ]);

            //update or insert customere opening balance details
            if (AccountOpeningBalance::where('account_sub_id', $saved_supplier->supplier_id)->find(431)) {
                AccountOpeningBalance::where('account_sub_id', $saved_supplier->supplier_id)
                    ->where('account_id', 431)
                    ->where('year', date('Y'))
                    ->update(['amount' => $request->input('opening_balance'), 'inserted_by' => decrypt(auth()->user()->encrypted_employee)]);
            } else {
                AccountOpeningBalance::insert([
                    'account_id' => 431,
                    'account_sub_id' => $saved_supplier->supplier_id,
                    'amount' => $request->input('opening_balance'),
                    'inserted_by' => decrypt(auth()->user()->encrypted_employee),
                    'year' => date('Y'),
                ]);
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'supplier_id' => $saved_supplier->encrypted_supplier,
                'message' => "suppliers.new_supplier_or_update",
            ], 200);
        } catch (\Exception$e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => "suppliers.error_new_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //delete Supplier from array
    public function delete_supplier(Request $request)
    {
        foreach ($request->input() as $item) {
            try {
                Supplier::find(decrypt($item))->delete();
            } catch (\Exception$e) {
                return response()->json([
                    'status' => false,
                    'message' => $e,
                ], 200);
            }
        }
        return response()->json([
            'status' => true,
            'message' => "suppliers.delete",
        ], 200);
    }

    //get Supplier by id
    public function get_supplier_by_id(Request $request)
    {
        $supplier = Supplier::with('details', 'opening_balance')->find(decrypt($request->input('supplier')))->makeVisible('supplier_id');
        return response()->json([
            'auth' => true,
            'data' => $supplier,
        ], 200);
    }

    //search_SUPPLIERS by item name
    public function search_suppliers(Request $request)
    {
        $query = Supplier::query();
        $keyword = $request->input('query');
        $query->whereRaw("name LIKE '%" . $keyword . "%'")
            ->orWhereRaw("mobile LIKE '%" . $keyword . "%'")
            ->orWhereRaw("email LIKE '%" . $keyword . "%'");
        // ->orWhereRaw("company_name LIKE '%" . $keyword . "%'");

        $result = $query->get();
        $result->makeVisible('supplier_id');

        return response()->json([
            'data' => $result,
        ], 200);
    }

    //insert Suppliers from excel
    public function bulk_insert(Request $request)
    {
        $failed_data = array();
        foreach ($request->input() as $data) {
            try {
                $saved_supplier = Supplier::Create([
                    'name' => isset($data['name']) ? $data['name'] : null,
                    'mobile' => isset($data['mobile']) ? $data['mobile'] : null,
                    'email' => isset($data['email']) ? $data['email'] : null,
                    'contact_person' => isset($data['contact_person']) ? $data['contact_person'] : null,
                    'vat_number' => isset($data['vat_number']) ? $data['vat_number'] : null,
                    'status' => 1,
                    'taxable' => isset($data['taxable']) ? $data['taxable'] : null,
                ]);

                if ($saved_supplier->supplier_id) {
                    SupplierDetail::updateOrCreate([
                        'supplier_id' => $saved_supplier->supplier_id,
                    ], [
                        'city' => isset($data['city']) ? $data['city'] : null,
                        'state' => isset($data['state']) ? $data['state'] : null,
                        'country' => isset($data['country']) ? $data['country'] : null,
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
