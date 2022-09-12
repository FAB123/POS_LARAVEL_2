<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'App\Http\Controllers'], function () {
    Route::group(['prefix' => 'v2'], function () {

        Route::group(['prefix' => 'login'], function () {
            Route::post("dologin", "LoginController@doLogin");
            Route::post("getstores", "LoginController@getAllStores");
        });

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

            //customer routes
            Route::group(['prefix' => 'customers'], function () {
                Route::get("get_all_customers", "CustomerController@getAll");
                Route::get('get_customer_by_id', 'CustomerController@get_customer_by_id');
                Route::post("delete_customer", "CustomerController@delete_customer");
                Route::post('save_customer', 'CustomerController@save_customer');
                Route::post('bulkinsert', 'CustomerController@bulk_insert');
                Route::get('search_customers', 'CustomerController@search_customers');
                Route::get('get_all_customers_list', 'CustomerController@get_all_customers_list');
            });

            //supplier routes
            Route::group(['prefix' => 'suppliers'], function () {
                Route::get("get_all_suppliers", "SupplierController@getAll");
                Route::get('get_supplier_by_id', 'SupplierController@get_supplier_by_id');
                Route::post("delete_suppliers", "SupplierController@delete_supplier");
                Route::post('save_supplier', 'SupplierController@save_supplier');
                Route::post('bulkinsert', 'SupplierController@bulk_insert');
                Route::get('get_all_suppliers_list', 'SupplierController@get_all_suppliers_list');
                Route::get('search_suppliers', 'SupplierController@search_suppliers');
            });

            //item routes
            Route::group(['prefix' => 'items'], function () {
                Route::get("get_all_items", "ItemController@getAll");
                Route::get('get_item_by_id', 'ItemController@get_item_by_id');
                Route::get('search_items_by_barcode', 'ItemController@search_items_by_barcode');
                Route::get('validate_barcode', 'ItemController@validatebarcode');
                Route::post("delete_items", "ItemController@delete_item");
                Route::post('save_item', 'ItemController@save_item');
                Route::post('bulkinsert', 'ItemController@bulk_insert');

                //search items with barcode / item name
                Route::get('search_items', 'ItemController@search_items');

                //search item category
                Route::get('search_category', 'ItemController@search_category');

            });

            //Boxed item routes
            Route::group(['prefix' => 'boxed_items'], function () {
                Route::get("get_all_boxed_items", "BoxedItemController@getAll");
                Route::get('get_boxed_item_by_id', 'BoxedItemController@get_item_by_id');
                Route::post('save_boxed_item', 'BoxedItemController@save_item');
            });

            //employee routes
            Route::group(['prefix' => 'employees'], function () {
                Route::get("get_all_employees", "EmployeeController@getAll");
                Route::get('get_employee_by_id', 'EmployeeController@get_employee_by_id');
                Route::get('get_new_employee', 'EmployeeController@get_new_employee');
                Route::post("delete_employees", "EmployeeController@delete_employees");
                Route::post('save_employee', 'EmployeeController@save_employee');
                Route::get('get_all_employees_list', 'EmployeeController@get_all_employees_list');
                Route::get('search_employees', 'EmployeeController@search_employees');

            });

            //sales routes
            Route::group(['prefix' => 'sales'], function () {
                Route::post('save_sale', 'SalesController@save_sale');
                // Route::post('save_cash_sale_return', 'SalesController@save_cash_sale_return');
                // Route::post('save_credit_sale', 'SalesController@save_credit_sale');
                // Route::post('save_credit_sale_return', 'SalesController@save_credit_sale_return');
                Route::post('get_sale', 'SalesController@get_sale');

            });

            //purchase routes
            Route::group(['prefix' => 'purchase'], function () {
                Route::post('save_purchase', 'PurchaseController@save_purchase');
                Route::post('get_purchase', 'PurchaseController@get_purchase');
                Route::get('get_purchase_image', 'PurchaseController@get_purchase_image');
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
            });

            //Suspended Sales routes
            Route::group(['prefix' => 'suspended_sales'], function () {
                Route::post('save_suspended', 'SuspendedSalesController@save_suspended');
                Route::get('get_suspended_details', 'SuspendedSalesController@get_suspended_details');
                Route::get('get_all_suspended', 'SuspendedSalesController@get_all_suspended');
            });

            //configuration routes
            Route::group(['prefix' => 'configurations'], function () {
                Route::get("get_all_configuration", "ConfigurationController@getConfigDatas");
                Route::post("save_configuration", "ConfigurationController@save_configuration");
                //location section
                Route::get('get_all_stores', "ConfigurationController@get_all_stores");
                Route::get('get_store_by_id', "ConfigurationController@get_store_by_id");
                Route::get('delete_store_by_id', "ConfigurationController@delete_store_by_id");
                Route::post('save_store', "ConfigurationController@save_store");
                Route::get('search_branches', "ConfigurationController@search_branches");
                //table section
                Route::get('get_all_tables', "ConfigurationController@get_all_tables");
                Route::get('get_table_by_id', "ConfigurationController@get_table_by_id");
                Route::get('delete_table_by_id', "ConfigurationController@delete_table_by_id");
                Route::post('save_table', "ConfigurationController@save_table");
                //unit section
                Route::get('get_all_units', "ConfigurationController@get_all_units");
                Route::get('get_unit_by_id', "ConfigurationController@get_unit_by_id");
                Route::get('delete_unit_by_id', "ConfigurationController@delete_unit_by_id");
                Route::post('save_unit', "ConfigurationController@save_unit");
            });

            //accounts routes
            Route::group(['prefix' => 'accounts'], function () {
                //Head Section
                Route::get("get_all_account_heads", "AccountHeadController@get_all_account_heads");
                Route::get("get_all_account_head_list", "AccountHeadController@get_all_account_head_list");
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
                Route::get('detailed_sales', 'ReportController@get_all_detailed_sales');
                Route::get('detailed_purchases', 'ReportController@get_all_detailed_purchases');
                Route::get('detailed_workorder', 'ReportController@get_all_detailed_workorder');
                Route::get('detailed_quotation', 'ReportController@get_all_detailed_quotation');
                Route::get('summary_sales', 'ReportController@get_all_summary_sales');
                Route::get('customer_sales', 'ReportController@get_specific_customer_detailed_sales');
                Route::get('employee_sales', 'ReportController@get_specific_employee_detailed_sales');
                Route::get('category_sales', 'ReportController@get_specific_category_summary_sales');
                Route::get('item_sales', 'ReportController@get_item_summary_sales');

                Route::get('summary_purchase', 'ReportController@get_all_summary_purchases');
                Route::get('supplier_purchase', 'ReportController@get_specific_supplier_detailed_purchase');
                Route::get('employee_purchase', 'ReportController@get_specific_employee_detailed_purchase');
                Route::get('category_purchase', 'ReportController@get_category_summary_purchase');
                Route::get('item_purchase', 'ReportController@get_item_summary_purchase');

                Route::get('summary_workorder', 'ReportController@get_all_summary_workorder');
                Route::get('summary_qutatation', 'ReportController@get_all_summary_qutatation');

                Route::get('inventory_summary', 'ReportController@get_inventory_summary');
                Route::get('low_inventory', 'ReportController@get_low_inventory');

                Route::get('sales_tax', 'ReportController@get_sales_tax');
                Route::get('purchase_tax', 'ReportController@get_purchase_tax');
                Route::get('generate_tax_reports', 'ReportController@get_generate_tax_reports');

                Route::get('journal_report', 'ReportController@get_general_journal');
                Route::get('ledger_accounts_balances', 'ReportController@get_ledger_accounts_balances');
                Route::get('ledger_details', 'ReportController@get_ledger_details');

                Route::get('trail_balance', 'ReportController@get_trail_balance');

            });
        });
    });

});
