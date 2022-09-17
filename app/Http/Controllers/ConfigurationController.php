<?php

namespace App\Http\Controllers;

use App\Models\Configurations\Configuration;
use App\Models\Configurations\DinnerTable;
use App\Models\Configurations\StockLocation;
use App\Models\Configurations\StoreUnit;
use App\Models\Configurations\TaxScheme;
use App\Models\PaymentOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigurationController extends Controller
{
    public function getConfigDatas()
    {
        $configuration_data = Configuration::all()->map(function ($item) {
            return [$item['key'] => $item['value']];
        });

        $tax_scheme = TaxScheme::all();
        return response()->json([
            'configuration_data' => $configuration_data->flatMap(function ($item) {return $item;})->all(),
            'tax_scheme' => $tax_scheme,
        ], 200);
    }

    //save store config
    public function save_configuration(Request $request)
    {
        // $configurations = [
        //     'company_name' => $request->input('companyname'),
        //     'company_name_ar' => $request->input('companyname_ar'),
        //     'company_address' => $request->input('address'),
        //     'company_address_ar' => $request->input('address_ar'),
        //     'return_policy' => $request->input('return_policy'),
        //     'return_policy_ar' => $request->input('return_policy_ar'),
        //     'vat_number' => $request->input('vat_number'),
        //     'company_email' => $request->input('email'),
        //     'company_telephone' => $request->input('telephone'),
        //     'include_tax' => $request->input('include_tax'),
        //     'company_fiscal_year_start' => $request->input('financial_year'),
        //     'application_lang' => $request->input('language'),
        //     'currency_symbol' => $request->input('currency_symbol'),
        //     'barcode_billing' => $request->input('barcode_billing'),
        //     //'api_provider' => $request->input('api_provider'),
        //     'sms_api_sender_id' => $request->input('sms_api_sender_id'),
        //     'sms_api_username' => $request->input('sms_api_username'),
        //     'sms_api_password' => $request->input('sms_api_password'),
        //     'email_api_username' => $request->input('smtp_username'),
        //     'email_api_password' => $request->input('smtp_password'),
        //     'email_smtp_server' => $request->input('smtp_server'),
        //     'email_smtp_port' => $request->input('smtp_port'),
        //     'email_smtp_encryption_type' => $request->input('smtp_encryption'),
        // ];
        $configurations = $request->all();

        try {
            info($configurations);
            DB::beginTransaction();
            foreach ($configurations as $k => $v) {
                if ($k != 'vatList') {
                    Configuration::updateOrCreate([
                        'key' => $k,
                    ], [
                        'value' => $v,
                    ]);
                } else {
                    //insert vat scheme
                }
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'configuration.configuration_saved',
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 200);
        }

    }

    //stock location section
    //get all locations
    public function get_all_stores()
    {
        $stores = StockLocation::all('location_id', 'location_name_en', 'location_name_ar');
        return response()->json([
            'data' => $stores,
        ], 200);
    }

    //get locations by id
    public function get_store_by_id(Request $request)
    {
        $store = StockLocation::find($request->input("location_id"));
        return response()->json([
            'data' => $store,
        ], 200);
    }

    //delete locations by id
    public function delete_store_by_id(Request $request)
    {
        try {
            $store = StockLocation::all()->count();
            if ($store > 1) {
                StockLocation::find($request->input("location_id"))->delete();
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "store.delete_lessthan_one_store",
                ], 200);
            }

        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e,
            ], 200);
        }

        $stores = StockLocation::all('location_id', 'location_name_en', 'location_name_ar');

        return response()->json([
            'stores' => $stores,
            'status' => true,
            'message' => "store.delete",
        ], 200);
    }

    //search branches
    public function search_branches(Request $request)
    {
        $query = StockLocation::query();
        $keyword = $request->input('query');
        $query->whereRaw("location_name_en LIKE '%" . $keyword . "%'")
            ->orWhereRaw("location_name_ar LIKE '%" . $keyword . "%'");
        $result = $query->get();
        return response()->json([
            'data' => $result,
        ], 200);
    }

    //save or update location
    public function save_store(Request $request)
    {
        try {
            $location = StockLocation::updateOrCreate([
                'location_id' => $request->input('location_id'),
            ],
                [
                    'location_name_en' => $request->input('location_name_en'),
                    'location_name_ar' => $request->input('location_name_ar'),
                    'location_address_en' => $request->input('location_address_en'),
                    'location_address_ar' => $request->input('location_address_ar'),
                    'location_mobile' => $request->input('location_mobile'),
                    'location_email' => $request->input('location_email'),
                    'location_building_no' => $request->input('location_building_no'),
                    'location_street_name_en' => $request->input('location_street_name_en'),
                    'location_street_name_ar' => $request->input('location_street_name_ar'),
                    'location_district_en' => $request->input('location_district_en'),
                    'location_district_ar' => $request->input('location_district_ar'),
                    'location_city_en' => $request->input('location_city_en'),
                    'location_city_ar' => $request->input('location_city_ar'),
                    'location_country_en' => $request->input('location_country_en'),
                    'location_country_ar' => $request->input('location_country_ar'),
                    'location_cr_number' => $request->input('location_cr_number'),
                    'location_vat_number' => $request->input('location_vat_number'),
                ]);

            $stores = StockLocation::all('location_id', 'location_name_en', 'location_name_ar');

            return response()->json([
                'stores' => $stores,
                'error' => false,
                'message' => "store.new_store_or_update",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => true,
                'message' => "store.error_new_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //dinner table section
    //get all tables
    public function get_all_tables()
    {
        $table = DinnerTable::all('table_id', 'table_name_en', 'table_name_ar');
        return response()->json([
            'data' => $table,
        ], 200);
    }

    //get locations by id
    public function get_table_by_id(Request $request)
    {
        $table = DinnerTable::find($request->input("table_id"));
        return response()->json([
            'data' => $table,
        ], 200);
    }

    //delete locations by id
    public function delete_table_by_id(Request $request)
    {
        try {
            DinnerTable::find($request->input("table_id"))->delete();
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e,
            ], 200);
        }

        $tables = DinnerTable::all('table_id', 'table_name_en', 'table_name_ar');

        return response()->json([
            'stores' => $tables,
            'status' => true,
            'message' => "table.delete",
        ], 200);
    }

    //save or update table
    public function save_table(Request $request)
    {
        try {
            $table = DinnerTable::updateOrCreate([
                'table_id' => $request->input('table_id'),
            ],
                [
                    'table_name_en' => $request->input('table_name_en'),
                    'table_name_ar' => $request->input('table_name_ar'),
                    'status' => 1,
                ]);

            $table = DinnerTable::all('table_id', 'table_name_en', 'table_name_ar');

            return response()->json([
                'stores' => $table,
                'error' => false,
                'message' => "table.new_store_or_update",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => true,
                'message' => "table.error_new_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //store unit section
    //get all units
    public function get_all_units()
    {
        $units = StoreUnit::all('unit_id', 'unit_name_en', 'unit_name_ar');
        return response()->json([
            'data' => $units,
        ], 200);
    }



    //get unit by id
    public function get_unit_by_id(Request $request)
    {
        $units = StoreUnit::find($request->input("unit_id"));
        return response()->json([
            'data' => $units,
        ], 200);
    }

    //delete unit by id
    public function delete_unit_by_id(Request $request)
    {
        try {
            StoreUnit::find($request->input("unit_id"))->delete();
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e,
            ], 200);
        }

        $units = StoreUnit::all('unit_id', 'unit_name_en', 'unit_name_ar');

        return response()->json([
            'stores' => $units,
            'status' => true,
            'message' => "unit.delete",
        ], 200);
    }

    //save or update unit
    public function save_unit(Request $request)
    {
        try {
            $unit = StoreUnit::updateOrCreate([
                'unit_id' => $request->input('unit_id'),
            ],
                [
                    'unit_name_en' => $request->input('unit_name_en'),
                    'unit_name_ar' => $request->input('unit_name_ar'),

                ]);

            $unit = StoreUnit::all('unit_id', 'unit_name_en', 'unit_name_ar');

            return response()->json([
                'stores' => $unit,
                'error' => false,
                'message' => "unit.new_store_or_update",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => true,
                'message' => "unit.error_new_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //store payments  methods
    //get all payments
    public function get_all_payments()
    {
        $payments = PaymentOption::all('payment_id', 'payment_name_en', 'payment_name_ar', 'account_id', 'editable', 'active');
        return response()->json([
            'data' => $payments,
        ], 200);
    }

    //get all payments
    public function get_all_active_payments()
    {
        $payments = PaymentOption::where('active', 1)->get();
        return response()->json([
            'data' => $payments,
        ], 200);
    }

    public function get_payment_option_by_id(Request $request)
    {
        $option = PaymentOption::find($request->input('payment_id'));
        return response()->json([
            'data' => $option,
        ], 200);
    }

    public function change_payment_option_status_by_id(Request $request)
    {
        try {
            DB::table('payment_options')
                ->where('payment_id', $request->input('payment_id'))
                ->update(['active' => $request->input('status')]);

            $payments = PaymentOption::all('payment_id', 'payment_name_en', 'payment_name_ar', 'account_id', 'editable', 'active');

            return response()->json([
                'data' => $payments,
                'status' => true,
                'message' => "configuration.new_payment_option_or_update",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => "configuration.error_new_payment_option_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //save or update payment option
    public function save_payment_option(Request $request)
    {
        try {
            PaymentOption::updateOrCreate([
                'payment_id' => $request->input('payment_id'),
            ],
                [
                    'payment_name_en' => $request->input('payment_name_en'),
                    'payment_name_ar' => $request->input('payment_name_ar'),
                    'account_id' => $request->input('account_id'),
                ]);

            $payments = PaymentOption::all('payment_id', 'payment_name_en', 'payment_name_ar', 'account_id', 'editable', 'active');

            return response()->json([
                'stores' => $payments,
                'error' => false,
                'message' => "configuration.new_payment_option_or_update",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => true,
                'message' => "configuration.error_new_payment_option_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }
}
