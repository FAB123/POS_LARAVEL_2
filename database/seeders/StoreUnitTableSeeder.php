<?php

namespace Database\Seeders;

use App\Models\Configurations\StoreUnit;
use Illuminate\Database\Seeder;

class StoreUnitTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        StoreUnit::insert([
            [
                'unit_name_en'=>'PCS',
                'unit_name_ar' => 'قطعة',
            ],
            [
                'unit_name_en'=>'KG',
                'unit_name_ar' => 'كيلوغرام',
            ],
            [
                'unit_name_en'=>'LTR',
                'unit_name_ar' => 'لتر'
            ],
            [
                'unit_name_en'=>'BOX',
                'unit_name_ar' => 'صندوق'
            ]
        ]);
    }
}
