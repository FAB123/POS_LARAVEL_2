<?php

namespace Database\Seeders;

use App\Models\Menu\Menu;
use Illuminate\Database\Seeder;

class MenuTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Menu::insert([
            [
                'menu_id' => 'dashboard',
                'order' => 10,
                'editorder' => 10,
                //'menu_label' => 'dashboard',
                'menu_icon' => 'BarChartOutlinedIcon',
                'permission_id' => 'dashboard',
            ],
            [
                'menu_id' => 'customers',
                'order' => 20,
                'editorder' => 50,
                //'menu_label' => 'customers',
                'menu_icon' => 'AccountCircleOutlinedIcon',
                'permission_id' => 'customers',
            ],
            [
                'menu_id' => 'suppliers',
                'order' => 30,
                'editorder' => 60,
                //'menu_label' => 'suppliers',
                'menu_icon' => 'PeopleAltOutlinedIcon',
                'permission_id' => 'suppliers',
            ],
            [
                'menu_id' => 'sales',
                'order' => 40,
                'editorder' => 110,
                //'menu_label' => 'sales',
                'menu_icon' => 'ShoppingCartOutlinedIcon',
                'permission_id' => 'sales',
            ],
            [
                'menu_id' => 'purchase',
                'order' => 50,
                'editorder' => 100,
                //'menu_label' => 'purchase',
                'menu_icon' => 'LocalShippingOutlinedIcon',
                'permission_id' => 'purchase',
            ],
            [
                'menu_id' => 'employees',
                'order' => 60,
                'editorder' => 70,
                //'menu_label' => 'employee',
                'menu_icon' => 'SecurityOutlinedIcon',
                'permission_id' => 'employee',
            ],
            [
                'menu_id' => 'items',
                'order' => 70,
                'editorder' => 90,
                //'menu_label' => 'items',
                'menu_icon' => 'DnsOutlinedIcon',
                'permission_id' => 'items',
            ],
            [
                'menu_id' => 'bundleditems',
                'order' => 80,
                'editorder' => 80,
                //'menu_label' => 'boxeditems',
                'menu_icon' => 'DashboardOutlinedIcon',
                'permission_id' => 'bundleditems',
            ],
            [
                'menu_id' => 'accounts',
                'order' => 90,
                'editorder' => 40,
                //'menu_label' => 'account',
                'menu_icon' => 'AccountBalanceOutlinedIcon',
                'permission_id' => 'accounts',
            ],
            [
                'menu_id' => 'reports',
                'order' => 100,
                'editorder' => 120,
                //'menu_label' => 'reports',
                'menu_icon' => 'ArticleOutlinedIcon',
                'permission_id' => 'reports',
            ],
            [
                'menu_id' => 'messages',
                'order' => 110,
                'editorder' => 30,
                //'menu_label' => 'messages',
                'menu_icon' => 'ForwardToInboxOutlinedIcon',
                'permission_id' => 'messages',
            ],
            [
                'menu_id' => 'configurations',
                'order' => 120,
                'editorder' => 20,
                //'menu_label' => 'configuration',
                'menu_icon' => 'SettingsOutlinedIcon',
                'permission_id' => 'configurations',
            ],
        ]);
    }
}
