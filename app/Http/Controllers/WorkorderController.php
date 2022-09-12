<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workorders\Workorder;
use App\Models\Workorders\WorkordersItem;
use App\Models\Workorders\WorkordersItemsTax;
use Illuminate\Support\Facades\DB;

class WorkorderController extends Controller
{
    //save or update work_order
    public function save_work_order(Request $request)
    {
        // $bill_type = $request->input('billType');
        $cart_items = $request->input('cartItems');
        $customer_info = $request->input('customerInfo');
        $store_id = $request->input('store_id');
        $payment_info = $request->input('paymentInfo');

        try {
            $work_order = Workorder::create([
                // 'workorder_time' => Carbon::now(),
                'customer_id' => $customer_info["customer_id"],
                'employee_id' => decrypt(auth()->user()->encrypted_employee),
                'comments' => $request->input('comments'),
                'sub_total' => $payment_info['subtotal'],
                'tax' => $payment_info['tax'],
                'total' => $payment_info['total'],
                'status' => 0,
            ]);

            if ($work_order->workorder_id) {
                //db transaction starting
                DB::beginTransaction();
                $item_list = [];
                $item_tax_list = [];
                foreach ($cart_items as $item) {
                    $item_list[] = [
                        'workorder_id' => $work_order->workorder_id,
                        'item_id' => $item['item_id'],
                        'description' => isset($item['description']) ? $item['description'] : null,
                        'serialnumber' => isset($item['serial']) ? $item['serial'] : null,
                        'workorder_quantity' => $item['quantity'],
                        'item_unit_price' => $item['unit_price'],
                        'item_sub_total' => $item['subTotal'],
                        'location_id' => $store_id,
                    ];
                    $line = 0;
                    foreach ($item['vatList'] as $vat) {
                        $line++;
                        $item_tax_list[] = [
                            'workorder_id' => $work_order->workorder_id,
                            'item_id' => $item['item_id'],
                            'line' => $line,
                            'tax_name' => $vat['tax_name'],
                            'percent' => $vat['percent'],
                            'amount' => $vat['amount'],
                        ];
                    }
                }
                WorkordersItem::insert($item_list);
                WorkordersItemsTax::insert($item_tax_list);
                Workorder::where('workorder_id', $work_order->workorder_id)->update(array('status' => 1));
                DB::commit();
                return response()->json([
                    'status' => true,
                    'invoice_data' => $this->fetch_workorder($work_order->workorder_id),
                    'message' => "sales.new_customer_or_update",
                ], 200);
            }
            return response()->json([
                'status' => false,
                'message' => "sales.new_customer_or_update",
            ], 200);
        } catch (\Exception$e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => "sales.error_new_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }
    public function get_workorder(Request $request)
    {
        $workorder = $this->fetch_workorder($request->input('id'));
        if ($workorder) {
            return response()->json([
                'error' => false,
                'invoice_data' => $workorder,
            ], 200);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'sales.no_invoice_number',
            ], 200);
        }

    }

    //get quatation
    private function fetch_workorder($workorder_id = null)
    {
        if ($workorder_id) {
            $workorder = Workorder::with([
                'customer' => function ($query) {
                    $query->select(['name', 'mobile', 'email', 'company_name', 'vat_number', 'payment_type', 'customer_type', 'taxable', 'status', 'customer_id']);
                },
                'employee' => function ($query) {
                    $query->select(['name', 'employee_id']);
                },
            ])->find($workorder_id);
        } else {
            $workorder = Workorder::with([
                'customer' => function ($query) {
                    $query->select(['name', 'mobile', 'email', 'company_name', 'vat_number', 'payment_type', 'customer_type', 'taxable', 'status', 'customer_id']);
                },
                'employee' => function ($query) {
                    $query->select(['name', 'employee_id']);
                },
            ])->latest('workorder_id')->first();
        }

        if ($workorder) {
            $items = WorkordersItem::with([
                'details' => function ($query) {
                    $query->select(['item_name', 'item_name_ar', 'category', 'item_id']);
                },
                'tax_details' => function ($query) use ($workorder) {
                    $query->where('workorder_id', $workorder['workorder_id'])->select(['percent', 'amount', 'item_id']);
                },
            ])->where('workorder_id', $workorder['workorder_id'])->get();

            $workorder["items"] = $items->map(function ($item, $key) {
                return [
                    'item_id' => $item->item_id,
                    'item_name' => $item->details->item_name,
                    'item_name_ar' => $item->details->item_name_ar,
                    'category' => $item->details->category,
                    'description' => $item->description,
                    'serialnumber' => $item->serialnumber,
                    'workorder_quantity' => $item->quotation_quantity,
                    'item_unit_price' => $item->item_unit_price,
                    'item_sub_total' => $item->item_sub_total,
                    'location_id' => $item->location_id,
                    'tax_amount' => $item->tax_details->sum('amount'),
                    'tax_percent' => $item->tax_details->sum('percent'),
                ];
            });

            $workorder["customer"] = $workorder["customer"]->makeVisible(['customer_id'])->makeHidden("encrypted_customer");
            $workorder["employee"] = $workorder["employee"]->makeVisible(['employee_id'])->makeHidden("encrypted_employee");

            return $workorder;
        } else {
            return false;
        }

    }
}
