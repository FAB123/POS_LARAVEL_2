<?php

namespace App\Http\Controllers;

use App\Models\Quotations\Quotation;
use App\Models\Quotations\QuotationsItem;
use App\Models\Quotations\QuotationsItemsTax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuatationController extends Controller
{
    //save quatations
    public function save_quatation(Request $request)
    {
        $cart_items = $request->input('cartItems');
        $customer_info = $request->input('customerInfo');
        $store_id = $request->input('store_id');
        $payment_info = $request->input('paymentInfo');
        try {
            $quotation = Quotation::create([
                'customer_id' => $customer_info["customer_id"],
                'employee_id' => decrypt(auth()->user()->encrypted_employee),
                'comments' => $request->input('comments'),
                'sub_total' => $payment_info['subtotal'],
                'tax' => $payment_info['tax'],
                'total' => $payment_info['total'],
                'status' => 0,
            ]);

            if ($quotation->quotation_id) {
                //db transaction starting
                DB::beginTransaction();
                $item_list = [];
                $item_tax_list = [];
                foreach ($cart_items as $item) {
                    $item_list[] = [
                        'quotation_id' => $quotation->quotation_id,
                        'item_id' => $item['item_id'],
                        'description' => isset($item['description']) ? $item['description'] : null,
                        'serialnumber' => isset($item['serial']) ? $item['serial'] : null,
                        'quotation_quantity' => $item['quantity'],
                        'item_unit_price' => $item['unit_price'],
                        'item_sub_total' => $item['subTotal'],
                        'location_id' => $store_id,
                    ];
                    $line = 0;
                    foreach ($item['vatList'] as $vat) {
                        $line++;
                        $item_tax_list[] = [
                            'quotation_id' => $quotation->quotation_id,
                            'item_id' => $item['item_id'],
                            'line' => $line,
                            'tax_name' => $vat['tax_name'],
                            'percent' => $vat['percent'],
                            'amount' => $vat['amount'],
                        ];
                    }
                }
                QuotationsItem::insert($item_list);
                QuotationsItemsTax::insert($item_tax_list);
                Quotation::where('quotation_id', $quotation->quotation_id)->update(array('status' => 1));
                DB::commit();
                return response()->json([
                    'status' => true,
                    'invoice_data' => $this->fetch_quatation($quotation->quotation_id),
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

    public function get_quatation(Request $request)
    {
        $quotation = $this->fetch_quatation($request->input('id'));
        if ($quotation) {
            return response()->json([
                'error' => false,
                'invoice_data' => $quotation,
            ], 200);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'sales.no_invoice_number',
            ], 200);
        }
    }

    //get quatation
    private function fetch_quatation($quotation_id = null)
    {

        if ($quotation_id) {
            $quotation = Quotation::with([
                'customer' => function ($query) {
                    $query->select(['name', 'mobile', 'email', 'company_name', 'vat_number', 'payment_type', 'customer_type', 'taxable', 'status', 'customer_id']);
                },
                'employee' => function ($query) {
                    $query->select(['name', 'employee_id']);
                },
            ])->find($quotation_id);
        } else {
            $quotation = Quotation::with([
                'customer' => function ($query) {
                    $query->select(['name', 'mobile', 'email', 'company_name', 'vat_number', 'payment_type', 'customer_type', 'taxable', 'status', 'customer_id']);
                },
                'employee' => function ($query) {
                    $query->select(['name', 'employee_id']);
                },
            ])->latest('quotation_id')->first();
        }

        if ($quotation) {
            $items = QuotationsItem::with([
                'details' => function ($query) {
                    $query->select(['item_name', 'item_name_ar', 'category', 'item_id']);
                },
                'tax_details' => function ($query) use ($quotation) {
                    $query->where('quotation_id', $quotation['quotation_id'])->select(['percent', 'amount', 'item_id']);
                },
            ])->where('quotation_id', $quotation['quotation_id'])->get();

            $quotation["items"] = $items->map(function ($item, $key) {
                return [
                    'item_id' => $item->item_id,
                    'item_name' => $item->details->item_name,
                    'item_name_ar' => $item->details->item_name_ar,
                    'category' => $item->details->category,
                    'description' => $item->description,
                    'serialnumber' => $item->serialnumber,
                    'quotation_quantity' => $item->quotation_quantity,
                    'item_unit_price' => $item->item_unit_price,
                    'item_sub_total' => $item->item_sub_total,
                    'location_id' => $item->location_id,
                    'tax_amount' => $item->tax_details->sum('amount'),
                    'tax_percent' => $item->tax_details->sum('percent'),
                ];
            });

            $quotation["customer"] = $quotation["customer"]->makeVisible(['customer_id'])->makeHidden("encrypted_customer");
            $quotation["employee"] = $quotation["employee"]->makeVisible(['employee_id'])->makeHidden("encrypted_employee");

            return $quotation;
        } else {
            return false;
        }
    }
}
