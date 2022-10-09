<?php

namespace App\Http\Controllers;

use App\Models\Account\AccountLedgerEntry;
use App\Models\Account\AccountsTransaction;
use App\Models\Account\AccountVoucher;
use App\Models\Inventory;
use App\Models\Item\BoxedItem;
use App\Models\Item\Item;
use App\Models\Item\ItemsQuantity;
use App\Models\Purchase\PurchaseItem;
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
                        'discount' => $item['discount'],
                        'discount_type' => $item['discount_type'],
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

    public function get_sales_history(Request $request)
    {
        $type = $request->input('type');
        $customer = $request->input('customer');
        $item_id = $request->input('item_id');
        if ($type == "salesHistory") {
            $data = SalesItem::join('sales', 'sales.sale_id', 'sales_items.sale_id')
                ->select('created_at as date', 'sales.sale_id as sale_id', 'item_unit_price as price', 'sold_quantity as quantity')
                ->where('item_id', $item_id)->limit(15)->get();
        } else if ($type == "customerHistory") {
            $data = SalesItem::join('sales', 'sales.sale_id', 'sales_items.sale_id')
                ->select('created_at as date', 'sales.sale_id as sale_id', 'item_unit_price as price', 'sold_quantity as quantity')
                ->where('sales.customer_id', $customer)
                ->where('item_id', $item_id)->limit(15)->get();
        } else if ($type == "costHistory") {
            $data = PurchaseItem::join('purchases', 'purchases.purchase_id', 'purchase_items.purchase_id')
                ->select('created_at as date', 'purchases.purchase_id as sale_id', 'item_cost_price as price', 'purchase_quantity as quantity')
                ->where('item_id', $item_id)->limit(15)->get();
        } else if ($type == "details") {
            $data = Item::join('items_quantities', 'items_quantities.item_id', 'items.item_id')
                ->select('item_name', 'item_name_ar', 'category', 'shelf', 'cost_price', 'unit_price', 'wholesale_price', 'minimum_price')
                ->where('items.item_id', $item_id)->first();
        }

        return response()->json([
            'status' => true,
            'data' => $data,
        ], 200);
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
                    'discount' => $item->discount,
                    'discount_type' => $item->discount_type,
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
        try {
            $transaction_type = ($sale_type == 'CASR' || $sale_type == 'CRSR') ? 'R' : 'S';
            $description = ($transaction_type == 'S') ? 'SALES' : 'SALES RETURN';
            $transactions_data = [
                'transaction_type' => 'S',
                'document_no' => $sale_id,
                'inserted_by' => decrypt(auth()->user()->encrypted_employee),
                'description' => "{$description} {$sale_id}",
            ];

            //db transaction starting
            DB::beginTransaction();
            $transaction = AccountsTransaction::create($transactions_data);
            $ledger_data = [
                [
                    'transaction_id' => $transaction->transaction_id,
                    'account_id' => '704', //cost of goods sold
                    'entry_type' => $transaction_type == 'S' ? 'D' : 'C',
                    'amount' => $total_cost_price,
                ],
                [
                    'transaction_id' => $transaction->transaction_id,
                    'account_id' => '211', //stock
                    'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                    'amount' => $total_cost_price,
                ],
            ];

            if ($sale_type == 'CASR' || $sale_type == 'CAS') {
                if ($customer_id) {
                    $ledger_data[] = [
                        'transaction_id' => $transaction->transaction_id,
                        'account_id' => '241', //account recivable
                        'entry_type' => $transaction_type == 'S' ? 'D' : 'C',
                        'amount' => $payment_info['total'],
                        'person_id' => $customer_id ? $customer_id : null,
                        'person_type' => $customer_id ? 'C' : null,
                    ];
                    $ledger_data[] = [
                        'transaction_id' => $transaction->transaction_id,
                        'account_id' => '449', //tax payable
                        'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                        'amount' => $payment_info['tax'],
                    ];
                    $ledger_data[] = [
                        'transaction_id' => $transaction->transaction_id,
                        'account_id' => '500', //sales
                        'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                        'amount' => $payment_info['subtotal'],
                    ];
                } else {
                    if (count($payment_info['payment']) > 0) {
                        $available_amount = 0;
                        $tax_payment_done = false;
                        $payable_tax = $payment_info['tax'];
                        $payable_amount = $payment_info['subtotal'];
                        foreach ($payment_info['payment'] as $payment) {
                            $available_amount = $payment['amount'];
                            //insert amount to specified ledger
                            $ledger_data[] = [
                                'transaction_id' => $transaction->transaction_id,
                                'account_id' => $payment['type'],
                                'entry_type' => $transaction_type == 'S' ? 'D' : 'C',
                                'amount' => $available_amount,
                                'person_id' => $customer_id ? $customer_id : null,
                                'person_type' => $customer_id ? 'C' : null,
                            ];

                            while ($available_amount > 0) {
                                if (!$tax_payment_done) {
                                    $ledger_data[] = [
                                        'transaction_id' => $transaction->transaction_id,
                                        'account_id' => '449', //tax payabel
                                        'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                                        'amount' => ($available_amount >= $payable_tax) ? $payable_tax : $available_amount,
                                    ];
                                    //update availabe fund
                                    if ($available_amount >= $payable_tax) {
                                        $available_amount = bcsub($available_amount, $payable_tax, 2);
                                        $tax_payment_done = true;
                                    } else {
                                        $payable_tax = bcsub($payable_tax, $available_amount, 2);
                                        $available_amount = 0;
                                    }
                                }

                                if ($available_amount > 0) {
                                    $ledger_data[] = [
                                        'transaction_id' => $transaction->transaction_id,
                                        'account_id' => '500', //sales
                                        'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                                        'amount' => ($available_amount >= $payable_amount) ? $payable_amount : $available_amount,
                                    ];

                                    //update availabe fund
                                    if ($available_amount >= $payable_amount) {
                                        $available_amount = bcsub($available_amount, $payable_amount, 2);
                                    } else {
                                        $payable_amount = bcsub($payable_amount, $available_amount, 2);
                                        $available_amount = 0;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $ledger_data[] = [
                    'transaction_id' => $transaction->transaction_id,
                    'account_id' => '241', //account recivable
                    'entry_type' => $transaction_type == 'S' ? 'D' : 'C',
                    'amount' => $payment_info['total'],
                    'person_id' => $customer_id ? $customer_id : null,
                    'person_type' => $customer_id ? 'C' : null,
                ];
                $ledger_data[] = [
                    'transaction_id' => $transaction->transaction_id,
                    'account_id' => '449', //tax payable
                    'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                    'amount' => $payment_info['tax'],
                ];
                $ledger_data[] = [
                    'transaction_id' => $transaction->transaction_id,
                    'account_id' => '500', //sales
                    'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                    'amount' => $payment_info['subtotal'],
                ];
            }

            //applying aditional discount
            if ($payment_info['discount'] != 0) {
                $ledger_data[] = [
                    'transaction_id' => $transaction->transaction_id,
                    'account_id' => '821', //aditional discount
                    'entry_type' => $transaction_type == 'S' ? 'D' : 'C',
                    'amount' => $payment_info['discount'],
                ];
                $ledger_data[] = [
                    'transaction_id' => $transaction->transaction_id,
                    'account_id' => $customer_id ? '241' : $payment_info['payment'][0]['type'],
                    'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                    'person_id' => $customer_id ? $customer_id : null,
                    'amount' => $payment_info['discount'],
                ];
            }

            foreach ($ledger_data as $ledger) {
                AccountLedgerEntry::insert($ledger);
            }

            //inserting payment vouchar
            if ($sale_type == 'CAS' || $sale_type == 'CASR') {
                if ($customer_id) {
                    if (count($payment_info['payment']) > 0) {
                        $voucher = AccountVoucher::create(['document_type' => 'TP']);
                        $type = $transaction_type == 'S' ? 'SALES' : 'SALES RETURN';
                        $voucher_transactions_data = [
                            'transaction_type' => 'TR',
                            'document_no' => $voucher->document_id,
                            'inserted_by' => decrypt(auth()->user()->encrypted_employee),
                            'description' => 'Voucher Against ' . $type . ' - ' . $sale_id,
                        ];
                        $voucher_transaction = AccountsTransaction::create($voucher_transactions_data);
                        foreach ($payment_info['payment'] as $payment) {
                            $voucher_ledger_from_data = [
                                'transaction_id' => $voucher_transaction->transaction_id,
                                'account_id' => 241,
                                'entry_type' => $transaction_type == 'S' ? 'C' : 'D',
                                'amount' => $payment['amount'],
                                'person_id' => $customer_id ? $customer_id : null,
                                'person_type' => $customer_id ? 'C' : null,
                            ];
                            AccountLedgerEntry::insert($voucher_ledger_from_data);

                            $voucher_ledger_to_data = [
                                'transaction_id' => $voucher_transaction->transaction_id,
                                'account_id' => $payment['type'],
                                'entry_type' => $transaction_type == 'S' ? 'D' : 'C',
                                'amount' => $payment['amount'],
                            ];

                            AccountLedgerEntry::insert($voucher_ledger_to_data);
                        }
                    }
                }
            }
            DB::commit();
        } catch (\Exception$e) {

            DB::rollBack();
            info($e);
            return false;
        }
        return true;
    }
}
