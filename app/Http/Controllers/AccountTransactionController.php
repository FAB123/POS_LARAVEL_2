<?php

namespace App\Http\Controllers;

use App\Models\Account\AccountLedgerEntry;
use App\Models\Account\AccountsTransaction;
use App\Models\Account\AccountVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountTransactionController extends Controller
{
    public function save_voucher_data(Request $request)
    {
        try {
            $inputs = $request->all();
            $transaction_type = $inputs["transaction_type"];
            DB::beginTransaction();
            $voucher = AccountVoucher::create(['document_type' => $transaction_type]);
            $transactions_data = [
                'transaction_type' => $transaction_type,
                'document_no' => $voucher->document_id,
                'inserted_by' => decrypt(auth()->user()->encrypted_employee),
                'description' => $inputs["comments"],
            ];

            $transaction = AccountsTransaction::create($transactions_data);

            $ledger_data = [];
            if ($transaction_type == 'TJ') {
                foreach ($inputs['account_list'] as $item) {
                    $ledger_data[] = [
                        'transaction_id' => $transaction->transaction_id,
                        'account_id' => $item['account_id'],
                        'entry_type' => $item['type'],
                        'amount' => $item['amount'],
                        'person_id' => $inputs['account_holder'],
                        'person_type' => $inputs['account_holder_type'],
                    ];
                }
            } else {
                $total_amount = 0;
                foreach ($inputs['account_list'] as $item) {
                    $ledger_data[] = [
                        'transaction_id' => $transaction->transaction_id,
                        'account_id' => $item['account_id'],
                        'entry_type' => $transaction_type == 'TP' ? 'D' : 'C',
                        'amount' => $item['amount'],
                        'person_id' => $inputs['account_holder'],
                        'person_type' => $inputs['account_holder_type'],
                    ];
                    $total_amount += $item['amount'];
                }

                $ledger_data[] = [
                    'transaction_id' => $transaction->transaction_id,
                    'account_id' => '202',
                    'entry_type' => $transaction_type == 'TP' ? 'C' : 'D',
                    'amount' => $total_amount,
                    'person_id' => $inputs['account_holder'],
                    'person_type' => $inputs['account_holder_type'],
                ];
            }

            AccountLedgerEntry::insert($ledger_data);
            DB::commit();

            return response()->json([
                'status' => true,
            ], 200);
        } catch (\Exception$e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $e,
            ], 200);
        }
    }

}
