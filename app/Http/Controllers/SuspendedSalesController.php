<?php

namespace App\Http\Controllers;

use App\Models\Sales\SuspendedSale;
use App\Models\Sales\SuspendedSalesItem;
use App\Models\Sales\SuspendedSalesItemsTax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuspendedSalesController extends Controller
{
    //save or update Suspended
    public function save_suspended(Request $request)
    {
        $bill_type = $request->input('billType');
        $sale_type = $request->input('sale_type');
        $cart_items = $request->input('cartItems');
        $customer_info = $request->input('customerInfo');
        $store_id = $request->input('store_id');
        $payment_info = $request->input('paymentInfo');
        try {
            //db transaction starting
            DB::beginTransaction();
            $suspended_sale = SuspendedSale::create([
                'customer_id' => $customer_info ? $customer_info["customer_id"] : null,
                'employee_id' => decrypt(auth()->user()->encrypted_employee),
                'bill_type' => $bill_type,
                'sale_type' => $sale_type,
                'suspended_status' => 0,
                //'table_id' => '',
                'sub_total' => $payment_info['subtotal'],
                'tax' => $payment_info['tax'],
                'total' => $payment_info['total'],
                'comments' => $request->input('comments'),
            ]);

            $item_list = [];
            $item_tax_list = [];
            foreach ($cart_items as $item) {
                $item_list[] = [
                    'suspended_id' => $suspended_sale->suspended_id,
                    'item_id' => $item['item_id'],
                    'description' => isset($item['description']) ? $item['description'] : null,
                    'serialnumber' => isset($item['serial']) ? $item['serial'] : null,
                    'suspended_quantity' => $item['quantity'],
                    'item_unit_price' => $item['unit_price'],
                    'discount_type' => $item['discount_type'],
                    'discount' => $item['discount'],
                    'item_sub_total' => $item['subTotal'],
                    'location_id' => $store_id,
                ];
                $line = 0;
                foreach ($item['vatList'] as $vat) {
                    $line++;
                    $item_tax_list[] = [
                        'suspended_id' => $suspended_sale->suspended_id,
                        'item_id' => $item['item_id'],
                        'line' => $line,
                        'tax_name' => $vat['tax_name'],
                        'percent' => $vat['percent'],
                        'amount' => $vat['amount'],
                    ];
                }
            }
            SuspendedSalesItem::insert($item_list);
            SuspendedSalesItemsTax::insert($item_tax_list);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "sales.sales_suspended",
            ], 200);

        } catch (\Exception$e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => "sales.error_sales_suspended",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //get all Suspended sales
    public function get_all_suspended(Request $request)
    {
        $query = SuspendedSale::query();
        $query->where('employee_id', '1');
        if ($request->input('keyword') != 'null') {
            $keyword = $request->input('keyword');
            $query->whereRaw("sub_total LIKE '%" . $keyword . "%'")
                ->orWhereRaw("total LIKE '%" . $keyword . "%'")
                ->orWhereRaw("comments LIKE '%" . $keyword . "%'");
        }

        if ($request->input('sortitem') != 'null') {
            $query->orderBy($request->input('sortitem'), $request->input('sortdir'));
        }

        $page = $request->input('page', 1);
        $per_page = $request->input('size') ? $request->input('size') : 10;

        $result = $query->with([
            'customer' => function ($query) {
                $query->select(['name', 'customer_id']);
            },
        ]
        )->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'data' => $result,
        ], 200);
    }

    //get suspended_sale
    public function get_suspended_details(Request $request)
    {
        $suspended_id = $request->input('id');
        if ($suspended_id) {
            $items = SuspendedSalesItem::with([
                'details' => function ($query) {
                    $query->select(['item_name', 'item_name_ar', 'category', 'item_id']);
                },
                'tax_details' => function ($query) use ($suspended_id) {
                    $query->where('suspended_id', $suspended_id)->select(['percent', 'amount', 'item_id']);
                },
            ])->where('suspended_id', $suspended_id)->get();

            $suspended_items = $items->map(function ($item, $key) {
                return [
                    'item_id' => $item->item_id,
                    'item_name' => $item->details->item_name,
                    'item_name_ar' => $item->details->item_name_ar,
                    'category' => $item->details->category,
                    'description' => $item->description,
                    'serialnumber' => $item->serialnumber,
                    'suspended_quantity' => $item->suspended_quantity,
                    'item_unit_price' => $item->item_unit_price,
                    'discount_type' => $item->discount_type,
                    'discount' => $item->discount,
                    'item_sub_total' => $item->item_sub_total,
                    'location_id' => $item->location_id,
                    'tax_amount' => $item->tax_details->sum('amount'),
                    'tax_percent' => $item->tax_details->sum('percent'),
                ];
            });

            if ($suspended_items) {
                return response()->json([
                    'error' => false,
                    'invoice_data' => $suspended_items,
                ], 200);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'sales.no_invoice_number',
                ], 200);
            }
        } else {
            return response()->json([
                'error' => true,
                'message' => 'sales.no_invoice_number',
            ], 200);
        }
    }
}
