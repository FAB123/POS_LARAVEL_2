<?php

namespace Database\Seeders;

use App\Models\Configurations\Configuration;
use Illuminate\Database\Seeder;

class ConfigTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Configuration::insert([
            ['key' => 'company_name',
                'value' => 'Company Name'],
            ['key' => 'company_name_ar',
                'value' => 'اسم الشركة'],
            ['key' => 'company_address',
                'value' => 'Company Address'],
            ['key' => 'company_address_ar',
                'value' => 'عنوان الشركة'],
            ['key' => 'vat_number',
                'value' => '0000000000000'],
            ['key' => 'company_telephone',
                'value' => '0120000000'],
            ['key' => 'company_email',
                'value' => 'comapany@email.com'],
            ['key' => 'return_policy',
                'value' => 'Company Name return policy'],
            ['key' => 'return_policy_ar',
                'value' => 'سياسة إرجاع اسم الشركة'],
            ['key' => 'currency_symbol',
                'value' => 'SAR'],
            ['key' => 'application_lang',
                'value' => 'en'],
            ['key' => 'company_fiscal_year_start',
                'value' => '1'],
            ['key' => 'include_tax',
                'value' => '1'],
            ['key' => 'calc_average_cost',
                'value' => '1'],
            ['key' => 'sms_api_username',
                'value' => 'smsuser'],
            ['key' => 'sms_api_password',
                'value' => 'password'],
            ['key' => 'sms_api_sender_id',
                'value' => 'SENDERID'],
            ['key' => 'email_smtp_server',
                'value' => 'server.smtp.com'],
            ['key' => 'email_smtp_port',
                'value' => '0000'],
            ['key' => 'email_smtp_encryption_type',
                'value' => '0'],
            ['key' => 'email_api_username',
                'value' => 'smsuser'],
            ['key' => 'email_api_password',
                'value' => 'password'],
        ]);
    }
}
