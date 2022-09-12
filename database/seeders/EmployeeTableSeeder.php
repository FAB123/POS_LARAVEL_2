<?php

namespace Database\Seeders;

use App\Models\Employee\Employee;
use Illuminate\Database\Seeder;

class EmployeeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Employee::create([
            'name' => 'admin User',
            'username' => 'admin',
            'password' => bcrypt('12345678'),
            'address_line_1' => 'address 1',
            'mobile' => '55555555',
            'email' => 'admin@mail.com',
            'comments' => 'This is Demo Admin User',
            'lang' => 1,
            'status' => 1,
        ]);
    }
}
