<?php

namespace App\Http\Controllers;

use App\Models\Configurations\StoreUnit;
use App\Models\Customer\Customer;
use App\Models\Item\ItemsQuantity;
use App\Models\Item\ItemsTax;
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
                        'discount' => $item['discount'],
                        'discount_type' => $item['discount_type'],
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
                    'discount' => $item->discount,
                    'discount_type' => $item->discount_type,
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

    //get Quatation Details
    public function get_quatation_details(Request $request)
    {
        $quatation_id = $request->input('id');
        if ($quatation_id) {
            $location_id = $request->header('Store');
            //update quatation status
            $quatation_sale = Quotation::find($quatation_id);
            $quatation_sale->status = 1;
            $quatation_sale->save();
            $customer_id = $quatation_sale->customer_id;

            $items = QuotationsItem::with(['details'])
                ->where('quotation_id', $quatation_id)
                ->get();
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
                    "quantity" => $item->quotation_quantity,
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
