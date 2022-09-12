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
                //'basicmenu_label' => 'edit_customers',
                'permission_id' => 'edit_customers',
            ],
            [
                'menu_id' => 'customers',
                // 'basicmenu_label' => 'delete_customers',
                'permission_id' => 'delete_customers',
            ],
            [
                'menu_id' => 'suppliers',
                //'basicmenu_label' => 'delete_suppliers',
                'permission_id' => 'delete_suppliers',
            ],
            [
                'menu_id' => 'suppliers',
                //'basicmenu_label' => 'edit_suppliers',
                'permission_id' => 'edit_suppliers',
            ],
            [
                'menu_id' => 'sales',
                //'basicmenu_label' => 'negativebilling',
                'permission_id' => 'negativebilling',
            ],
            [
                'menu_id' => 'sales',
                //'basicmenu_label' => 'edit_rate',
                'permission_id' => 'edit_rate',
            ],
            [
                'menu_id' => 'employees',
                //'basicmenu_label' => 'edit_employee',
                'permission_id' => 'edit_employee',
            ],
            [
                'menu_id' => 'employees',
                //'basicmenu_label' => 'delete_employee',
                'permission_id' => 'delete_employee',
            ],
            [
                'menu_id' => 'items',
                //'basicmenu_label' => 'edit_items',
                'permission_id' => 'edit_items',
            ],
            [
                'menu_id' => 'items',
                //'basicmenu_label' => 'delete_items',
                'permission_id' => 'delete_items',
            ],
            [
                'menu_id' => 'bundleditems',
                //'basicmenu_label' => 'edit_bundleditems',
                'permission_id' => 'edit_bundleditems',
            ],
            [
                'menu_id' => 'bundleditems',
                //'basicmenu_label' => 'delete_bundleditems',
                'permission_id' => 'delete_bundleditems',
            ],
        ]);
    }
}
