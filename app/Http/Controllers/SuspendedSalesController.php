<?php

namespace App\Http\Controllers;

use App\Models\Configurations\StoreUnit;
use App\Models\Customer\Customer;
use App\Models\Item\ItemsQuantity;
use App\Models\Item\ItemsTax;
use App\Models\Sales\SuspendedSale;
use App\Models\Sales\SuspendedSalesItem;
use App\Models\Sales\SuspendedSalesItemsTax;
use function PHPSTORM_META\map;
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
        $query->where('suspended_status', 0);
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
            $location_id = $request->header('Store');
            $suspended_sale = SuspendedSale::find($suspended_id);
            $suspended_sale->suspended_status = 1;
            $suspended_sale->save();
            $customer_id = $suspended_sale->customer_id;
            $items = SuspendedSalesItem::with([
                'details',
                // 'tax_details' => function ($query) use ($suspended_id) {
                //     $query->where('suspended_id', $suspended_id)->select(['percent', 'amount', 'item_id']);
                // },
            ])->where('suspended_id', $suspended_id)->get();
            $new_item = $items->map(function ($item) use ($location_id) {
                $item_taxs = ItemsTax::find($item->item_id)->get();
                $sub_total = $item->item_sub_total;
                $total = 0;
                $total_percent = 0;
                $total_tax = 0;
                $tax_details = $item_taxs->map(function ($item_tax) use ($sub_total, &$total, &$total_percent, &$total_tax) {
                    $tax_fraction = $item_tax->percent / 100;
                    $tax_amount = number_format($sub_total * $tax_fraction, 2);
                    $total += $sub_total + $tax_amount;
                    $total_percent += $item_tax->percent;
                    $total_tax += $tax_amount;
                    return [
                        "tax_name" => $item_tax->tax_name,
                        "percent" => $item_tax->percent,
                        "amount" => $tax_amount,
                    ];
                });

                //fine tune it
                $unit = StoreUnit::find($item->details->unit_type);
                $stock = ItemsQuantity::where('location_id', $location_id)
                    ->find($item->item_id);

                return [
                    "item_id" => $item->item_id,
                    "item_name" => $item->details->item_name,
                    "item_name_ar" => $item->details->item_name_ar,
                    "unit_price" => $item->item_unit_price,
                    "cost_price" => $item->details->cost_price,
                    "quantity" => $item->suspended_quantity,
                    "discount" => $item->discount,
                    "discount_type" => $item->discount_type,
                    "unit" => "{$unit->unit_name_en} - {$unit->unit_name_ar}",
                    "subTotal" => $item->item_sub_total,
                    "allowdesc" => $item->details->allowdesc,
                    "is_serialized" => $item->details->is_serialized,
                    "minimum_price" => $item->details->minimum_price,
                    "stock_type" => $item->details->stock_type,
                    "is_boxed" => $item->details->is_boxed,
                    "vatList" => $tax_details,
                    "vat" => "{$total_tax} [{$total_percent}%]",
                    "total" => $total,
                    "vatPercentage" => $total_percent,
                    "stock" => $stock->quantity,
                ];
            });

            $data = [
                'customerInfo' => $customer_id ? Customer::with(['details', 'opening_balance'])->find($customer_id)->makeVisible('customer_id') : null,
                'cartItems' => $new_item,
            ];

            if ($items) {
                return response()->json([
                    'status' => true,
                    'data' => $data,
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
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
