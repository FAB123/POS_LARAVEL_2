<?php

namespace App\Http\Controllers;

use App\Models\Account\AccountLedgerEntry;
use App\Models\Account\AccountsTransaction;
use App\Models\Inventory;
use App\Models\Item\BoxedItem;
use App\Models\Item\ItemsQuantity;
use App\Models\Sales\Sale;
use App\Models\Sales\SalesItem;
use App\Models\Sales\SalesItemsTaxes;
use GenerateTVL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SalesController extends Controller
{
    //save or update sales
    public function save_sale(Request $request)
    {
        $store_id = $request->header('Store');
        $bill_type = $request->input('billType');
        $sale_type = $request->input('sale_type');
        $cart_items = $request->input('cartItems');
        $customer_info = $request->input('customerInfo');
        $payment_info = $request->input('paymentInfo');

        $customer_id = $customer_info ? $customer_info["customer_id"] : null;

        try {
            $sales = Sale::create([
                'customer_id' => $customer_id,
                'employee_id' => decrypt(auth()->user()->encrypted_employee),
                'bill_type' => $bill_type,
                'sale_type' => $sale_type,
                'sale_status' => 0,
                //'table_id' => '',
                'sub_total' => $payment_info['subtotal'],
                'tax' => $payment_info['tax'],
                'total' => $payment_info['total'],
                'comments' => $request->input('comments'),
            ]);

            $total_cost_price = 0;

            if ($sales->sale_id) {
                //db transaction starting
                DB::beginTransaction();
                $item_list = [];
                $item_tax_list = [];
                $inventory_list = [];
                foreach ($cart_items as $item) {
                    //calculate total cost
                    $total_cost_price += bcmul($item['cost_price'], $item['quantity']);
                    //adjust item quantity
                    if ($item['stock_type'] == '1' && $item['is_boxed'] == '0') {
                        if ($sale_type == 'CASR' || $sale_type == 'CRSR') {
                            $inventory_quantity = $item['quantity'];
                            ItemsQuantity::where([['item_id', $item['item_id']],
                                ['location_id', $store_id]])->increment('quantity', $item['quantity']);
                        } else {
                            $inventory_quantity = $item['quantity'] * -1;
                            ItemsQuantity::where([['item_id', $item['item_id']],
                                ['location_id', $store_id]])->decrement('quantity', $item['quantity']);
                        }

                        //insert inventory data
                        $inventory_list[] = [
                            'item_id' => $item['item_id'],
                            'trans_user' => decrypt(auth()->user()->encrypted_employee),
                            'trans_comment' => 'POS ' . $sales->sale_id,
                            'location_id' => $store_id,
                            'quantity' => $inventory_quantity,
                        ];
                    }

                    if ($item['is_boxed'] == '1') {
                        $boxed_items = BoxedItem::where('boxed_item_id', $item['item_id'])->get();
                        foreach ($boxed_items as $boxed_item) {
                            if ($boxed_item['details']['stock_type'] == 1) {
                                //insert inventory data
                                $inventory_list[] = [
                                    'item_id' => $boxed_item['item_id'],
                                    'trans_user' => decrypt(auth()->user()->encrypted_employee),
                                    'trans_comment' => '[PACK] POS ' . $sales->sale_id,
                                    'location_id' => $store_id,
                                    'quantity' => $item['quantity'] * $boxed_item['quantity'],
                                ];

                                if ($sale_type == 'CASR' || $sale_type == 'CRSR') {
                                    ItemsQuantity::where([['item_id', $boxed_item['item_id']],
                                        ['location_id', $store_id]])->increment('quantity', $item['quantity'] * $boxed_item['quantity']);
                                } else {
                                    ItemsQuantity::where([['item_id', $boxed_item['item_id']],
                                        ['location_id', $store_id]])->decrement('quantity', $item['quantity'] * $boxed_item['quantity']);
                                }
                            }
                        }
                    }

                    //set item list for sales item
                    $item_list[] = [
                        'sale_id' => $sales->sale_id,
                        'item_id' => $item['item_id'],
                        'description' => isset($item['description']) ? $item['description'] : null,
                        'serialnumber' => isset($item['serial']) ? $item['serial'] : null,
                        'sold_quantity' => $item['quantity'],
                        'item_cost_price' => bcmul($item['cost_price'], $item['quantity']),
                        'item_unit_price' => $item['unit_price'],
                        'item_sub_total' => $item['subTotal'],
                        'location_id' => $store_id,
                    ];
                    $line = 0;
                    foreach ($item['vatList'] as $vat) {
                        $line++;
                        $item_tax_list[] = [
                            'sale_id' => $sales->sale_id,
                            'item_id' => $item['item_id'],
                            'line' => $line,
                            'tax_name' => $vat['tax_name'],
                            'percent' => $vat['percent'],
                            'amount' => $vat['amount'],
                        ];
                    }
                }

                if (!$this->create_account_entry($sales->sale_id, $payment_info, $total_cost_price, $sale_type, $customer_id)) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => "sales.error_sales_or_update",
                        'info' => 'Error Inserting Account Entry',
                    ], 200);
                }

                SalesItem::insert($item_list);
                SalesItemsTaxes::insert($item_tax_list);
                Inventory::insert($inventory_list);

                Sale::where('sale_id', $sales->sale_id)->update(array('sale_status' => 1));
                DB::commit();

                return response()->json([
                    'status' => true,
                    'invoice_data' => $this->fetch_sale($sales->sale_id),
                    'message' => "sales.new_sales_or_update",
                ], 200);
            }
        } catch (\Exception$e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => "sales.error_sales_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    public function get_sale(Request $request)
    {
        $sale = $this->fetch_sale($request->input('id'));
        if ($sale) {
            return response()->json([
                'error' => false,
                'invoice_data' => $sale,
            ], 200);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'sales.no_invoice_number',
            ], 200);
        }
    }

    //get quatation
    private function fetch_sale($sale_id = null)
    {
        if ($sale_id) {
            $sale = Sale::with([
                'customer' => function ($query) {
                    $query->select(['name', 'mobile', 'email', 'company_name', 'vat_number', 'payment_type', 'customer_type', 'taxable', 'status', 'customer_id']);
                },
                'employee' => function ($query) {
                    $query->select(['name', 'employee_id']);
                },
            ])->find($sale_id);
        } else {
            $sale = Sale::with([
                'customer' => function ($query) {
                    $query->select(['name', 'mobile', 'email', 'company_name', 'vat_number', 'payment_type', 'customer_type', 'taxable', 'status', 'customer_id']);
                },
                'employee' => function ($query) {
                    $query->select(['name', 'employee_id']);
                },
            ])->latest('sale_id')->first();
        }

        if ($sale) {
            $items = SalesItem::with([
                'details' => function ($query) {
                    $query->select(['item_name', 'item_name_ar', 'category', 'item_id']);
                },
                'tax_details' => function ($query) use ($sale) {
                    $query->where('sale_id', $sale['sale_id'])->select(['percent', 'amount', 'item_id']);
                },
            ])->where('sale_id', $sale['sale_id'])->get();

            $ar = new \NumberFormatter('ar', \NumberFormatter::SPELLOUT);
            $en = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);

            $sale["items"] = $items->map(function ($item, $key) {
                return [
                    'item_id' => $item->item_id,
                    'item_name' => $item->details->item_name,
                    'item_name_ar' => $item->details->item_name_ar,
                    'category' => $item->details->category,
                    'description' => $item->description,
                    'serialnumber' => $item->serialnumber,
                    'sold_quantity' => $item->sold_quantity,
                    'item_unit_price' => $item->item_unit_price,
                    'item_sub_total' => $item->item_sub_total,
                    'location_id' => $item->location_id,
                    'tax_amount' => $item->tax_details->sum('amount'),
                    'tax_percent' => $item->tax_details->sum('percent'),
                ];
            });

            $sale['total_en'] = $en->format($sale['total']);
            $sale['total_ar'] = $ar->format($sale['total']);

            $tvl_generator = new GenerateTVL;
            $qr_data = $tvl_generator->generate("Hasib", '84375834758', $sale['created_at'], $sale['total'], $sale['tax']);
            $sale['qr_codes'] = base64_encode(QrCode::format('png')->size(300)->generate($qr_data));

            $sale["customer"] = $sale["customer"]->makeVisible(['customer_id'])->makeHidden("encrypted_customer");
            $sale["employee"] = $sale["employee"]->makeVisible(['employee_id'])->makeHidden("encrypted_employee");

            return $sale;
        } else {
            return false;
        }
    }

    private function create_account_entry($sale_id, $payment_info, $total_cost_price, $sale_type, $customer_id = null)
    {
        if ($sale_type == 'CASR' || $sale_type == 'CRSR') {
            $transaction_type = 'R';
        } else {
            $transaction_type = 'S';
        }

        $transactions_data = [
            'transaction_type' => 'S',
            'document_no' => $sale_id,
            'inserted_by' => decrypt(auth()->user()->encrypted_employee),
            'description' => $transaction_type == 'S' ? 'SALES ' . $sale_id : 'SALES RETURN ' . $sale_id,
        ];

        $transaction = AccountsTransaction::create($transactions_data);

        $ledger_data = [
            [
                'transaction_id' => $transaction->transaction_id,
                'account_id' => '241',
                'entry_type' => $transaction_type == 'S' ? 'D' : 'C',
                'amount' => $payment_info['total'],
                'person_id' => $customer_id ? $customer_id : null,
                'person_type' => $customer_id ? 'C' : null,
            ], [
                'transaction_id' => $transaction->transaction_id,
                'account_id' => '449',
                'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                'amount' => $payment_info['tax'],
            ],
            [
                'transaction_id' => $transaction->transaction_id,
                'account_id' => '500',
                'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                'amount' => $payment_info['subtotal'],
            ],
            [
                'transaction_id' => $transaction->transaction_id,
                'account_id' => '704',
                'entry_type' => $transaction_type == 'S' ? 'D' : 'C',
                'amount' => $total_cost_price,
            ],
            [
                'transaction_id' => $transaction->transaction_id,
                'account_id' => '211',
                'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                'amount' => $total_cost_price,
            ],
        ];

        if ($sale_type == 'CASR' || $sale_type == 'CAS') {
            if (count($payment_info['payment']) > 0) {
                //validate total payment is equal to toatl cart
                foreach ($payment_info['payment'] as $payment) {
                    $ledger_data[] = [
                        'transaction_id' => $transaction->transaction_id,
                        'account_id' => $payment['type'],
                        'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                        'amount' => $payment['amount'],
                    ];
                }
            }
        }

        $success = false;
        foreach ($ledger_data as $ledger) {
            $success = AccountLedgerEntry::insert($ledger);
            if (!$success) {
                return false;
            }
        }
        return true;
    }
}
