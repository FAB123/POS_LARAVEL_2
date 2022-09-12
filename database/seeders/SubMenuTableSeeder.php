<?php

namespace Database\Seeders;

use App\Models\Menu\SubMenu;
use Illuminate\Database\Seeder;

class SubMenuTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SubMenu::insert([
            [
                'menu_id' => 'items',
                //'submenu_label' => 'add_item',
                'permission_id' => 'add_items',
            ],
            [
                'menu_id' => 'items',
                //'submenu_label' => 'view_items',
                'permission_id' => 'view_items',
            ],
            [
                'menu_id' => 'items',
                //'submenu_label' => 'opening_stock',
                'permission_id' => 'opening_stock',
            ],
            [
                'menu_id' => 'employees',
                //'submenu_label' => 'add_employee',
                'permission_id' => 'add_employee',
            ],
            [
                'menu_id' => 'employees',
                //'submenu_label' => 'view_employee',
                'permission_id' => 'view_employee',
            ],
            [
                'menu_id' => 'customers',
                //'submenu_label' => 'new_customer',
                'permission_id' => 'add_customers',
            ],
            [
                'menu_id' => 'customers',
                //'submenu_label' => 'view_customers',
                'permission_id' => 'view_customers',
            ],
            [
                'menu_id' => 'bundleditems',
                //'submenu_label' => 'new_boxeditem',
                'permission_id' => 'add_bundleditems',
            ],
            [
                'menu_id' => 'bundleditems',
                //'submenu_label' => 'view_boxeditems',
                'permission_id' => 'view_bundleditems',
            ],

            [
                'menu_id' => 'suppliers',
                //'submenu_label' => 'new_supplier',
                'permission_id' => 'add_suppliers',
            ],
            [
                'menu_id' => 'suppliers',
                //'submenu_label' => 'view_suppliers',
                'permission_id' => 'view_suppliers',
            ],
            [
                'menu_id' => 'purchase',
                //'submenu_label' => 'new_cash_purchase',
                'permission_id' => 'new_cash_purchase',
            ],
            [
                'menu_id' => 'purchase',
                //'submenu_label' => 'cash_purchase_return',
                'permission_id' => 'cash_purchase_return',
            ],
            [
                'menu_id' => 'purchase',
                //'submenu_label' => 'new_credit_purchase',
                'permission_id' => 'new_credit_purchase',
            ],
            [
                'menu_id' => 'purchase',
                //'submenu_label' => 'credit_purchase_return',
                'permission_id' => 'credit_purchase_return',
            ],
            [
                'menu_id' => 'purchase',
                //'submenu_label' => 'requisition',
                'permission_id' => 'requisition',
            ],
            [
                'menu_id' => 'sales',
                //'submenu_label' => 'cash_sales',
                'permission_id' => 'cash_sales',
            ],
            [
                'menu_id' => 'sales',
                //'submenu_label' => 'credit_sales',
                'permission_id' => 'credit_sales',
            ],
            [
                'menu_id' => 'sales',
                //'submenu_label' => 'cash_sales_return',
                'permission_id' => 'cash_sales_return',
            ],
            [
                'menu_id' => 'sales',
                //'submenu_label' => 'credit_sales_return',
                'permission_id' => 'credit_sales_return',
            ],
            [
                'menu_id' => 'sales',
                //'submenu_label' => 'quotation',
                'permission_id' => 'quotation',
            ],
            [
                'menu_id' => 'sales',
                'permission_id' => 'workorder',
            ],
        ]);
    }
}
