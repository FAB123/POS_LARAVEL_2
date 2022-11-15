<?php

use App\Models\Sales\Sale;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'App\Http\Controllers'], function () {
    Route::group(['prefix' => 'v2'], function () {
        // Route::get("sendmail", 'HomeController@send_mail');
        // Route::post("send_text_email", 'MessageController@send_text_email');
        Route::get("/report", "ZatkaController@test");

        Route::group(['prefix' => 'login'], function () {
            Route::post("dologin", "LoginController@doLogin");
            Route::post("getstores", "LoginController@getAllStores");
        });

        Route::get('/fixstorage', function () {
            Artisan::call('storage:link');
        });

        Route::get('/refresh_db', function () {
            Artisan::call('migrate:fresh');
            echo 'done';
        });

        Route::get('/seed_db', function () {
            // Artisan::call('db:seed');
            echo date("l jS \of F Y h:i:s A");

            return response()->json([
                'data' => Sale::all(),
            ], 200);
            echo 'done';
        });

        // Route::get('/openssl', function () {
        //     $uuid = Str::uuid();
        //     $store_data = Configuration::find(['company_name', 'vat_number', 'egs_city'])->pluck('value', 'key');
        //     var_dump($store_data['company_name']);
        //     // // Sample EGSUnit
        //     // $egsunit = [
        //     //     "private_key_pass" => '',
        //     //     "uuid" => $uuid,
        //     //     "custom_id" => "EGS1-886431145",
        //     //     "egs_model" => env("EGS_MODEL"),
        //     //     "CRN_number" => "454634645645654",
        //     //     "VAT_name" => $store_data['company_name'],
        //     //     "VAT_number" => $store_data['vat_number'],
        //     //     "location" => [
        //     //         "city" => "Khobar",
        //     //         "city_subdivision" => "West",
        //     //         "street" => "King Fahahd st",
        //     //         "plot_identification" => "0000",
        //     //         "building" => "0000",
        //     //         "postal_zone" => "31952",
        //     //         "country_subentity" => "SA",
        //     //     ],
        //     //     "branch_name" => "My Branch Name",
        //     //     "branch_industry" => "Food",
        //     //     "solution_name" => "solution_name",
        //     //     "production" => false,
        //     // ];

        //     // $invoice_data = [
        //     //     "invoice_counter" => 2,
        //     //     "previous_invoice_hash" => "NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==",
        //     //     "time" => date("Y-m-d\TH:i:sp"),
        //     // ];

        //     // $egs = new EGenerator($egsunit);
        //     // $egs->generateNewKeysAndCSR();
        //     // //api call for complince id
        //     // $compliance_request_id = $egs->issueComplianceCertificate("123345");
        //     // if ($compliance_request_id == "Client error" || $compliance_request_id == "Server error") {
        //     //     return false;
        //     // }
        //     // $signed_invoice = $egs->signInvoice($invoice_data);

        //     // // Check invoice compliance

        //     // var_dump($egs->checkInvoiceCompliance($signed_invoice));

        //     // $production_request_id = $egs->issueProductionCertificate($compliance_request_id);

        //     // $production_request_id = $egs->reportInvoice($signed_invoice['invoice'], $signed_invoice['invoice_hash']);

        //     // return ($production_request_id);

        // });

        Route::middleware('auth:sanctum')->group(function () {
            Route::get("get_required_info", "ConfigurationController@getConfigDatas");

            //login routes
            Route::group(['prefix' => 'login'], function () {
                Route::get("logout", "LoginController@logout");
                Route::post('home', 'LoginController@home');
            });

            //dashboard routes
            Route::group(['prefix' => 'dashboard'], function () {
                Route::get("get_basic_info", "HomeController@get_basic_info");
                Route::post('get_monthly_graph', 'HomeController@home');
            });

            //message routes
            Route::group(['prefix' => 'message'], function () {
                Route::post('send_text_email', 'MessageController@send_text_email');
            });

            //customer routes
            Route::group(['prefix' => 'customers'], function () {
                Route::get("get_all_customers/{page}/{size}/{keyword}/{sortitem}/{sortdir}", "CustomerController@get_all_customers");
                Route::get('get_customer_by_id/{customer_id}', 'CustomerController@get_customer_by_id');
                Route::post("delete_customer", "CustomerController@delete_customer");
                Route::post('save_customer', 'CustomerController@save_customer');
                Route::post('bulkinsert', 'CustomerController@bulk_insert');
                Route::get('search_customers/{keyword}', 'CustomerController@search_customers');
                Route::get('get_all_customers_list', 'CustomerController@get_all_customers_list');
            });

            //supplier routes
            Route::group(['prefix' => 'suppliers'], function () {
                Route::get("get_all_suppliers/{page}/{size}/{keyword}/{sortitem}/{sortdir}", "SupplierController@getAll");
                Route::get('get_supplier_by_id/{supplier_id}', 'SupplierController@get_supplier_by_id');
                Route::post("delete_suppliers", "SupplierController@delete_supplier");
                Route::post('save_supplier', 'SupplierController@save_supplier');
                Route::post('bulkinsert', 'SupplierController@bulk_insert');
                Route::get('get_all_suppliers_list', 'SupplierController@get_all_suppliers_list');
                Route::get('search_suppliers/{keyword}', 'SupplierController@search_suppliers');
            });

            //item routes
            Route::group(['prefix' => 'items'], function () {
                Route::get("get_all_items/{page}/{size}/{keyword}/{sortitem}/{sortdir}", "ItemController@getAll");
                Route::get('get_item_by_id/{item_id}', 'ItemController@get_item_by_id');
                Route::get('search_items_by_barcode/{keyword}', 'ItemController@search_items_by_barcode');
                Route::get('validate_barcode/{barcode}', 'ItemController@validatebarcode');
                Route::get('get_inventory_details/{item_id}', 'ItemController@get_inventory_details');
                Route::post("delete_items", "ItemController@delete_item");
                Route::post('save_item', 'ItemController@save_item');
                Route::post('bulkinsert', 'ItemController@bulk_insert');
                Route::post('generate_barcode', 'ItemController@generate_barcode');

                //search items with barcode / item name
                Route::get('search_items/{type}/{keyword}', 'ItemController@search_items');

                //search item category
                Route::get('search_category/{keyword}', 'ItemController@search_category');

                //get items for ob

                Route::get('search_items_for_ob', 'ItemController@search_items_for_opening_balance');
                Route::post('save_items_ob', 'ItemController@save_items_opening_balance');
            });

            //Boxed item routes
            Route::group(['prefix' => 'boxed_items'], function () {
                Route::get("get_all_boxed_items/{page}/{size}/{keyword}/{sortitem}/{sortdir}", "BoxedItemController@getAll");
                Route::get('get_boxed_item_by_id', 'BoxedItemController@get_item_by_id');
                Route::post('save_boxed_item', 'BoxedItemController@save_item');
            });

            //employee routes
            Route::group(['prefix' => 'employees'], function () {
                Route::get("get_all_employees/{page}/{size}/{keyword}/{sortitem}/{sortdir}", "EmployeeController@getAll");
                Route::get('get_employee_by_id/{employee_id}', 'EmployeeController@get_employee_by_id');
                Route::get('get_default_permissions', 'EmployeeController@get_default_permissions');
                Route::post("delete_employees", "EmployeeController@delete_employees");
                Route::post('save_employee', 'EmployeeController@save_employee');
                Route::get('get_all_employees_list', 'EmployeeController@get_all_employees_list');
                Route::get('search_employees/{keyword}', 'EmployeeController@search_employees');

            });

            //sales routes
            Route::group(['prefix' => 'sales'], function () {
                Route::post('save_sale', 'SalesController@save_sale');
                Route::post('get_sale', 'SalesController@get_sale');
                Route::get('get_sales_history/{item_id}/{type}/{customer}', 'SalesController@get_sales_history');
                Route::get('get_daily_sales/{page}/{size}/{sortitem}/{sortdir}', 'SalesController@get_daily_sales');
                Route::get('get_sale_by_id/{sale_id}', 'SalesController@get_sale_by_id');
            });

            //purchase routes
            Route::group(['prefix' => 'purchase'], function () {
                Route::post('save_purchase', 'PurchaseController@save_purchase');
                Route::post('get_purchase', 'PurchaseController@get_purchase');
                Route::get('get_purchase_image/{purchase_id}', 'PurchaseController@get_purchase_image');
                Route::get('get_purchase_by_id/{purchase_id}', 'PurchaseController@get_purchase_by_id');
            });

            //workorder rout
            Route::group(['prefix' => 'workorder'], function () {
                Route::post('save_work_order', 'WorkorderController@save_work_order');
                Route::post('get_workorder', 'WorkorderController@get_workorder');
            });

            //qutation route
            Route::group(['prefix' => 'quatation'], function () {
                Route::post('save_quatation', 'QuatationController@save_quatation');
                Route::post('get_quatation', 'QuatationController@get_quatation');
                Route::get('get_quatation_details/{quatation_id}', 'QuatationController@get_quatation_details');
            });

            //Suspended Sales routes
            Route::group(['prefix' => 'suspended_sales'], function () {
                Route::post('save_suspended', 'SuspendedSalesController@save_suspended');
                Route::get('get_suspended_details/{type}/{suspended_id}', 'SuspendedSalesController@get_suspended_details');
                Route::get('get_all_suspended/{page}/{size}/{keyword}/{sortitem}/{sortdir}', 'SuspendedSalesController@get_all_suspended');
            });

            //configuration routes
            Route::group(['prefix' => 'configurations'], function () {
                Route::get("get_all_configuration", "ConfigurationController@getConfigDatas");
                Route::get("csid_status", "ConfigurationController@csid_status");
                Route::post("save_configuration", "ConfigurationController@save_configuration");
                //location section
                Route::get('get_all_stores', "ConfigurationController@get_all_stores");
                Route::get('get_store_by_id/{location_id}', "ConfigurationController@get_store_by_id");
                Route::get('delete_store_by_id/{location_id}', "ConfigurationController@delete_store_by_id");
                Route::post('save_store', "ConfigurationController@save_store");
                Route::get('search_branches/{keyword}', "ConfigurationController@search_branches");
                //table section
                Route::get('get_all_tables', "ConfigurationController@get_all_tables");
                Route::get('get_table_by_id/{table_id}', "ConfigurationController@get_table_by_id");
                Route::get('delete_table_by_id/{table_id}', "ConfigurationController@delete_table_by_id");
                Route::post('save_table', "ConfigurationController@save_table");
                //unit section
                Route::get('get_all_units', "ConfigurationController@get_all_units");
                Route::get('get_unit_by_id/{unit_id}', "ConfigurationController@get_unit_by_id");
                Route::get('delete_unit_by_id/{unit_id}', "ConfigurationController@delete_unit_by_id");
                Route::post('save_unit', "ConfigurationController@save_unit");

                //payments
                Route::get('get_all_payments', "ConfigurationController@get_all_payments");
                Route::get('get_all_active_payments', "ConfigurationController@get_all_active_payments");

                Route::get('get_payment_option_by_id/{payment_id}', "ConfigurationController@get_payment_option_by_id");
                Route::post('change_payment_option_status_by_id', "ConfigurationController@change_payment_option_status_by_id");
                Route::post('save_payment_option', "ConfigurationController@save_payment_option");
                Route::post('generate_csid', "ConfigurationController@generate_csid");
            });

            //accounts routes
            Route::group(['prefix' => 'accounts'], function () {
                //Head Section
                Route::get("get_all_account_heads", "AccountHeadController@get_all_account_heads");
                Route::get("get_all_account_head_list", "AccountHeadController@get_all_account_head_list");
                Route::get("get_all_account_payment_head_list", "AccountHeadController@get_all_account_payment_head_list");
                Route::get("delete_account_head", "AccountHeadController@delete_account_head");
                Route::post("update_account_heads", "AccountHeadController@update_account_heads");
                Route::get("validate_account_head", "AccountHeadController@validate_account_head");
                Route::post("create_new_account_head", "AccountHeadController@create_new_account_head");
                Route::get("get_account_heads_ob", "AccountHeadController@get_account_heads_openning_balance");
                Route::post("update_account_head_ob", "AccountHeadController@update_account_head_ob");

                //transaction Section
                Route::post("save_voucher_data", "AccountTransactionController@save_voucher_data");
            });

            //report routes
            Route::group(['prefix' => 'reports'], function () {
                Route::get('detailed_sales/{from}/{to}/{option1}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_all_detailed_sales');
                Route::get('detailed_purchases/{from}/{to}/{option1}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_all_detailed_purchases');
                Route::get('detailed_workorder/{from}/{to}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_all_detailed_workorder');
                Route::get('detailed_quotation/{from}/{to}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_all_detailed_quotation');
                Route::get('summary_sales/{from}/{to}/{option1}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_all_summary_sales');
                Route::get('customer_sales/{from}/{to}/{option1}/{option2}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_specific_customer_detailed_sales');
                Route::get('employee_sales/{from}/{to}/{option1}/{option2}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_specific_employee_detailed_sales');
                Route::get('category_sales/{from}/{to}/{option1}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_specific_category_summary_sales');
                Route::get('item_sales/{from}/{to}/{option1}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_item_summary_sales');

                Route::get('summary_purchase/{from}/{to}/{option1}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_all_summary_purchases');
                Route::get('supplier_purchase/{from}/{to}/{option1}/{option2}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_specific_supplier_detailed_purchase');
                Route::get('employee_purchase/{from}/{to}/{option1}/{option2}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_specific_employee_detailed_purchase');
                Route::get('category_purchase/{from}/{to}/{option1}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_category_summary_purchase');
                Route::get('item_purchase/{from}/{to}/{option1}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_item_summary_purchase');

                Route::get('summary_workorder/{from}/{to}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_all_summary_workorder');
                Route::get('summary_qutatation/{from}/{to}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_all_summary_qutatation');

                Route::get('inventory_summary/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_inventory_summary');
                Route::get('low_inventory/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_low_inventory');

                Route::get('sales_tax/{from}/{to}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_sales_tax');
                Route::get('purchase_tax/{from}/{to}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_purchase_tax');
                Route::get('generate_tax_reports/{from}/{to}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_generate_tax_reports');

                Route::get('journal_report/{from}/{to}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_general_journal');
                Route::get('ledger_accounts_balances/{from}/{to}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_ledger_accounts_balances');
                Route::get('ledger_details/{from}/{to}/{option1}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_ledger_details');
                Route::get('customer_ledger_details/{from}/{to}/{option1}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_customer_ledger_details');
                Route::get('supplier_ledger_details/{from}/{to}/{option1}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_supplier_ledger_details');
                Route::get('trail_balance/{from}/{to}/{location}/{page}/{size}/{sortitem}/{sortdir}', 'ReportController@get_trail_balance');
            });
        });
    });

});
