<?php

namespace Database\Seeders;

use App\Models\Configurations\TaxScheme;
use Illuminate\Database\Seeder;

class TaxSchmesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TaxScheme::insert([
            ['tax_name' => 'VAT 1',
                'percent' => '15'],
            // ['tax_name'=>'VAT 2',
            // 'percent' => '20']
        ]);
    }
}
