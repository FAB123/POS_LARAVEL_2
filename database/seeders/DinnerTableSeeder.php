<?php

namespace Database\Seeders;

use App\Models\Configurations\DinnerTable;
use Illuminate\Database\Seeder;

class DinnerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DinnerTable::insert([
            [
                'table_name_en' => 'Delivery',
                'table_name_ar' => 'توصيل',
                'status' => 1,
            ],
            [
                'table_name_en' => 'Take Away',
                'table_name_ar' => 'الاستلام من المتجر',
                'status' => 1,
            ],
        ]);
    }
}
