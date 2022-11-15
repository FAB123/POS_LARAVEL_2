<?php

namespace Database\Seeders;

use App\Models\Menu\BasicMenu;
use Illuminate\Database\Seeder;

class BasicMenuTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        BasicMenu::insert([
            [
                'menu_id' => 'customers',
                'permission_id' => 'edit_customers',
            ],
            [
                'menu_id' => 'customers',
                'permission_id' => 'delete_customers',
            ],
            [
                'menu_id' => 'suppliers',
                'permission_id' => 'delete_suppliers',
            ],
            [
                'menu_id' => 'suppliers',
                'permission_id' => 'edit_suppliers',
            ],
            [
                'menu_id' => 'sales',
                'permission_id' => 'negativebilling',
            ],
            [
                'menu_id' => 'sales',
                'permission_id' => 'edit_rate',
            ],
            [
                'menu_id' => 'employees',
                'permission_id' => 'edit_employee',
            ],
            [
                'menu_id' => 'employees',
                'permission_id' => 'delete_employee',
            ],
            [
                'menu_id' => 'items',
                'permission_id' => 'edit_items',
            ],
            [
                'menu_id' => 'items',
                'permission_id' => 'delete_items',
            ],
            [
                'menu_id' => 'bundleditems',
                'permission_id' => 'edit_bundleditems',
            ],
            [
                'menu_id' => 'bundleditems',
                'permission_id' => 'delete_bundleditems',
            ],
            [
                'menu_id' => 'reports',
                'permission_id' => 'detailed_sales',
            ],
            [
                'menu_id' => 'reports',
                'permission_id' => 'detailed_purchases',
            ],
        ]);
    }
}
