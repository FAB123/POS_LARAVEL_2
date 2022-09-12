<?php

namespace Database\Seeders;

use App\Models\Configurations\StockLocation;
use Illuminate\Database\Seeder;

class StockTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        StockLocation::create([
            'location_name_en' => 'Store 1',
            'location_name_ar' => 'المتجر 1',
            'location_address_en' => 'Store Location',
            'location_address_ar' => 'موقع المتجر',
            'location_mobile' => '55555555',
            'location_email' => 'admin@mail.com',
            'location_building_no' => '2341',
            'location_street_name_en' => 'Street EN',
            'location_street_name_ar' => 'Street AR',
            'location_district_en' => 'District EN',
            'location_district_ar' => 'District AR',
            'location_city_en' => 'City EN',
            'location_city_ar' => 'City AR',
            'location_country_en' => 'Country EN',
            'location_country_ar' => 'Country AR',
            'location_cr_number' => '0000000',
            'location_vat_number' => '0000000',
        ]);
    }
}
