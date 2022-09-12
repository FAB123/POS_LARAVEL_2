<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(EmployeeTableSeeder::class);
        $this->call(StockTableSeeder::class);
        $this->call(ConfigTableSeeder::class);
        $this->call(PermissionTableSeeder::class);
        $this->call(MenuTableSeeder::class);
        $this->call(SubMenuTableSeeder::class);
        $this->call(BasicMenuTableSeeder::class);
        $this->call(GrantsTableSeeder::class);
        $this->call(TaxSchmesTableSeeder::class);
        $this->call(DinnerTableSeeder::class);
        $this->call(StoreUnitTableSeeder::class);
        $this->call(AccountHeadTableSeeder::class);
        $this->call(OpeningBalanceTableSeeder::class);
    }
}
