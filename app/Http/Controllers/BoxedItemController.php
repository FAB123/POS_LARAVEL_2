<?php

namespace App\Http\Controllers;

use App\Models\Item\BoxedItem;
use App\Models\Item\Item;
use App\Models\Item\ItemsQuantity;
use App\Models\Item\ItemsTax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BoxedItemController extends Controller
{
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

        $result = $query->boxeditems()->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'data' => $result,
        ], 200);
    }

    //save or update Boxed Item
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
            'reorder_level' => 0,
            'unit_type' => $request->input('unit_type'),
            'stock_type' => 0,
            'allowdesc' => $request->input('allowdesc') == 'true' ? 1 : 0,
            'is_serialized' => $request->input('is_serialized') == 'true' ? 1 : 0,
            'shelf' => $request->input('shelf'),
            'is_boxed' => 1,
            'description' => $request->input('description'),
        );

        isset($fileName) && $item['pic_filename'] = $fileName;

        try {
            DB::beginTransaction();
            $saved_item = Item::updateOrCreate([
                'item_id' => $request->input('itemID') ? decrypt($request->input('itemID')) : null,
            ], $item);

            ItemsTax::find($saved_item->item_id) && ItemsTax::find($saved_item->item_id)->delete();
            foreach (json_decode($request->input('vatList'), true) as $item) {
                ItemsTax::insert([
                    'item_id' => $saved_item->item_id,
                    'tax_name' => $item['tax_name'],
                    'percent' => $item['percent'],
                ]);
            }

            BoxedItem::find($saved_item->item_id) && BoxedItem::find($saved_item->item_id)->delete();
            foreach (json_decode($request->input('productList'), true) as $item) {
                BoxedItem::insert([
                    'boxed_item_id' => $saved_item->item_id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();
            return response()->json([
                'error' => false,
                'message' => "items.new_customer_or_update",
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

    //get boxed item by id
    public function get_item_by_id(Request $request)
    {
        $item_id = decrypt($request->input('item'));
        $item = Item::with(['vat_list', 'boxed_items'])->find($item_id);

        $item_quantity = ItemsQuantity::where('location_id', $request->input('store'))->find($item_id);
        $item['item_quantity'] = $item_quantity ? $item_quantity->quantity : "0.00";
        $item['pic_filename'] = $item->pic_filename ? asset('storage/item_img/' . $item->pic_filename) : null;

        $boxed_items = $item->boxed_items->map(function ($item) {
            return [
                'item_id' => $item['item_id'],
                'item_name' => $item['details']['item_name'],
                'unit_price' => $item['details']['unit_price'],
                'quantity' => $item['quantity'],
            ];
        });

        unset($item['boxed_items']);
        $item['boxed_items'] = $boxed_items;

        return response()->json([
            'data' => $item,
            'boxed_items' => $boxed_items,
        ], 200);
    }
}
