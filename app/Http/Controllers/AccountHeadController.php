<?php

namespace App\Http\Controllers;

use App\Models\Account\AccountHead;
use App\Models\Account\AccountOpeningBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountHeadController extends Controller
{
    //List All account Heads
    public function get_all_account_heads()
    {
        $account_heads = AccountHead::all();
        $grouped = $account_heads->groupBy(['account_type'])->all();
        $list = ["Asset", 'Capital', 'Liabilities', 'Income', 'Expenses'];
        $renamed = [];
        foreach ($grouped as $k => $v) {
            foreach ($v as $l => $m) {
                if ($m->account_id > 9) {
                    $sub_type = $this->account_sub_type($m->account_id);
                    $renamed[$list[$k - 1]][$sub_type][] = $m;
                }
            }
        }

        return response()->json([
            'data' => $renamed,
        ], 200);
    }

    public function get_all_account_head_list()
    {
        $head_list = DB::table('account_heads')
            ->select('account_name as name', 'account_id as value')
            ->where('account_id', '>', 8)
            ->get();
        return response()->json([
            'data' => $head_list,
        ], 200);
    }

    public function get_all_account_payment_head_list()
    {
        $head_list = DB::table('account_heads')
            ->select('account_name as label', 'account_id as value')
            ->whereBetween('account_id', [201, 210])
            ->get();
        return response()->json([
            'data' => $head_list,
        ], 200);
    }

    public function update_account_heads(Request $request)
    {
        $account_id = $request->input("account_id");
        $value = $request->input("value");
        $status = AccountHead::where('account_id', $account_id)->update(array('account_name' => $value));
        return response()->json([
            'status' => $status,
        ], 200);
    }

    public function get_account_heads_openning_balance()
    {
        try {
            $balances = AccountOpeningBalance::whereNotIn('account_id', array('211', '241', '431'))->with('account_details')->get();
            return response()->json([
                'status' => true,
                'accounts' => $balances,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e,
            ], 200);
        }
    }

    public function update_account_head_ob(Request $request)
    {
        $account_id = $request->input("account_id");
        $value = $request->input("value");
        $entry_type = $request->input("entryType");
        $status = AccountOpeningBalance::where('account_id', $account_id)->update(array('amount' => $value, 'entry_type' => $entry_type));
        return response()->json([
            'status' => $status,
        ], 200);
    }

    public function delete_account_head(Request $request)
    {
        $account_id = $request->input("account_id");
        try {
            AccountHead::find($account_id)->delete();
            return response()->json([
                'status' => true,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e,
            ], 200);
        }
    }

    public function create_new_account_head(Request $request)
    {
        $inputs = $request->all();
        $data = [
            'account_id' => $inputs["account_code"],
            'account_name' => $inputs["account_name"],
            'account_name_ar' => $inputs["account_name_ar"],
            'account_type' => $inputs["nature"],
            'comments' => $inputs["comments"],
            'inserted_by' => decrypt(auth()->user()->encrypted_employee),
            'editable' => 1,
        ];

        try {
            AccountHead::insert($data);
            return response()->json([
                'status' => true,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e,
            ], 200);
        }
    }

    public function validate_account_head(Request $request)
    {
        $account_id = $request->input("account_id");
        try {
            $item = AccountHead::find($account_id);
            if ($item) {
                return response()->json([
                    'status' => false,
                ], 200);
            } else {
                return response()->json([
                    'status' => true,
                ], 200);
            }
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e,
            ], 200);
        }
    }

    private function account_sub_type($type)
    {
        $f_letter = substr($type, 0, 1);
        $list = ["Fixed Asset", "Current Asset", 'Capital', 'Liabilities', 'Direct Income', 'Indirect Income', 'Direct Expenses', 'Indirect Expenses'];
        return $list[$f_letter - 1];
    }

}
