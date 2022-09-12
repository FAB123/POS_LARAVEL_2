<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::insert([
            ['permission_id' => 'dashboard',
                'under' => 'dashboard'],
            ['permission_id' => 'totalsale',
                'under' => 'dashboard'],
            ['permission_id' => 'totalpurchase',
                'under' => 'dashboard'],
            ['permission_id' => 'customers',
                'under' => 'customers'],
            ['permission_id' => 'edit_customers',
                'under' => 'customers'],
            ['permission_id' => 'delete_customers',
                'under' => 'customers'],
            ['permission_id' => 'add_customers',
                'under' => 'add_customers'],
            ['permission_id' => 'view_customers',
                'under' => 'view_customers'],
            ['permission_id' => 'suppliers',
                'under' => 'suppliers'],
            ['permission_id' => 'edit_suppliers',
                'under' => 'suppliers'],
            ['permission_id' => 'delete_suppliers',
                'under' => 'suppliers'],
            ['permission_id' => 'add_suppliers',
                'under' => 'add_suppliers'],
            ['permission_id' => 'view_suppliers',
                'under' => 'view_suppliers'],
            ['permission_id' => 'sales',
                'under' => 'sales'],
            ['permission_id' => 'negativebilling',
                'under' => 'sales'],
            ['permission_id' => 'edit_rate',
                'under' => 'sales'],
            ['permission_id' => 'cash_sales',
                'under' => 'cash_sales'],
            ['permission_id' => 'credit_sales',
                'under' => 'credit_sales'],
            ['permission_id' => 'cash_sales_return',
                'under' => 'cash_sales_return'],
            ['permission_id' => 'credit_sales_return',
                'under' => 'credit_sales_return'],
            ['permission_id' => 'quotation',
                'under' => 'quotation'],
            ['permission_id' => 'workorder',
                'under' => 'workorder'],
            ['permission_id' => 'purchase',
                'under' => 'purchase'],
            ['permission_id' => 'requisition',
                'under' => 'requisition'],
            ['permission_id' => 'new_cash_purchase',
                'under' => 'new_cash_purchase'],
            ['permission_id' => 'cash_purchase_return',
                'under' => 'cash_purchase_return'],
            ['permission_id' => 'new_credit_purchase',
                'under' => 'new_credit_purchase'],
            ['permission_id' => 'credit_purchase_return',
                'under' => 'credit_purchase_return'],
            ['permission_id' => 'employee',
                'under' => 'employee'],
            ['permission_id' => 'edit_employee',
                'under' => 'employee'],
            ['permission_id' => 'delete_employee',
                'under' => 'employee'],
            ['permission_id' => 'add_employee',
                'under' => 'add_employee'],
            ['permission_id' => 'view_employee',
                'under' => 'view_employee'],
            ['permission_id' => 'items',
                'under' => 'items'],
            ['permission_id' => 'edit_items',
                'under' => 'items'],
            ['permission_id' => 'delete_items',
                'under' => 'items'],
            ['permission_id' => 'add_items',
                'under' => 'add_items'],
            ['permission_id' => 'view_items',
                'under' => 'view_items'],
            ['permission_id' => 'opening_stock',
                'under' => 'opening_stock'],
            ['permission_id' => 'bundleditems',
                'under' => 'bundleditems'],
            ['permission_id' => 'edit_bundleditems',
                'under' => 'bundleditems'],
            ['permission_id' => 'delete_bundleditems',
                'under' => 'bundleditems'],
            ['permission_id' => 'add_bundleditems',
                'under' => 'add_bundleditems'],
            ['permission_id' => 'view_bundleditems',
                'under' => 'view_bundleditems'],
            ['permission_id' => 'accounts',
                'under' => 'accounts'],
            ['permission_id' => 'reports',
                'under' => 'reports'],
            ['permission_id' => 'messages',
                'under' => 'messages'],
            ['permission_id' => 'configurations',
                'under' => 'configurations'],
        ]);

    }
}
