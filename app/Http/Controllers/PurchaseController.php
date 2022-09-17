<?php

namespace App\Http\Controllers;

use App\Models\Account\AccountLedgerEntry;
use App\Models\Account\AccountsTransaction;
use App\Models\Account\AccountVoucher;
use App\Models\Configurations\Configuration;
use App\Models\Inventory;
use App\Models\Item\Item;
use App\Models\Item\ItemsQuantity;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Purchase\PurchaseItemsTaxes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    //save or update Purchase
    public function save_purchase(Request $request)
    {
        $store_id = $request->header('Store');
        $purchase_type = $request->input('purchase_type');
        $cart_items = $request->input('cartItems');
        $supplier_info = $request->input('supplierInfo');
        $payment_info = $request->input('paymentInfo');
        $supplier_id = $supplier_info ? $supplier_info["supplier_id"] : null;

        if ($request->input('invoiceImage')) {
            $image_64 = $request->input('invoiceImage');
            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
            $image = str_replace($replace, '', $image_64);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(10) . '.png';
            Storage::disk('public')->put('purchase_bills/' . $imageName, base64_decode($image), 'public');
        }

        $purchase_data = array(
            'supplier_id' => $supplier_info ? $supplier_info["supplier_id"] : null,
            'employee_id' => decrypt(auth()->user()->encrypted_employee),
            'purchase_type' => $purchase_type,
            'sub_total' => $payment_info['subtotal'],
            'tax' => $payment_info['tax'],
            'total' => $payment_info['total'],
            'comments' => $request->input('comments'),
            'reference' => $request->input('reference'),
        );

        isset($imageName) && $purchase_data['pic_filename'] = $imageName;

        try {
            DB::beginTransaction();

            $purchase = Purchase::create($purchase_data);

            if ($purchase->purchase_id) {
                //db transaction starting

                $item_list = [];
                $item_tax_list = [];
                $inventory_list = [];
                foreach ($cart_items as $item) {
                    //adjust item quantity
                    if ($item['stock_type'] == '1') {
                        if ($purchase_type == 'CAPR' || $purchase_type == 'CRPR') {
                            $inventory_quantity = $item['quantity'] * -1;
                            ItemsQuantity::where([['item_id', $item['item_id']],
                                ['location_id', $store_id]])->decrement('quantity', $item['quantity']);
                        } else {
                            $inventory_quantity = $item['quantity'];
                            ItemsQuantity::where([['item_id', $item['item_id']],
                                ['location_id', $store_id]])->increment('quantity', $item['quantity']);
                        }

                        //insert inventory data
                        $inventory_list[] = [
                            'item_id' => $item['item_id'],
                            'trans_user' => decrypt(auth()->user()->encrypted_employee),
                            'trans_comment' => 'RECV ' . $purchase->purchase_id,
                            'location_id' => $store_id,
                            'quantity' => $inventory_quantity,
                        ];
                    }

                    $cur_item_info = Item::find($item['item_id']);

                    if ($cur_item_info->cost_price != $item['cost_price'] && Configuration::find('calc_average_cost') != "0") {
                        $this->calculate_avarage_cost($item['item_id'], $store_id, $item['cost_price'], $item['quantity'], $cur_item_info->cost_price);
                    }

                    //set item list for sales item
                    $item_list[] = [
                        'purchase_id' => $purchase->purchase_id,
                        'item_id' => $item['item_id'],
                        'description' => isset($item['description']) ? $item['description'] : null,
                        'serialnumber' => isset($item['serial']) ? $item['serial'] : null,
                        'purchase_quantity' => $item['quantity'],
                        'item_cost_price' => $item['cost_price'],
                        'location_id' => $store_id,
                    ];

                    $line = 0;
                    foreach ($item['vatList'] as $vat) {
                        $line++;
                        $item_tax_list[] = [
                            'purchase_id' => $purchase->purchase_id,
                            'item_id' => $item['item_id'],
                            'line' => $line,
                            'tax_name' => $vat['tax_name'],
                            'percent' => $vat['percent'],
                            'amount' => $vat['amount'],
                        ];
                    }
                }

                if (!$this->create_account_entry($purchase->purchase_id, $payment_info, $purchase_type, $supplier_id)) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => "purchase.error_purchases_or_update",
                        'info' => 'Error Inserting Account Entry',
                    ], 200);
                }

                PurchaseItem::insert($item_list);
                PurchaseItemsTaxes::insert($item_tax_list);
                Inventory::insert($inventory_list);
                DB::commit();

                return response()->json([
                    'status' => true,
                    'receipt_id' => $purchase->purchase_id,
                    'message' => "purchase.new_purchases_or_update",
                ], 200);
            }
        } catch (\Exception$e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => "purchase.error_purchases_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    public function get_purchase_image(Request $request)
    {
        $purchase_id = $request->input('purchase_id');
        $purchase = Purchase::find($purchase_id);
        $pic_filename = $purchase->pic_filename ? asset('storage/purchase_bills/' . $purchase->pic_filename) : null;
        return response()->json([
            'status' => true,
            'pic_filename' => $pic_filename,
        ], 200);
    }

    public function calculate_avarage_cost($item_id, $store_id, $new_cost, $new_quantity, $current_cost)
    {

        $current_stock = ItemsQuantity::where([['item_id', $item_id], ['location_id', $store_id]])->first()->quantity;
        $total_quantity = bcadd($current_stock, $new_quantity);

        $average_price = bcdiv(bcadd(bcmul($new_quantity, $new_cost), bcmul($current_stock, $current_cost)), $total_quantity);
        return Item::where('item_id', $item_id)->update(array('cost_price' => $average_price));
    }

    private function create_account_entry($purchase_id, $payment_info, $purchase_type, $supplier_id = null)
    {
        try {
            $transaction_type = ($purchase_type == 'CAPR' || $purchase_type == 'CRPR') ? 'R' : 'P';
            $description = ($transaction_type == 'P') ? 'PURCHASE' : 'PURCHASE RETURN';
            $transactions_data = [
                'transaction_type' => 'P',
                'document_no' => $purchase_id,
                'inserted_by' => decrypt(auth()->user()->encrypted_employee),
                'description' => "{$description} {$purchase_id}",
            ];

            //db transaction starting
            DB::beginTransaction();
            $transaction = AccountsTransaction::create($transactions_data);

            $ledger_data = [
                [
                    'transaction_id' => $transaction->transaction_id,
                    'account_id' => '431',
                    'entry_type' => $transaction_type == 'P' ? 'C' : 'D',
                    'amount' => $payment_info['total'],
                    'person_id' => $supplier_id ? $supplier_id : null,
                    'person_type' => $supplier_id ? 'S' : null,
                ], [
                    'transaction_id' => $transaction->transaction_id,
                    'account_id' => '449',
                    'entry_type' => $transaction_type == 'P' ? 'D' : 'C',
                    'amount' => $payment_info['tax'],
                ],
                [
                    'transaction_id' => $transaction->transaction_id,
                    'account_id' => '211',
                    'entry_type' => $transaction_type == 'P' ? 'C' : 'D',
                    'amount' => $payment_info['subtotal'],
                ],
            ];

            foreach ($ledger_data as $ledger) {
                AccountLedgerEntry::insert($ledger);
            }

            if ($purchase_type == 'CAPR' || $purchase_type == 'CAP') {
                if (count($payment_info['payment']) > 0) {
                    //validate total payment is equal to toatl cart

                    $voucher = AccountVoucher::create(['document_type' => 'TR']);

                    $type = $transaction_type == 'P' ? 'PURCHASE' : 'PURCHASE RETURN';

                    $voucher_transactions_data = [
                        'transaction_type' => 'TR',
                        'document_no' => $voucher->document_id,
                        'inserted_by' => decrypt(auth()->user()->encrypted_employee),
                        'description' => 'Payment Against ' . $type . ' - ' . $purchase_id,
                    ];
                    $voucher_transaction = AccountsTransaction::create($voucher_transactions_data);

                    foreach ($payment_info['payment'] as $payment) {
                        $voucher_ledger_from_data = [
                            'transaction_id' => $voucher_transaction->transaction_id,
                            'account_id' => 431,
                            'entry_type' => $transaction_type == 'P' ? 'D' : 'C',
                            'amount' => $payment['amount'],
                            'person_id' => $supplier_id ? $supplier_id : null,
                            'person_type' => $supplier_id ? 'C' : null,
                        ];
                        AccountLedgerEntry::insert($voucher_ledger_from_data);

                        $voucher_ledger_to_data = [
                            'transaction_id' => $voucher_transaction->transaction_id,
                            'account_id' => $payment['type'],
                            'entry_type' => $transaction_type == 'P' ? 'C' : 'D',
                            'amount' => $payment['amount'],
                        ];

                        AccountLedgerEntry::insert($voucher_ledger_to_data);
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
