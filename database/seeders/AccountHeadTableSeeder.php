<?php

namespace Database\Seeders;

use App\Models\Account\AccountHead;
use Illuminate\Database\Seeder;

class AccountHeadTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //types - ‘assets’, ‘equity’, ‘liabilities’,  ‘income’, ‘expenses’
        AccountHead::insert([
            [
                'account_id' => '1',
                'account_name' => 'Fixed assets',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '111',
                'account_name' => 'Equipment',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '112',
                'account_name' => 'Building',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '113',
                'account_name' => 'Capital Expenditure',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '114',
                'account_name' => 'Land',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '2',
                'account_name' => 'Current assets',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '201',
                'account_name' => 'Cash',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '202',
                'account_name' => 'Cash in Hand',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '211',
                'account_name' => 'Stock [Inventory]',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '241',
                'account_name' => 'Accounts receivable',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '221',
                'account_name' => 'Cash in bank',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '231',
                'account_name' => 'Advance to Vendor',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '271',
                'account_name' => 'Tools and Equipments',
                'account_type' => 1,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '3',
                'account_name' => 'Equity',
                'account_type' => 2,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '301',
                'account_name' => 'Equity Capital',
                'account_type' => 2,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '4',
                'account_name' => 'Liabilities',
                'account_type' => 3,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '431',
                'account_name' => 'Accounts payable',
                'account_type' => 3,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '443',
                'account_name' => 'Advance from Customer',
                'account_type' => 3,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '449',
                'account_name' => 'VAT Payable',
                'account_type' => 3,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '5',
                'account_name' => 'Direct Income',
                'account_type' => 4,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '500',
                'account_name' => 'Sales',
                'account_type' => 4,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '501',
                'account_name' => 'Repair Service Revenue',
                'account_type' => 4,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '502',
                'account_name' => 'Service Revenue',
                'account_type' => 4,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '6',
                'account_name' => 'In Direct Income',
                'account_type' => 4,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '601',
                'account_name' => 'Room Rent',
                'account_type' => 4,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '7',
                'account_name' => 'Direct Expenses',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '704',
                'account_name' => 'Cost of goods sold',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '705',
                'account_name' => 'Wages Expense',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '8',
                'account_name' => 'Indirect Expenses',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '804',
                'account_name' => 'Salary',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '805',
                'account_name' => 'Adverstising Expense',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '806',
                'account_name' => 'Bad Debit',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '807',
                'account_name' => 'Bad Debit Expense',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '808',
                'account_name' => 'Collection Fee',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '809',
                'account_name' => 'General and Administration Expense',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '810',
                'account_name' => 'Telephone Expense',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
            [
                'account_id' => '811',
                'account_name' => 'Utitlity Expense',
                'account_type' => 5,
                'editable' => 0,
                'inserted_by' => 1,
            ],
        ]);
    }
}
