<?php

namespace App\Http\Controllers;

use App\Models\Account\AccountOpeningBalance;
use App\Models\Configurations\StockLocation;
use App\Models\Item\Item;
use App\Models\Item\ItemsQuantity;
use App\Models\Item\ItemsTax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    //get all items
    public function getAll(Request $request)
    {
        $query = Item::query();
        if ($request->input('keyword') != 'null') {
            $keyword = $request->input('keyword');
            $query->whereRaw("name LIKE '%" . $keyword . "%'")
                ->orWhereRaw("name_ar LIKE '%" . $keyword . "%'")
                ->orWhereRaw("category LIKE '%" . $keyword . "%'")
                ->orWhereRaw("cost LIKE '%" . $keyword . "%'")
                ->orWhereRaw("price LIKE '%" . $keyword . "%'")
                ->orWhereRaw("comments LIKE '%" . $keyword . "%'");
        }

        if ($request->input('sortitem') != 'null') {
            $query->orderBy($request->input('sortitem'), $request->input('sortdir'));
        }

        $page = $request->input('page', 1);
        $per_page = $request->input('size') ? $request->input('size') : 10;

        $result = $query->normalitems()->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'data' => $result,
        ], 200);
    }

    //save or update Item
    public function save_item(Request $request)
    {

        if ($request->hasFile('img')) {
            $image = $request->file('img');
            $fileName = Storage::disk('public')->put('item_img', $image, 'public');
            $fileName = explode("/", $fileName)[1];
        }

        $item = array(
            'barcode' => $request->input('barcode'),
            'item_name' => $request->input('item_name'),
            'item_name_ar' => $request->input('item_name_ar'),
            'category' => $request->input('category'),
            'cost_price' => $request->input('cost_price'),
            'unit_price' => $request->input('unit_price'),
            'wholesale_price' => $request->input('wholesale_price'),
            'minimum_price' => $request->input('minimum_price'),
            'reorder_level' => $request->input('reorder_level'),
            'unit_type' => $request->input('unit_type'),
            'stock_type' => $request->input('stock_type') == 'true' ? 1 : 0,
            'allowdesc' => $request->input('allowdesc') == 'true' ? 1 : 0,
            'is_serialized' => $request->input('is_serialized') == 'true' ? 1 : 0,
            'shelf' => $request->input('shelf'),
            'is_boxed' => 0,
            'description' => $request->input('description'),
        );

        isset($fileName) && $item['pic_filename'] = $fileName;

        try {
            //db transaction starting
            DB::beginTransaction();
            //insert or update item table
            $saved_item = Item::updateOrCreate([
                'item_id' => $request->input('itemID') ? decrypt($request->input('itemID')) : null,
            ], $item);

            ItemsTax::find($saved_item->item_id) && ItemsTax::find($saved_item->item_id)->delete();
            foreach (json_decode($request->input('vatList'), true) as $item) {
                if ($item['percent']) {
                    ItemsTax::insert([
                        'item_id' => $saved_item->item_id,
                        'tax_name' => $item['tax_name'],
                        'percent' => $item['percent'],
                    ]);
                }
            }

            if (!$request->input('itemID')) {
                // pos_items_quantities
                $locations = StockLocation::all();
                foreach ($locations as $location) {
                    ItemsQuantity::
                        updateOrInsert(
                        ['location_id' => $location['location_id'], 'item_id' => $saved_item['item_id']],
                        ['quantity' => 0]
                    );

                    AccountOpeningBalance::updateOrInsert(
                        ['account_sub_id' => $saved_item['item_id'], 'location_id' => $location['location_id'], 'account_id' => 211, 'year' => date('Y')],
                        ['amount' => 0, 'inserted_by' => decrypt(auth()->user()->encrypted_employee)]
                    );
                }
            }

            DB::commit();
            return response()->json([
                'error' => false,
                'item_id' => $saved_item->item_id,
                'message' => "items.new_item_or_update",
            ], 200);

        } catch (\Exception$e) {
            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => "items.error_new_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //delete Item from array
    public function delete_item(Request $request)
    {
        foreach ($request->input() as $item) {
            try {
                Item::find(decrypt($item))->delete();
            } catch (\Exception$e) {
                return response()->json([
                    'status' => false,
                    'message' => $e,
                ], 200);
            }
        }
        return response()->json([
            'status' => true,
            'message' => "items.delete",
        ], 200);
    }

    //get item by id
    public function get_item_by_id(Request $request)
    {
        $item_id = decrypt($request->input('item'));
        $item = Item::with('vat_list', 'opening_balance')->find($item_id);
        $item_quantity = ItemsQuantity::where('location_id', $request->header('Store'))->find($item_id);
        $item['item_quantity'] = $item_quantity ? $item_quantity->quantity : "0.00";
        $item['pic_filename'] = $item->pic_filename ? asset('storage/item_img/' . $item->pic_filename) : null;

        return response()->json([
            'data' => $item,
        ], 200);
    }

    //validate barcode
    public function validatebarcode(Request $request)
    {
        $item = Item::where('barcode', '=', $request->input('barcode'))->count();
        return response()->json([
            'status' => ($item > 0) ? false : true,
        ], 200);
    }

    //search_items by barcode or item name
    public function search_items(Request $request)
    {
        $store_id = $request->header('Store');
        $type = $request->input('type');
        $keyword = $request->input('query');
        $query = Item::query();

        if ($type == "P") {
            $query->where('is_boxed', 0);
            $query->where('stock_type', 1);
        }

        $query->whereRaw("item_name LIKE '%" . $keyword . "%'")
            ->orWhereRaw("item_name_ar LIKE '%" . $keyword . "%'");

        // $result = $query->first();
        // $result['item_quantity'] = ItemsQuantity::select('quantity')->where('location_id', $request->input('store'))->find($result->item_id)->quantity;
        $result = $query->with(['vat_list', 'item_unit'])->get();
        $result->makeVisible('item_id');

        $result->each(function ($item, $key) use ($store_id, $result) {
            if ($item['stock_type'] == '1') {
                $result[$key]['item_quantity'] = ItemsQuantity::select('quantity')->where('location_id', $store_id)->find($item["item_id"])->quantity;
            }
        });

        return response()->json([
            'data' => $result,
        ], 200);
    }

    //search_items by barcode or item name
    public function search_items_by_barcode(Request $request)
    {
        // $query = Item::query();
        $keyword = $request->input('query');
        $item = Item::where("barcode", $keyword)->orWhere("item_id", $keyword)->with(['vat_list', 'item_unit'])->first();
        if ($item) {
            $item->makeVisible('item_id');
            $item->makeHidden('encrypted_item');
            $item['item_quantity'] = ItemsQuantity::select('quantity')->where('location_id', $request->header('Store'))->find($item->item_id)->quantity;
        }

        return response()->json([
            'data' => $item,
        ], 200);
    }

    //search_category by barcode or item name
    public function search_category(Request $request)
    {
        $query = Item::query();
        $keyword = $request->input('query');
        $query->whereRaw("category LIKE '%" . $keyword . "%'");
        $query->select('category');
        $query->groupBy('category');
        $result = $query->get();

        return response()->json([
            'data' => $result,
        ], 200);
    }

    //insert bulk items from excel
    public function bulk_insert(Request $request)
    {
        $failed_data = array();
        foreach ($request->input() as $data) {
            try {
                Item::Create([
                    'barcode' => isset($data['barcode']) ? $data['barcode'] : null,
                    'item_name' => isset($data['item_name']) ? $data['item_name'] : null,
                    'item_name_ar' => isset($data['item_name_ar']) ? $data['item_name_ar'] : null,
                    'category' => isset($data['category']) ? $data['category'] : null,
                    'cost_price' => isset($data['cost_price']) ? $data['cost_price'] : null,
                    'unit_price' => isset($data['unit_price']) ? $data['unit_price'] : null,
                    'wholesale_price' => isset($data['wholesale_price']) ? $data['wholesale_price'] : null,
                    'minimum_price' => isset($data['minimum_price']) ? $data['minimum_price'] : null,
                    'reorder_level' => isset($data['reorder_level']) ? $data['reorder_level'] : null,
                    'allowdesc' => isset($data['allowdesc']) ? $data['allowdesc'] : null,
                    'is_serialized' => isset($data['allowserial']) ? $data['allowserial'] : null,
                    'shelf' => isset($data['shelf']) ? $data['shelf'] : null,
                    // 'vat' => isset($data['vat']) ? $data['vat'] : null,
                    'stock_type' => isset($data['stock_type']) ? $data['stock_type'] : null,
                    'unit_type' => isset($data['unit_type']) ? $data['unit_type'] : null,
                    'comments' => isset($data['comments']) ? $data['comments'] : null,
                ]);
            } catch (\Exception$e) {
                $failed_data[] = $data;
            }
        }
        return response()->json([
            'failed' => $failed_data,
        ], 200);
    }

    //search_items for oprning balance
    public function search_items_for_opening_balance(Request $request)
    {
        $store_id = $request->header('Store');
        $page = $request->input('page', 1);
        $per_page = $request->input('size') ? $request->input('size') : 10;
        $items = $this->fetch_ob_items($store_id, $per_page, $page);

        return response()->json([
            'items' => $items,
        ], 200);
    }

    public function save_items_opening_balance(Request $request)
    {
        $item = $request->input('item');
        $store_id = $request->header('Store');
        foreach ($request->input('item') as $item) {
            $item_id = $item['item_id'];
            DB::table('items_quantities')
                ->updateOrInsert(
                    ['location_id' => $store_id, 'item_id' => $item_id],
                    ['quantity' => $item['quantity']]
                );
            $opening_balance = bcmul($item['cost_price'], $item['quantity']);
            DB::table('account_opening_balances')
                ->updateOrInsert(
                    ['account_sub_id' => $item_id, 'location_id' => $store_id, 'account_id' => 211, 'year' => date('Y')],
                    ['amount' => $opening_balance, 'ob' => 1, 'inserted_by' => decrypt(auth()->user()->encrypted_employee)]
                );
        }

        $items = $this->fetch_ob_items($store_id, 10, 1);

        return response()->json([
            'items' => $items,
        ], 200);
    }

    private function fetch_ob_items($store, $per_page, $page)
    {
        // $items = Item::select('items.item_id as item_id',
        //     'items.item_name as item_name',
        //     'items.item_name_ar as item_name_ar',
        //     'items.cost_price as cost_price',
        //     DB::raw('0 as quantity'),
        //     DB::raw("CONCAT(pos_store_units.unit_name_en,' - ',pos_store_units.unit_name_ar)  AS unit_name"))
        //     ->join('store_units', 'items.unit_type', 'store_units.unit_id')
        //     ->rightJoin('account_opening_balances', 'items.item_id', 'account_opening_balances.account_sub_id')
        //     ->leftJoin('items_quantities', 'items.item_id', 'items_quantities.item_id')
        //     ->where('items.stock_type', '1')
        //     ->where('items.is_boxed', '0')
        //     ->where('account_opening_balances.account_id', '211')
        //     ->where('account_opening_balances.ob', '0')
        //     ->where('account_opening_balances.location_id', $store)
        //     ->paginate($per_page, ['*'], 'page', $page);
        // return $items;
        $items = AccountOpeningBalance::select('items.item_id as item_id',
            'items.item_name as item_name',
            'items.item_name_ar as item_name_ar',
            'items.cost_price as cost_price',
            DB::raw('0 as quantity'),
            DB::raw("CONCAT(pos_store_units.unit_name_en,' - ',pos_store_units.unit_name_ar)  AS unit_name"))
            ->where('account_opening_balances.account_id', '211')
            ->where('account_opening_balances.ob', '0')
            ->where('account_opening_balances.location_id', $store)
            ->join('items', 'account_opening_balances.account_sub_id', 'items.item_id')
            ->join('store_units', 'items.unit_type', 'store_units.unit_id')
            ->paginate($per_page, ['*'], 'page', $page);
        return $items;
    }

}

// if (ItemsQuantity::where('location_id', $request->input('store'))->find($saved_item->item_id)) {
//     ItemsQuantity::where('location_id', $request->input('store'))
//         ->where('item_id', $saved_item->item_id)
//         ->update(['quantity' => $request->input('item_quantity')]);
// } else {
//     ItemsQuantity::insert([
//         'item_id' => $saved_item->item_id,
//         'location_id' => $request->input('store'),
//         'quantity' => $request->input('item_quantity'),
//     ]);
// }

// //update or insert customere opening balance details
// if (AccountOpeningBalance::where('account_sub_id', $saved_item->item_id)->find(211)) {
//     AccountOpeningBalance::where('account_sub_id', $saved_item->item_id)
//         ->where('account_id', 211)
//         ->where('year', date('Y'))
//         ->update(['amount' => $request->input('opening_balance'), 'inserted_by' => decrypt(auth()->user()->encrypted_employee)]);
// } else {
//     AccountOpeningBalance::insert([
//         'account_id' => 211,
//         'account_sub_id' => $saved_item->item_id,
//         'amount' => $request->input('opening_balance'),
//         'inserted_by' => decrypt(auth()->user()->encrypted_employee),
//         'year' => date('Y'),
//     ]);
// }
