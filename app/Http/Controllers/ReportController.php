<?php

namespace App\Http\Controllers;

use App\Models\Account\AccountOpeningBalance;
use App\Models\Account\AccountsTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    //get all Detailed Sales
    public function get_all_detailed_sales($from, $to, $option1, $location, $page, $size, $sortitem, $sortdir)
    {
        $per_page = $size ? $size : 10;
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('sales_items')
            ->select(
                'sales.sale_id as sale_id',
                'sales.created_at as created_at',
                'sales.comments as comments',
                'sales.bill_type as bill_type',
                'sales.sale_type as sale_type',
                'sales.customer_id as customer_id',
                DB::raw('pos_sales.sub_total AS sub_total'),
                DB::raw('pos_sales.tax AS tax'),
                DB::raw('pos_sales.total AS total'),
                // DB::raw('(CASE WHEN pos_sales.sale_type = \'CAS\' OR pos_sales.sale_type = \'CRS\' THEN pos_sales.tax ELSE CONCAT(\'-\', pos_sales.tax) END) AS tax'),
                // DB::raw('(CASE WHEN pos_sales.sale_type = \'CAS\' OR pos_sales.sale_type = \'CRS\' THEN pos_sales.total ELSE CONCAT(\'-\', pos_sales.total) END) AS total'),
                DB::raw('SUM(pos_sales_items.sold_quantity) as sold_quantity'),
                DB::raw('SUM(pos_sales_items.item_cost_price) as item_cost_price'),
                'customers.name as customer_name',
                'employees.name as employee_name')
            ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.customer_id')
            ->leftJoin('employees', 'sales.employee_id', '=', 'employees.employee_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('sale_id')
            ->paginate($per_page, ['*'], 'page', $page);

        $summary_data = DB::table('sales_items')
            ->join('sales', 'sales_items.sale_id', 'sales.sale_id')
            ->select(
                DB::raw('SUM(pos_sales_items.sold_quantity) as quantity'),
                DB::raw('SUM(pos_sales_items.item_cost_price) as cost_price'),
                DB::raw('(pos_sales.sub_total) as subtotal'),
                DB::raw('(pos_sales.tax) as tax'),
                DB::raw('(pos_sales.total) as total'),
            )
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->groupBy('sales_items.sale_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "cost_price" => $collection->sum('cost_price'),
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all Detailed Purchase
    public function get_all_detailed_purchases($from, $to, $option1, $location, $page, $size, $sortitem, $sortdir)
    {
        $per_page = $size ? $size : 10;
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('purchase_items')
            ->select(
                'purchases.*',
                DB::raw('SUM(pos_purchase_items.purchase_quantity) as purchase_quantity'),
                'suppliers.name as supplier_name', 'employees.name as employee_name')
            ->join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.supplier_id')
            ->leftJoin('employees', 'purchases.employee_id', '=', 'employees.employee_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('purchase_type', $option1);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('purchase_id')
            ->paginate($per_page, ['*'], 'page', $page);

        $summary_data = DB::table('purchase_items')
            ->select(
                DB::raw('SUM(pos_purchase_items.purchase_quantity) as quantity'),
                DB::raw('(pos_purchases.sub_total) as subtotal'),
                DB::raw('(pos_purchases.tax) as tax'),
                DB::raw('(pos_purchases.total) as total'),
            )
            ->join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('purchase_type', $option1);
            })
            ->groupBy('purchase_items.purchase_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "cost_price" => $collection->sum('cost_price'),
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all Detailed Workorder
    public function get_all_detailed_workorder($from, $to, $location, $page, $size, $sortitem, $sortdir)
    {
        $per_page = $size ? $size : 10;
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('workorders_items')
            ->select('workorders.*', DB::raw('SUM(pos_workorders_items.workorder_quantity) as workorder_quantity'),
                'customers.name as customer_name', 'employees.name as employee_name')
            ->join('workorders', 'workorders_items.workorder_id', '=', 'workorders.workorder_id')
            ->leftJoin('customers', 'workorders.customer_id', '=', 'customers.customer_id')
            ->leftJoin('employees', 'workorders.employee_id', '=', 'employees.employee_id')
            ->whereBetween('workorders.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('workorders.location_id', $location);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('workorder_id')
            ->paginate($per_page, ['*'], 'page', $page);

        $summary_data = DB::table('workorders_items')
            ->select(
                DB::raw('SUM(pos_workorders_items.workorder_quantity) as quantity'),
                DB::raw('SUM(pos_workorders.sub_total) as subtotal'),
                DB::raw('SUM(pos_workorders.tax) as tax'),
                DB::raw('SUM(pos_workorders.total) as total'),
            )
            ->join('workorders', 'workorders_items.workorder_id', '=', 'workorders.workorder_id')
            ->whereBetween('workorders.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('workorders.location_id', $location);
            })
            ->groupBy('workorders.workorder_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all Detailed Quotation
    public function get_all_detailed_quotation($from, $to, $location, $page, $size, $sortitem, $sortdir)
    {
        $per_page = $size ? $size : 10;
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('quotations_items')
            ->select('quotations.*', DB::raw('SUM(pos_quotations_items.quotation_quantity) as quotation_quantity'),
                'customers.name as customer_name', 'employees.name as employee_name')
            ->join('quotations', 'quotations_items.quotation_id', '=', 'quotations_items.quotation_id')
            ->leftJoin('customers', 'quotations.customer_id', '=', 'customers.customer_id')
            ->leftJoin('employees', 'quotations.employee_id', '=', 'employees.employee_id')
            ->whereBetween('quotations.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('quotations.location_id', $location);
            })

            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('quotation_id')
            ->paginate($per_page, ['*'], 'page', $page);

        $summary_data = DB::table('quotations_items')
            ->select(
                DB::raw('SUM(pos_quotations_items.quotation_quantity) as quantity'),
                DB::raw('SUM(pos_quotations.sub_total) as subtotal'),
                DB::raw('SUM(pos_quotations.tax) as tax'),
                DB::raw('SUM(pos_quotations.total) as total'),
            )
            ->join('quotations', 'quotations_items.quotation_id', '=', 'quotations_items.quotation_id')
            ->whereBetween('quotations.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('quotations.location_id', $location);
            })
            ->groupBy('quotations_items.quotation_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all summary sales
    public function get_all_summary_sales($from, $to, $option1, $location, $page, $size, $sortitem, $sortdir)
    {
        $per_page = $size ? $size : 10;
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('sales_items')
            ->select(DB::raw('DATE(pos_sales.created_at) as date'),
                DB::raw('SUM(pos_sales_items.sold_quantity) as sold_quantity'),
                DB::raw('SUM(pos_sales_items.item_cost_price) as item_cost_price'),
                DB::raw('SUM(pos_sales.sub_total) as sub_total'),
                DB::raw('SUM(pos_sales.tax) as tax'),
                DB::raw('SUM(pos_sales.total) as total'),
            )
            ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy(DB::raw('DATE(pos_sales.created_at)'))
            ->paginate($per_page, ['*'], 'page', $page);

        $summary_data = DB::table('sales_items')
            ->select(
                DB::raw('SUM(pos_sales_items.sold_quantity) as quantity'),
                DB::raw('SUM(pos_sales_items.item_cost_price) as cost_price'),
                DB::raw('pos_sales.sub_total as subtotal'),
                DB::raw('pos_sales.tax as tax'),
                DB::raw('pos_sales.total as total'),
            )
            ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->groupBy('sales_items.sale_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "cost_price" => $collection->sum('cost_price'),
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        // ->whereDate('created_at', '>=', now()->subDays(30))

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all specific customer sales
    public function get_specific_customer_detailed_sales($from, $to, $option1, $option2, $location, $page, $size, $sortitem, $sortdir)
    {
        $per_page = $size ? $size : 10;
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('sales_items')
            ->select('sales.*', DB::raw('SUM(pos_sales_items.sold_quantity) as sold_quantity'), DB::raw('SUM(pos_sales_items.item_cost_price) as item_cost_price'),
                'customers.name as customer_name', 'employees.name as employee_name')
            ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.customer_id')
            ->leftJoin('employees', 'sales.employee_id', '=', 'employees.employee_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sales.sale_type', $option1);
            })
            ->when($option2 != 'ALL', function ($query) use ($option2) {
                $query->where('sales.customer_id', $option2);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('sale_id')
            ->paginate($per_page, ['*'], 'page', $page);

        $summary_data = DB::table('sales_items')
            ->select(
                DB::raw('SUM(pos_sales_items.sold_quantity) as quantity'),
                DB::raw('SUM(pos_sales_items.item_cost_price) as cost_price'),
                DB::raw('pos_sales.sub_total as subtotal'),
                DB::raw('pos_sales.tax as tax'),
                DB::raw('pos_sales.total as total'),
            )
            ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->when($option2 != 'ALL', function ($query) use ($option2) {
                $query->where('sales.customer_id', $option2);
            })
            ->groupBy('sales_items.sale_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "cost_price" => $collection->sum('cost_price'),
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                    "profit" => $collection->sum('subtotal') - $collection->sum('cost_price'),
                ];
            });

        // ->whereDate('created_at', '>=', now()->subDays(30))

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all specific employee sales
    public function get_specific_employee_detailed_sales($from, $to, $option1, $option2, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('sales_items')
            ->select('sales.*', DB::raw('SUM(pos_sales_items.sold_quantity) as sold_quantity'), DB::raw('SUM(pos_sales_items.item_cost_price) as item_cost_price'),
                'customers.name as customer_name', 'employees.name as employee_name')
            ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.customer_id')
            ->leftJoin('employees', 'sales.employee_id', '=', 'employees.employee_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->when($option2 != 'ALL', function ($query) use ($option2) {
                $query->where('sales.employee_id', $option2);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('sale_id')
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('sales_items')
            ->select(
                DB::raw('SUM(pos_sales_items.sold_quantity) as quantity'),
                DB::raw('SUM(pos_sales_items.item_cost_price) as cost_price'),
                DB::raw('pos_sales.sub_total as subtotal'),
                DB::raw('pos_sales.tax as tax'),
                DB::raw('pos_sales.total as total'),
            )
            ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->when($option2 != 'ALL', function ($query) use ($option2) {
                $query->where('sales.employee_id', $option2);
            })
            ->groupBy('sales_items.sale_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "cost_price" => $collection->sum('cost_price'),
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        // ->whereDate('created_at', '>=', now()->subDays(30))

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all specific cateogory sales
    public function get_specific_category_summary_sales($from, $to, $option1, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('sales_items')
            ->select(
                'category',
                DB::raw('SUM(pos_sales_items.sold_quantity) as sold_quantity'),
                DB::raw('SUM(pos_sales_items.item_cost_price) as item_cost_price'),
                DB::raw('SUM(pos_sales_items.item_sub_total) as sub_total'),
            )
            ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
            ->join('items', 'sales_items.item_id', '=', 'items.item_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('category')
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('sales_items')
            ->select(
                DB::raw('SUM(pos_sales_items.sold_quantity) as quantity'),
                DB::raw('SUM(pos_sales_items.item_cost_price) as cost_price'),
                DB::raw('SUM(pos_sales_items.item_sub_total) as subtotal'),
            )
            ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->groupBy('sales_items.sale_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "cost_price" => $collection->sum('cost_price'),
                    "subtotal" => $collection->sum('subtotal'),
                ];
            });

        // ->whereDate('created_at', '>=', now()->subDays(30))

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all specific cateogory sales
    public function get_item_summary_sales($from, $to, $option1, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('sales_items')
            ->select(
                'item_name', 'item_name_ar',
                DB::raw('SUM(pos_sales_items.sold_quantity) as sold_quantity'),
                DB::raw('SUM(pos_sales_items.item_cost_price) as item_cost_price'),
                DB::raw('SUM(pos_sales_items.item_sub_total) as sub_total'),
            )
            ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
            ->join('items', 'sales_items.item_id', '=', 'items.item_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('item_name', 'item_name_ar')
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('sales_items')
            ->select(
                DB::raw('SUM(pos_sales_items.sold_quantity) as quantity'),
                DB::raw('SUM(pos_sales_items.item_cost_price) as cost_price'),
                DB::raw('SUM(pos_sales_items.item_sub_total) as subtotal'),
            )
            ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->groupBy('sales_items.sale_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "cost_price" => $collection->sum('cost_price'),
                    "subtotal" => $collection->sum('subtotal'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all summary purchases
    public function get_all_summary_purchases($from, $to, $option1, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('purchase_items')
            ->select(DB::raw('DATE(pos_purchases.created_at) as date'),
                DB::raw('SUM(pos_purchase_items.purchase_quantity) as purchase_quantity'),
                DB::raw('SUM(pos_purchase_items.item_cost_price) as item_cost_price'),
                DB::raw('SUM(pos_purchases.sub_total) as sub_total'),
                DB::raw('SUM(pos_purchases.tax) as tax'),
                DB::raw('SUM(pos_purchases.total) as total'),
            )
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.purchase_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('sale_type', $option1);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy(DB::raw('DATE(pos_purchases.created_at)'))
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('purchase_items')
            ->select(
                DB::raw('SUM(pos_purchase_items.purchase_quantity) as quantity'),
                DB::raw('pos_purchases.sub_total as subtotal'),
                DB::raw('pos_purchases.tax as tax'),
                DB::raw('pos_purchases.total as total'),
            )
            ->join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('purchase_type', $option1);
            })
            ->groupBy('purchases.purchase_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all specific supplier purchase
    public function get_specific_supplier_detailed_purchase($from, $to, $option1, $option2, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('purchase_items')
            ->select('purchases.*', DB::raw('SUM(pos_purchase_items.purchase_quantity) as purchase_quantity'),
                'suppliers.name as supplier_name', 'employees.name as employee_name')
            ->join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.supplier_id')
            ->leftJoin('employees', 'purchases.employee_id', '=', 'employees.employee_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('purchase_type', $option1);
            })
            ->when($option2 != 'ALL', function ($query) use ($option2) {
                $query->where('purchases.supplier_id', $option2);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('purchase_id')
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('purchase_items')
            ->select(
                DB::raw('SUM(pos_purchase_items.purchase_quantity) as quantity'),
                DB::raw('pos_purchases.sub_total as subtotal'),
                DB::raw('pos_purchases.tax as tax'),
                DB::raw('pos_purchases.total as total'),
            )
            ->join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('purchase_type', $option1);
            })
            ->when($option2 != 'ALL', function ($query) use ($option2) {
                $query->where('purchases.supplier_id', $option2);
            })
            ->groupBy('purchases.purchase_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all specific employee purchase
    public function get_specific_employee_detailed_purchase($from, $to, $option1, $option2, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('purchase_items')
            ->select('purchases.*', DB::raw('SUM(pos_purchase_items.purchase_quantity) as purchase_quantity'),
                'suppliers.name as supplier_name', 'employees.name as employee_name')
            ->join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.supplier_id')
            ->leftJoin('employees', 'purchases.employee_id', '=', 'employees.employee_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('purchase_type', $option1);
            })
            ->when($option2 != 'ALL', function ($query) use ($option2) {
                $query->where('purchases.employee_id', $option2);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('purchase_id')
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('purchase_items')
            ->select(
                DB::raw('SUM(pos_purchase_items.purchase_quantity) as quantity'),
                DB::raw('pos_purchases.sub_total as subtotal'),
                DB::raw('pos_purchases.tax as tax'),
                DB::raw('pos_purchases.total as total'),
            )
            ->join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('purchase_type', $option1);
            })
            ->when($option2 != 'ALL', function ($query) use ($option2) {
                $query->where('purchases.employee_id', $option2);
            })
            ->groupBy('purchases.purchase_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all specific cateogory purchase
    public function get_category_summary_purchase($from, $to, $option1, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('purchase_items')
            ->select(
                'category',
                DB::raw('SUM(pos_purchase_items.purchase_quantity) as purchase_quantity'),
                DB::raw('SUM(pos_purchase_items.item_cost_price) as item_cost_price'),
                DB::raw('SUM(pos_purchase_items.item_sub_total) as sub_total'),
            )
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.purchase_id')
            ->join('items', 'purchase_items.item_id', '=', 'items.item_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('purchase_type', $option1);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('category')
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('purchase_items')
            ->select(
                DB::raw('SUM(pos_purchase_items.purchase_quantity) as quantity'),
                DB::raw('SUM(pos_purchase_items.item_sub_total) as subtotal'),
                DB::raw('SUM(pos_purchase_items.item_cost_price) as cost_price'),
            )
            ->join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('purchase_type', $option1);
            })
            ->groupBy('purchases.purchase_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "cost_price" => $collection->sum('cost_price'),
                    "subtotal" => $collection->sum('subtotal'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all specific cateogory purchase
    public function get_item_summary_purchase($from, $to, $option1, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('purchase_items')
            ->select(
                'item_name', 'item_name_ar',
                DB::raw('SUM(pos_purchase_items.purchase_quantity) as purchase_quantity'),
                DB::raw('SUM(pos_purchase_items.item_cost_price) as item_cost_price'),
                DB::raw('SUM(pos_purchase_items.item_sub_total) as sub_total'),
            )
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.purchase_id')
            ->join('items', 'purchase_items.item_id', '=', 'items.item_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('purchase_type', $option1);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('item_name', 'item_name_ar', )
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('purchase_items')
            ->select(
                DB::raw('SUM(pos_purchase_items.purchase_quantity) as quantity'),
                DB::raw('SUM(pos_purchase_items.item_cost_price) as cost_price'),
                DB::raw('SUM(pos_purchase_items.item_sub_total) as subtotal'),
            )
            ->join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($option1 != 'ALL', function ($query) use ($option1) {
                $query->where('purchase_type', $option1);
            })
            ->groupBy('purchases.purchase_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "cost_price" => $collection->sum('cost_price'),
                    "subtotal" => $collection->sum('subtotal'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all summary workorder
    public function get_all_summary_workorder($from, $to, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('workorders_items')
            ->select(DB::raw('DATE(pos_workorders.created_at) as date'),
                DB::raw('SUM(pos_workorders_items.workorder_quantity) as workorder_quantity'),
                DB::raw('SUM(pos_workorders.sub_total) as sub_total'),
                DB::raw('SUM(pos_workorders.tax) as tax'),
                DB::raw('SUM(pos_workorders.total) as total'),
            )
            ->join('workorders', 'workorders_items.workorder_id', '=', 'workorders.workorder_id')
            ->whereBetween('workorders.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('workorders.location_id', $location);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy(DB::raw('DATE(pos_workorders.created_at)'))
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('workorders_items')
            ->select(
                DB::raw('SUM(pos_workorders_items.workorder_quantity) as quantity'),
                DB::raw('pos_workorders.sub_total as subtotal'),
                DB::raw('pos_workorders.tax as tax'),
                DB::raw('pos_workorders.total as total'),
            )
            ->join('workorders', 'workorders_items.workorder_id', '=', 'workorders.workorder_id')
            ->whereBetween('workorders.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('workorders.location_id', $location);
            })
            ->groupBy('workorders_items.workorder_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get all summary qutatation
    public function get_all_summary_qutatation($from, $to, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('quotations_items')
            ->select(DB::raw('DATE(pos_quotations.created_at) as date'),
                DB::raw('SUM(pos_quotations_items.quotation_quantity) as quotation_quantity'),
                DB::raw('SUM(pos_quotations.sub_total) as sub_total'),
                DB::raw('SUM(pos_quotations.tax) as tax'),
                DB::raw('SUM(pos_quotations.total) as total'),
            )
            ->join('quotations', 'quotations_items.quotation_id', '=', 'quotations.quotation_id')
            ->whereBetween('quotations.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('quotations.location_id', $location);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy(DB::raw('DATE(pos_quotations.created_at)'))
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('quotations_items')
            ->select(
                DB::raw('SUM(pos_quotations_items.quotation_quantity) as quantity'),
                DB::raw('pos_quotations.sub_total as subtotal'),
                DB::raw('pos_quotations.tax as tax'),
                DB::raw('pos_quotations.total as total'),
            )
            ->join('quotations', 'quotations_items.quotation_id', '=', 'quotations.quotation_id')
            ->whereBetween('quotations.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('quotations.location_id', $location);
            })
            ->groupBy('quotations_items.quotation_id')
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get inventory summary
    public function get_inventory_summary($location, $page, $size = 10, $sortitem, $sortdir)
    {
        $data = DB::table('items')
            ->select('items.*', DB::raw('SUM(pos_items_quantities.quantity) as quantity'))
            ->join('items_quantities', 'items.item_id', '=', 'items_quantities.item_id')
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('items_quantities.location_id', $location);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->where('items.stock_type', 1)
            ->groupBy('items_quantities.item_id')
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('items')
            ->select(DB::raw('SUM(pos_items.cost_price) as cost_price'),
                DB::raw('SUM(pos_items.wholesale_price) as wholesale_price'),
                DB::raw('SUM(pos_items.unit_price) as unit_price'),
                DB::raw('SUM(pos_items_quantities.quantity) as quantity'))
            ->join('items_quantities', 'items.item_id', '=', 'items_quantities.item_id')
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('items_quantities.location_id', $location);
            })
            ->where('items.stock_type', 1)
            ->get()
            ->pipe(function ($collection) {
                return [
                    "quantity" => $collection->sum('quantity'),
                    "cost_price" => $collection->sum('cost_price'),
                    "wholesale_price" => $collection->sum('wholesale_price'),
                    "unit_price" => $collection->sum('unit_price'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get low inventory
    public function get_low_inventory($location, $page, $size = 10, $sortitem, $sortdir)
    {
        $summary_items = DB::table('items')
            ->select('items.*', DB::raw('SUM(pos_items_quantities.quantity) as quantity'))
            ->join('items_quantities', 'items.item_id', '=', 'items_quantities.item_id')
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('items_quantities.location_id', $location);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->where('items.stock_type', 1)
            ->where('items_quantities.quantity', '<=', 'items.reorder_level')
            ->groupBy('items_quantities.item_id')
            ->paginate($size, ['*'], 'page', $page);

        return response()->json([
            'data' => $summary_items,
        ], 200);
    }

    //get sales tax
    public function get_sales_tax($from, $to, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('sales')
            ->select('sales.*', 'employees.name as employee_name', 'customers.name as customer_name', 'customers.vat_number as customer_vat_number')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.customer_id')
            ->join('employees', 'sales.employee_id', '=', 'employees.employee_id')
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('sales.sale_id')
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('sales')
            ->select(
                DB::raw('SUM(pos_sales.sub_total) as subtotal'),
                DB::raw('SUM(pos_sales.tax) as tax'),
                DB::raw('SUM(pos_sales.total) as total'),
            )
            ->whereBetween('sales.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('sales.location_id', $location);
            })
            ->get()
            ->pipe(function ($collection) {
                return [
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get purchase tax
    public function get_purchase_tax($from, $to, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('purchases')
            ->select('purchases.*', 'employees.name as employee_name', 'suppliers.name as supplier_name', 'suppliers.vat_number as supplier_vat_number')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.supplier_id')
            ->join('employees', 'purchases.employee_id', '=', 'employees.employee_id')
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->when($sortitem != 'null', function ($query) use ($sortitem, $sortdir) {
                $query->orderBy($sortitem, $sortdir);
            })
            ->groupBy('purchases.purchase_id')
            ->paginate($size, ['*'], 'page', $page);

        $summary_data = DB::table('purchases')
            ->select(
                DB::raw('SUM(pos_purchases.sub_total) as subtotal'),
                DB::raw('SUM(pos_purchases.tax) as tax'),
                DB::raw('SUM(pos_purchases.total) as total'),
            )
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchases.location_id', $location);
            })
            ->get()
            ->pipe(function ($collection) {
                return [
                    "subtotal" => $collection->sum('subtotal'),
                    "tax" => $collection->sum('tax'),
                    "total" => $collection->sum('total'),
                ];
            });

        return response()->json([
            'data' => $data,
            'summary_data' => $summary_data,
        ], 200);
    }

    //get generate tax
    public function get_generate_tax_reports($from, $to, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $data = DB::table('purchase_items_taxes')
            ->join('purchases', 'purchase_items_taxes.purchase_id', '=', 'purchases.purchase_id')
            ->join('purchase_items', 'purchase_items.purchase_id', '=', 'purchase_items_taxes.purchase_id')
            ->select(
                DB::raw('"Purchase" as type'),
                DB::raw('SUM(pos_purchase_items.item_cost_price) as subtotal'),
                DB::raw('pos_purchase_items_taxes.percent as percent'),
                DB::raw('SUM(pos_purchase_items_taxes.amount) as tax'),
                DB::raw('SUM(pos_purchases.total) as total'),
            )
            ->whereBetween('purchases.created_at', [$from_date, $to_date])
            ->when($location != 'ALL', function ($query) use ($location) {
                $query->where('purchase_items.location_id', $location);
            })
            ->groupBy('purchase_items_taxes.percent')
            ->union(DB::table('sales_items')
                    ->select(
                        DB::raw('"Sales" as type'),
                        DB::raw('SUM(pos_sales_items.item_sub_total) as subtotal'),
                        DB::raw('pos_sales_items_taxes.percent as percent'),
                        DB::raw('SUM(pos_sales_items_taxes.amount) as tax'),
                        DB::raw('SUM(pos_sales.total) as total'),
                    )
                    ->whereBetween('sales.created_at', [$from_date, $to_date])
                    ->join('sales', 'sales_items.sale_id', '=', 'sales.sale_id')
                    ->join('sales_items_taxes', 'sales.sale_id', '=', 'sales_items_taxes.sale_id')

                    ->when($location != 'ALL', function ($query) use ($location) {
                        $query->where('sales_items.location_id', $location);
                    })
                    ->groupBy('sales_items_taxes.percent'))
            ->paginate();

        return response()->json([
            'data' => $data,
        ], 200);
    }

    //account general journal report
    public function get_general_journal($from, $to, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $general_journal = DB::table('accounts_transactions')
            ->select('accounts_transactions.created_at AS Date',
                'accounts_transactions.description AS DescriptionOrAccountTitle',
                DB::raw('null as AmountDebit'),
                DB::raw('null AS AmountCredit'),
                'accounts_transactions.transaction_id AS Reference',
                DB::raw('null AS sortID'),
                DB::raw('null AS IsLine'))
            ->leftJoin('account_ledger_entries', 'account_ledger_entries.transaction_id', '=', 'accounts_transactions.transaction_id')
            ->leftJoin('account_heads', 'account_heads.account_id', '=', 'account_ledger_entries.account_id')
            ->whereBetween('accounts_transactions.created_at', [$from_date, $to_date])
            ->union(DB::table('accounts_transactions')
                    ->select(DB::raw('null AS Date'),
                        DB::raw('(CASE WHEN pos_account_ledger_entries.entry_type = \'D\' THEN pos_account_heads.account_name ELSE CONCAT(\'-  \', pos_account_heads.account_name) END) AS DescriptionOrAccountTitle'),
                        DB::raw('(CASE WHEN pos_account_ledger_entries.entry_type = \'D\' THEN pos_account_ledger_entries.amount ELSE null END) AS AmountDebit'),
                        DB::raw('(CASE WHEN pos_account_ledger_entries.entry_type = \'C\' THEN pos_account_ledger_entries.amount ELSE null END) AS AmountDebit'),
                        'accounts_transactions.transaction_id AS Reference',
                        'account_ledger_entries.id AS sortID',
                        DB::raw('(CASE WHEN pos_account_ledger_entries.entry_type = \'D\' THEN 1 ELSE 2 END) AS IsLine'))
                    ->leftJoin('account_ledger_entries', 'account_ledger_entries.transaction_id', '=', 'accounts_transactions.transaction_id')
                    ->leftJoin('account_heads', 'account_heads.account_id', '=', 'account_ledger_entries.account_id')
                // ->whereBetween('accounts_transactions.created_at', [urldecode($from), urldecode($to)]))
                    ->whereBetween('accounts_transactions.created_at', [$from_date, $to_date]))
            ->orderByRaw("Reference ASC,sortID ASC, IsLine ASC")
            ->paginate($size, ['*'], 'page', $page);

        return response()->json([
            'data' => $general_journal,
        ], 200);
    }

    public function get_ledger_accounts_balances($from, $to, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $ledger_accounts_balances = DB::table('accounts_transactions')
            ->select('account_ledger_entries.account_id',
                'account_heads.account_name',
                'account_heads.account_name_ar',
                DB::raw('SUM(CASE WHEN pos_account_ledger_entries.entry_type=\'D\' THEN pos_account_ledger_entries.amount ELSE -pos_account_ledger_entries.amount END) AS Balance'))
            ->leftJoin('account_ledger_entries', 'account_ledger_entries.transaction_id', '=', 'accounts_transactions.transaction_id')
            ->leftJoin('account_heads', 'account_heads.account_id', '=', 'account_ledger_entries.account_id')
        // ->where('t.transaction_date', '<=', '2018-06-30')
            ->groupBy('account_ledger_entries.account_id')
            ->orderByRaw('CAST(pos_account_ledger_entries.account_id AS CHAR) ASC')
            ->paginate($size, ['*'], 'page', $page);
        return response()->json([
            'data' => $ledger_accounts_balances,
        ], 200);
    }

    public function get_ledger_details($from, $to, $option1, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $ledger_ob_summary = AccountOpeningBalance::select(
            DB::raw('SUM(CASE WHEN pos_account_opening_balances.entry_type=\'D\' THEN pos_account_opening_balances.amount ELSE 0.00 END) AS OpeningDebit'),
            DB::raw('SUM(CASE WHEN pos_account_opening_balances.entry_type=\'C\' THEN pos_account_opening_balances.amount ELSE 0.00 END) AS OpeningCredit'))
            ->where('account_opening_balances.account_id', '=', $option1)
            ->union(DB::table('accounts_transactions')
                    ->select(
                        DB::raw('SUM(CASE WHEN pos_account_ledger_entries.entry_type=\'D\' THEN pos_account_ledger_entries.amount ELSE 0.00 END) AS OpeningDebit'),
                        DB::raw('SUM(CASE WHEN pos_account_ledger_entries.entry_type=\'C\' THEN pos_account_ledger_entries.amount ELSE 0.00 END) AS OpeningCredit'))
                    ->leftJoin('account_ledger_entries', 'account_ledger_entries.transaction_id', '=', 'accounts_transactions.transaction_id')
                    ->where('accounts_transactions.created_at', '<', $from_date)
                    ->where('account_ledger_entries.account_id', '=', $option1))

            ->get()->pipe(function ($collection) {
            return collect([(object) [
                'created_at' => '',
                'description' => 'Opening Balance',
                'DebitAmount' => $collection->sum('OpeningDebit') ? $collection->sum('OpeningDebit') : null,
                'CreditAmount' => $collection->sum('OpeningCredit') ? $collection->sum('OpeningCredit') : null,
            ]]
            );
        });

        $balance = 0;
        $ledger_account = AccountsTransaction::select(
            'accounts_transactions.created_at',
            'accounts_transactions.description',
            DB::raw('(CASE WHEN pos_account_ledger_entries.entry_type=\'D\' THEN pos_account_ledger_entries.amount ELSE NULL END) AS DebitAmount'),
            DB::raw('(CASE WHEN pos_account_ledger_entries.entry_type=\'C\' THEN pos_account_ledger_entries.amount ELSE NULL END) AS CreditAmount'))
            ->leftJoin('account_ledger_entries', 'account_ledger_entries.transaction_id', '=', 'accounts_transactions.transaction_id')
            ->whereBetween('accounts_transactions.created_at', [$from_date, $to_date])
            ->where('account_ledger_entries.account_id', '=', $option1)
            ->orderBy('accounts_transactions.created_at', 'ASC')
            ->paginate($size, ['*'], 'page', $page);

        $temp_collection = $ledger_ob_summary->merge($ledger_account->getCollection());
        $ledger_account->setCollection($temp_collection);
        $ledger_account->getCollection()->transform(function ($item) use (&$balance) {
            $row_balance = $item->DebitAmount - $item->CreditAmount;
            $balance = $balance + $row_balance;
            $item->balance = $balance;
            return $item;
        });

        return response()->json([
            'data' => $ledger_account,
            'info' => $ledger_ob_summary,
        ], 200);
    }

    //customer ledger
    public function get_customer_ledger_details($from, $to, $option1, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $ledger_ob_summary = AccountOpeningBalance::select(
            DB::raw('SUM(CASE WHEN pos_account_opening_balances.entry_type=\'D\' THEN pos_account_opening_balances.amount ELSE 0.00 END) AS OpeningDebit'),
            DB::raw('SUM(CASE WHEN pos_account_opening_balances.entry_type=\'C\' THEN pos_account_opening_balances.amount ELSE 0.00 END) AS OpeningCredit'))
            ->where('account_opening_balances.account_id', '=', '241')
            ->where('account_opening_balances.account_sub_id', '=', $option1)
            ->union(DB::table('accounts_transactions')
                    ->select(
                        DB::raw('SUM(CASE WHEN pos_account_ledger_entries.entry_type=\'D\' THEN pos_account_ledger_entries.amount ELSE 0.00 END) AS OpeningDebit'),
                        DB::raw('SUM(CASE WHEN pos_account_ledger_entries.entry_type=\'C\' THEN pos_account_ledger_entries.amount ELSE 0.00 END) AS OpeningCredit'))
                    ->leftJoin('account_ledger_entries', 'account_ledger_entries.transaction_id', '=', 'accounts_transactions.transaction_id')
                    ->where('accounts_transactions.created_at', '<', $from_date)
                    ->where('account_ledger_entries.person_type', '=', 'C')
                    ->where('account_ledger_entries.person_id', '=', $option1)
                    ->where('account_ledger_entries.account_id', '=', '241'))

            ->get()->pipe(function ($collection) {
            return collect([(object) [
                'created_at' => '',
                'description' => 'Opening Balance',
                'DebitAmount' => $collection->sum('OpeningDebit') ? $collection->sum('OpeningDebit') : null,
                'CreditAmount' => $collection->sum('OpeningCredit') ? $collection->sum('OpeningCredit') : null,
            ]]
            );
        });

        $balance = 0;
        $ledger_account = AccountsTransaction::select(
            'accounts_transactions.created_at',
            'accounts_transactions.description',
            DB::raw('(CASE WHEN pos_account_ledger_entries.entry_type=\'D\' THEN pos_account_ledger_entries.amount ELSE NULL END) AS DebitAmount'),
            DB::raw('(CASE WHEN pos_account_ledger_entries.entry_type=\'C\' THEN pos_account_ledger_entries.amount ELSE NULL END) AS CreditAmount'))
            ->leftJoin('account_ledger_entries', 'account_ledger_entries.transaction_id', '=', 'accounts_transactions.transaction_id')
            ->whereBetween('accounts_transactions.created_at', [$from_date, $to_date])
            ->where('account_ledger_entries.person_type', '=', 'C')
            ->where('account_ledger_entries.account_id', '=', '241')
            ->where('account_ledger_entries.person_id', '=', $option1)
            ->orderBy('accounts_transactions.created_at', 'ASC')
            ->paginate($size, ['*'], 'page', $page);

        $temp_collection = $ledger_ob_summary->merge($ledger_account->getCollection());
        $ledger_account->setCollection($temp_collection);
        $ledger_account->getCollection()->transform(function ($item) use (&$balance) {
            $row_balance = $item->DebitAmount - $item->CreditAmount;
            $balance = $balance + $row_balance;
            $item->balance = $balance;
            return $item;
        });

        return response()->json([
            'data' => $ledger_account,
            'info' => $ledger_ob_summary,
        ], 200);
    }

    //supplier ledger
    public function get_supplier_ledger_details($from, $to, $option1, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');
        $ledger_ob_summary = AccountOpeningBalance::select(
            DB::raw('SUM(CASE WHEN pos_account_opening_balances.entry_type=\'D\' THEN pos_account_opening_balances.amount ELSE 0.00 END) AS OpeningDebit'),
            DB::raw('SUM(CASE WHEN pos_account_opening_balances.entry_type=\'C\' THEN pos_account_opening_balances.amount ELSE 0.00 END) AS OpeningCredit'))
            ->where('account_opening_balances.account_id', '=', '431')
            ->where('account_opening_balances.account_sub_id', '=', $option1)
            ->union(DB::table('accounts_transactions')
                    ->select(
                        DB::raw('SUM(CASE WHEN pos_account_ledger_entries.entry_type=\'D\' THEN pos_account_ledger_entries.amount ELSE 0.00 END) AS OpeningDebit'),
                        DB::raw('SUM(CASE WHEN pos_account_ledger_entries.entry_type=\'C\' THEN pos_account_ledger_entries.amount ELSE 0.00 END) AS OpeningCredit'))
                    ->leftJoin('account_ledger_entries', 'account_ledger_entries.transaction_id', '=', 'accounts_transactions.transaction_id')
                    ->where('accounts_transactions.created_at', '<', $from_date)
                    ->where('account_ledger_entries.person_type', '=', 'S')
                    ->where('account_ledger_entries.person_id', '=', $option1)
                    ->where('account_ledger_entries.account_id', '=', '431'))

            ->get()->pipe(function ($collection) {
            return collect([(object) [
                'created_at' => '',
                'description' => 'Opening Balance',
                'DebitAmount' => $collection->sum('OpeningDebit') ? $collection->sum('OpeningDebit') : null,
                'CreditAmount' => $collection->sum('OpeningCredit') ? $collection->sum('OpeningCredit') : null,
            ]]
            );
        });

        $balance = 0;
        $ledger_account = AccountsTransaction::select(
            'accounts_transactions.created_at',
            'accounts_transactions.description',
            DB::raw('(CASE WHEN pos_account_ledger_entries.entry_type=\'D\' THEN pos_account_ledger_entries.amount ELSE NULL END) AS DebitAmount'),
            DB::raw('(CASE WHEN pos_account_ledger_entries.entry_type=\'C\' THEN pos_account_ledger_entries.amount ELSE NULL END) AS CreditAmount'))
            ->leftJoin('account_ledger_entries', 'account_ledger_entries.transaction_id', '=', 'accounts_transactions.transaction_id')
            ->whereBetween('accounts_transactions.created_at', [$from_date, $to_date])
            ->where('account_ledger_entries.person_type', '=', 'S')
            ->where('account_ledger_entries.account_id', '=', '431')
            ->where('account_ledger_entries.person_id', '=', $option1)
            ->orderBy('accounts_transactions.created_at', 'ASC')
            ->paginate($size, ['*'], 'page', $page);

        $temp_collection = $ledger_ob_summary->merge($ledger_account->getCollection());
        $ledger_account->setCollection($temp_collection);
        $ledger_account->getCollection()->transform(function ($item) use (&$balance) {
            $row_balance = $item->DebitAmount - $item->CreditAmount;
            $balance = $balance + $row_balance;
            $item->balance = $balance;
            return $item;
        });

        return response()->json([
            'data' => $ledger_account,
            'info' => $ledger_ob_summary,
        ], 200);
    }

    //account reports
    public function get_trail_balance($from, $to, $location, $page, $size = 10, $sortitem, $sortdir)
    {
        // $from_date = Carbon::parse(urldecode($from))->settings(['toStringFormat' => 'Y-m-d H:i:s']);
        // $to_date = Carbon::parse(urldecode($to))->settings(['toStringFormat' => 'Y-m-d H:i:s']);

        $from_date = Carbon::parse(urldecode($from))->setTimezone('Asia/Qatar');
        $to_date = Carbon::parse(urldecode($to))->setTimezone('Asia/Qatar');

        $trail_balance = AccountsTransaction::
            select('account_ledger_entries.account_id', 'account_heads.account_name',
            DB::raw('SUM(CASE WHEN pos_accounts_transactions.created_at < "' . $from_date . '"
                  AND pos_account_ledger_entries.entry_type=\'D\'
                  THEN pos_account_ledger_entries.amount ELSE 0.0 END) AS TotalDebitOpening'),
            DB::raw('SUM(CASE WHEN pos_accounts_transactions.created_at < "' . $from_date . '"
                 AND pos_account_ledger_entries.entry_type=\'C\'
                 THEN pos_account_ledger_entries.amount ELSE 0.0 END) AS TotalCreditOpening'),
            DB::raw('SUM(CASE WHEN DATE(pos_accounts_transactions.created_at) >= "' . $from_date . '"
                 AND DATE(pos_accounts_transactions.created_at) < "' . $to_date . '"
                 AND pos_account_ledger_entries.entry_type=\'D\'
                 THEN pos_account_ledger_entries.amount ELSE 0.0 END) AS DebitTransactionPeriod'),
            DB::raw('SUM(CASE WHEN DATE(pos_accounts_transactions.created_at) >= "' . $from_date . '"
                 AND DATE(pos_accounts_transactions.created_at) < "' . $to_date . '"
                 AND pos_account_ledger_entries.entry_type=\'C\'
                 THEN pos_account_ledger_entries.amount ELSE 0.0 END) AS CreditTransactionPeriod'),
            DB::raw('SUM(CASE WHEN DATE(pos_accounts_transactions.created_at) >= "' . $to_date . '"
                AND pos_account_ledger_entries.entry_type=\'D\'
                THEN pos_account_ledger_entries.amount ELSE 0.0 END) AS TotalDebitClosing'),
            DB::raw('SUM(CASE WHEN DATE(pos_accounts_transactions.created_at) >= "' . $to_date . '"
                 AND pos_account_ledger_entries.entry_type=\'C\'
                 THEN pos_account_ledger_entries.amount ELSE 0.0 END) AS TotalCreditClosing'))
            ->leftJoin('account_ledger_entries', 'account_ledger_entries.transaction_id', '=', 'accounts_transactions.transaction_id')
            ->leftJoin('account_heads', 'account_heads.account_id', '=', 'account_ledger_entries.account_id')
        // ->where('accounts_transactions.created_at', '<=', $to_date)
            ->groupBy('account_ledger_entries.account_id')
            ->orderByRaw('CAST(pos_account_ledger_entries.account_id AS CHAR) ASC')
            ->paginate($size, ['*'], 'page', $page);

        $trail_balance[] = [
            'account_name' => 'Total',
            'TotalDebitOpening' => $trail_balance->sum('TotalDebitOpening'),
            'TotalCreditOpening' => $trail_balance->sum('TotalCreditOpening'),
            'DebitTransactionPeriod' => $trail_balance->sum('DebitTransactionPeriod'),
            'CreditTransactionPeriod' => $trail_balance->sum('CreditTransactionPeriod'),
            'TotalDebitClosing' => $trail_balance->sum('TotalDebitClosing'),
            'TotalCreditClosing' => $trail_balance->sum('TotalCreditClosing'),
        ];

        return response()->json([
            'data' => $trail_balance,
        ], 200);
    }
}
