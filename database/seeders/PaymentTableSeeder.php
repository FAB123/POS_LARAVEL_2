<?php

namespace Database\Seeders;

use App\Models\PaymentOption;
use Illuminate\Database\Seeder;

class PaymentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PaymentOption::insert([
            [
                'payment_name_en' => 'CASH',
                'payment_name_ar' => 'نقد',
                'account_id' => 201,
                'editable' => 1,
            ],
            [
                'payment_name_en' => 'CREDIT CARD',
                'payment_name_ar' => 'بطاقة الائتمان',
                'account_id' => 202,
                'editable' => 1,
            ],
            [
                'payment_name_en' => 'DEBIT CARD',
                'payment_name_ar' => 'بطاقة ائتمان ',
                'account_id' => 203,
                'editable' => 1,
            ],
            [
                'payment_name_en' => 'BANK',
                'payment_name_ar' => ' بنك ',
                'account_id' => 204,
                'editable' => 1,
            ],
        ]);
    }
}
