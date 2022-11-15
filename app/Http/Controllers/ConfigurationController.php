<?php

namespace App\Http\Controllers;

use App\GAZT\EInvoice\EGenerator;
use App\GAZT\EInvoice\EInvoice;
use App\GAZT\Xml\InvoiceTypeCode;
use App\Models\Account\AccountHead;
use App\Models\Account\AccountOpeningBalance;
use App\Models\Configurations\Configuration;
use App\Models\Configurations\DinnerTable;
use App\Models\Configurations\StockLocation;
use App\Models\Configurations\StoreUnit;
use App\Models\Configurations\TaxScheme;
use App\Models\GaztData;
use App\Models\Item\Item;
use App\Models\Item\ItemsQuantity;
use App\Models\PaymentOption;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str as Str;

class ConfigurationController extends Controller
{
    public function getConfigDatas()
    {
        try {
            $configuration_data = Configuration::all()->map(function ($item) {
                return [$item['key'] => $item['value']];
            });

            $tax_scheme = TaxScheme::all();
            return response()->json([
                'configuration_data' => $configuration_data->flatMap(function ($item) {return $item;})->all(),
                'tax_scheme' => $tax_scheme,
                'status' => true,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    //save store config
    public function save_configuration(Request $request)
    {
        $configurations = $request->all();
        try {

            DB::beginTransaction();
            foreach ($configurations as $k => $v) {
                if ($k != 'vatList') {
                    Configuration::updateOrCreate([
                        'key' => $k,
                    ], [
                        'value' => $v,
                    ]);
                } else {
                    //insert vat scheme
                }
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'configuration.configuration_saved',
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 200);
        }

    }

    //stock location section
    //get all locations
    public function get_all_stores()
    {
        $stores = StockLocation::all('location_id', 'location_name_en', 'location_name_ar');
        return response()->json([
            'data' => $stores,
        ], 200);
    }

    //get locations by id
    public function get_store_by_id(Request $request, $location_id)
    {
        $store = StockLocation::find($location_id);
        return response()->json([
            'data' => $store,
        ], 200);
    }

    //delete locations by id
    public function delete_store_by_id(Request $request, $location_id)
    {
        try {
            $store = StockLocation::all()->count();
            if ($store > 1) {
                StockLocation::find($location_id)->delete();
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "store.delete_lessthan_one_store",
                ], 200);
            }

        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e,
            ], 200);
        }

        $stores = StockLocation::all('location_id', 'location_name_en', 'location_name_ar');

        return response()->json([
            'stores' => $stores,
            'status' => true,
            'message' => "store.delete",
        ], 200);
    }

    //search branches
    public function search_branches(Request $request, $keyword)
    {
        $query = StockLocation::query();
        $query->whereRaw("location_name_en LIKE '%" . $keyword . "%'")
            ->orWhereRaw("location_name_ar LIKE '%" . $keyword . "%'");
        $result = $query->get();
        return response()->json([
            'data' => $result,
        ], 200);
    }

    //save or update location
    public function save_store(Request $request)
    {
        try {
            $location = StockLocation::updateOrCreate([
                'location_id' => $request->input('location_id'),
            ],
                [
                    'location_name_en' => $request->input('location_name_en'),
                    'location_name_ar' => $request->input('location_name_ar'),
                    'location_address_en' => $request->input('location_address_en'),
                    'location_address_ar' => $request->input('location_address_ar'),
                    'location_mobile' => $request->input('location_mobile'),
                    'location_email' => $request->input('location_email'),
                    'location_building_no' => $request->input('location_building_no'),
                    'location_street_name_en' => $request->input('location_street_name_en'),
                    'location_street_name_ar' => $request->input('location_street_name_ar'),
                    'location_district_en' => $request->input('location_district_en'),
                    'location_district_ar' => $request->input('location_district_ar'),
                    'location_city_en' => $request->input('location_city_en'),
                    'location_city_ar' => $request->input('location_city_ar'),
                    'location_country_en' => $request->input('location_country_en'),
                    'location_country_ar' => $request->input('location_country_ar'),
                    'location_cr_number' => $request->input('location_cr_number'),
                    'location_vat_number' => $request->input('location_vat_number'),
                ]);

            if (!$request->input('location_id')) {
                //update quantity of all existing Items
                $items = Item::all()->where('stock_type', '1')->where('is_boxed', '0');
                $account_heads = AccountHead::all();

                foreach ($items as $item) {
                    // pos_items_quantities
                    ItemsQuantity::
                        updateOrInsert(
                        ['location_id' => $location['location_id'], 'item_id' => $item['item_id']],
                        ['quantity' => 0]
                    );

                    AccountOpeningBalance::updateOrInsert(
                        ['account_sub_id' => $item['item_id'], 'location_id' => $location['location_id'], 'account_id' => 211, 'year' => date('Y')],
                        ['amount' => 0, 'inserted_by' => decrypt(auth()->user()->encrypted_employee)]
                    );
                }

                foreach ($account_heads as $head) {
                    AccountOpeningBalance::updateOrInsert(
                        ['location_id' => $location['location_id'], 'account_id' => $head['account_id'], 'year' => date('Y')],
                        ['amount' => 0, 'inserted_by' => decrypt(auth()->user()->encrypted_employee)]
                    );
                }
            }
            $stores = StockLocation::all('location_id', 'location_name_en', 'location_name_ar');

            return response()->json([
                'stores' => $stores,
                'error' => false,
                'message' => "configuration.configuration_store_saved",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => true,
                'message' => "configuration.configuration_store_not_saved",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //dinner table section
    //get all tables
    public function get_all_tables()
    {
        $table = DinnerTable::all('table_id', 'table_name_en', 'table_name_ar');
        return response()->json([
            'data' => $table,
        ], 200);
    }

    //get locations by id
    public function get_table_by_id($table_id)
    {
        $table = DinnerTable::find($table_id);
        return response()->json([
            'data' => $table,
        ], 200);
    }

    //delete locations by id
    public function delete_table_by_id($table_id)
    {
        try {
            DinnerTable::find($table_id)->delete();
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e,
            ], 200);
        }

        $tables = DinnerTable::all('table_id', 'table_name_en', 'table_name_ar');

        return response()->json([
            'stores' => $tables,
            'status' => true,
            'message' => "table.delete",
        ], 200);
    }

    //save or update table
    public function save_table(Request $request)
    {
        try {
            $table = DinnerTable::updateOrCreate([
                'table_id' => $request->input('table_id'),
            ],
                [
                    'table_name_en' => $request->input('table_name_en'),
                    'table_name_ar' => $request->input('table_name_ar'),
                    'status' => 1,
                ]);

            $table = DinnerTable::all('table_id', 'table_name_en', 'table_name_ar');

            return response()->json([
                'stores' => $table,
                'error' => false,
                'message' => "table.new_store_or_update",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => true,
                'message' => "table.error_new_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //store unit section
    //get all units
    public function get_all_units()
    {
        $units = StoreUnit::all('unit_id', 'unit_name_en', 'unit_name_ar');
        return response()->json([
            'data' => $units,
        ], 200);
    }

    //get unit by id
    public function get_unit_by_id($unit_id)
    {
        $units = StoreUnit::find($unit_id);
        return response()->json([
            'data' => $units,
        ], 200);
    }

    //delete unit by id
    public function delete_unit_by_id($unit_id)
    {
        try {
            StoreUnit::find($unit_id)->delete();
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => $e,
            ], 200);
        }

        $units = StoreUnit::all('unit_id', 'unit_name_en', 'unit_name_ar');

        return response()->json([
            'stores' => $units,
            'status' => true,
            'message' => "unit.delete",
        ], 200);
    }

    //save or update unit
    public function save_unit(Request $request)
    {
        try {
            $unit = StoreUnit::updateOrCreate([
                'unit_id' => $request->input('unit_id'),
            ],
                [
                    'unit_name_en' => $request->input('unit_name_en'),
                    'unit_name_ar' => $request->input('unit_name_ar'),

                ]);

            $unit = StoreUnit::all('unit_id', 'unit_name_en', 'unit_name_ar');

            return response()->json([
                'stores' => $unit,
                'error' => false,
                'message' => "unit.new_store_or_update",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => true,
                'message' => "unit.error_new_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //store payments  methods
    //get all payments
    public function get_all_payments()
    {
        $payments = PaymentOption::all('payment_id', 'payment_name_en', 'payment_name_ar', 'account_id', 'editable', 'active');
        return response()->json([
            'data' => $payments,
        ], 200);
    }

    //get all payments
    public function get_all_active_payments()
    {
        $payments = PaymentOption::where('active', 1)->get();
        return response()->json([
            'data' => $payments,
        ], 200);
    }

    public function get_payment_option_by_id($payment_id)
    {
        $option = PaymentOption::find($payment_id);
        return response()->json([
            'data' => $option,
        ], 200);
    }

    public function change_payment_option_status_by_id(Request $request)
    {
        try {
            DB::table('payment_options')
                ->where('payment_id', $request->input('payment_id'))
                ->update(['active' => $request->input('status')]);

            $payments = PaymentOption::all('payment_id', 'payment_name_en', 'payment_name_ar', 'account_id', 'editable', 'active');

            return response()->json([
                'data' => $payments,
                'status' => true,
                'message' => "configuration.new_payment_option_or_update",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'status' => false,
                'message' => "configuration.error_new_payment_option_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    //save or update payment option
    public function save_payment_option(Request $request)
    {
        try {
            PaymentOption::updateOrCreate([
                'payment_id' => $request->input('payment_id'),
            ],
                [
                    'payment_name_en' => $request->input('payment_name_en'),
                    'payment_name_ar' => $request->input('payment_name_ar'),
                    'account_id' => $request->input('account_id'),
                ]);

            $payments = PaymentOption::all('payment_id', 'payment_name_en', 'payment_name_ar', 'account_id', 'editable', 'active');

            return response()->json([
                'stores' => $payments,
                'error' => false,
                'message' => "configuration.new_payment_option_or_update",
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => true,
                'message' => "configuration.error_new_payment_option_or_update",
                'info' => $e->getMessage(),
            ], 200);
        }
    }

    public function csid_status()
    {
        try {
            $egs_data = GaztData::latest()->first();
            return response()->json([
                'error' => false,
                'data' => $egs_data['activated'],
            ], 200);
        } catch (\Throwable$th) {
            //throw $th;
            return response()->json([
                'error' => false,
                'info' => $th->getMessage(),
            ], 200);
        }

    }

    public function generate_csid(Request $request)
    {
        try {
            $otp = $request->input('otp');
            if (!$otp) {
                return response()->json([
                    'error' => true,
                    'message' => "configuration.invalid_otp",
                ], 200);
            } else {
                $uuid = Str::uuid();
                $data = $this->get_egs_data_and_validate($uuid);

                $egs = new EGenerator($data);
                $csr_keys = $egs->generateNewKeysAndCSR();

                // //123345
                $compliance_request = $egs->issueComplianceCertificate($otp);

                if ($compliance_request['error']) {
                    return response()->json([
                        'error' => true,
                        'info' => empty($compliance_request['info']['errors']) ? $compliance_request['info']['message'] : $compliance_request['info']['errors'],
                        'message' => "configuration.error_creating_new_csid",
                    ], 200);
                } else {

                    $compliance_check = $this->compliance_check($compliance_request, $csr_keys['private_key'], $egs);
                    if (count($compliance_check['failed']) == 0) {
                        $production_certificate = $egs->issueProductionCertificate($compliance_request['request_id']);
                        if ($production_certificate['error']) {
                            return response()->json([
                                'error' => true,
                                'info' => $production_certificate['info'],
                                'message' => "configuration.error_creating_new_csid",
                            ], 200);
                        } else {
                            //get certification data
                            $invoice = new EInvoice(['uuid' => $uuid]);

                            $cert_info = $invoice->getCertificateInfo($production_certificate['production_certificate']);
                            //insert certificate to database

                            $valid = '';
                            foreach ($compliance_check['passed'] as $item) {
                                $valid .= $item['type'] . ',';
                            }

                            GaztData::create([
                                'production_certificate' => $production_certificate['production_certificate'],
                                'production_key' => $production_certificate['production_api_secret'],
                                'hash' => $cert_info['hash'],
                                'issuer' => $cert_info['issuer'],
                                'serial_number' => $cert_info['serial_number'],
                                "private_key" => $csr_keys['private_key'],
                                'public_key' => $cert_info['public_key'],
                                'signature' => $cert_info['signature'],
                                'activated' => $valid,
                            ]);

                            return response()->json([
                                'error' => false,
                                'table' => $compliance_check,
                                'message' => "configuration.new_csid_generated",
                            ], 200);
                        }

                    } else {
                        return response()->json([
                            'error' => true,
                            'info' => 'some invoice failed.',
                            'table' => $compliance_check,
                            'message' => "configuration.new_csid_generated",
                        ], 200);
                    }
                }
            }
        } catch (\Throwable$th) {
            return response()->json([
                'error' => true,
                "info" => $th->getMessage(),
                'message' => "configuration.error_creating_new_csid",
            ], 200);
        }

    }

    private function compliance_check($compliance_request, $private_key, $egs)
    {
        $compliance_result = ['passed' => [], 'failed' => []];

        $invoice = new EInvoice(['uuid' => '875948375934']);
        $cert_info = $invoice->getCertificateInfo($compliance_request['issued_certificate']);

        $company_data = Configuration::find(['egs_city',
            'egs_city_subdivision',
            'egs_postal_zone',
            'egs_building_number',
            'egs_street',
            'egs_invoice_type',
            'egs_plot_identification',
            'vat_number',
            'company_name',
            'egs_crn_number'])->pluck('value', 'key');

        $invoice_data = [
            "invoice_counter" => 1,
            'invoice_number' => 'SME00062',
            "previous_invoice_hash" => "NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==",
            "trans_time" => new \DateTime(),
            'invoice_reference_commands' => 'cancel',
            'invoice_reference' => 'SME00052',
            "client_data" => [
                'name' => 'FAb',
                'street_name' => 'Ajwad Street',
                'additional_street_name' => 'A Road',
                'building_number' => '3353',
                'plot_identification' => '3434',
                'city' => 'jeddha',
                'city_sub_division_name' => 'fgff',
                'postal_zone' => '34534',
                'country_subentity' => 'SA',
                'party_identification_type' => 'NAT',
                'party_identification_id' => '2345',

            ],
            'cart_total' => [
                'total_without_discount' => 966,
                'total_after_discount' => 964,
                'total_with_vat' => 1108.90,
                'prepaid_amount' => 0,
                'payable_amount' => 1108.90,
                'discount' => 2,
                'tax_amount' => 144.90,
            ],
            'items' => [
                [
                    'item_name' => 'ALFA',
                    'price' => 22,
                    'tax' => 144.90,
                    'rounding_amount' => 1110.90, //amount without discount  include tax
                    'qty' => 44,
                    'unit_code' => "PCE",
                    'total_include_discount' => 966, //amount witot discount withot tax
                ],
            ],
        ];

        $egs_info = [
            // 'uuid' => Str::uuid(),
            "cert_info" => [
                'hash' => $cert_info['hash'], //certificate hash - invoice xml parser
                'issuer' => $cert_info['issuer'], //csid issuer - invoice xml parser
                'serial_number' => $cert_info['serial_number'], //csid serial - invoice xml parser
                'public_key' => $cert_info['public_key'], //csid_public_key
                "private_key" => $private_key, //csr_private_key
                'signature' => $cert_info['signature'], //csid_signature  - qrcode
            ],
            "CRN_number" => $company_data['egs_crn_number'],
            "VAT_name" => $company_data['company_name'],
            "VAT_number" => $company_data['vat_number'],
            "location" => [
                "city" => $company_data['egs_city'],
                "city_subdivision" => $company_data['egs_city_subdivision'],
                "street" => $company_data['egs_street'],
                "plot_identification" => $company_data['egs_plot_identification'],
                "building" => $company_data['egs_building_number'],
                "postal_zone" => $company_data['egs_postal_zone'],
                "country_subentity" => "SA",
            ],
            "production" => env("PRODUCTION") == '0' ? false : true,
        ];

        $EInvoice = new EInvoice($egs_info);
        $types = [];
        $egs_invoice_type = substr($company_data['egs_invoice_type'], 0, 2);
        if ($egs_invoice_type == 11 || $egs_invoice_type == 01) {
            $types[] =
                [
                'name' => 'SIMPIFIED INVOICE',
                'bill_type' => InvoiceTypeCode::INVOICE,
                'invoice_type' => InvoiceTypeCode::SIMPIFIED_TAX_INVOICE,
            ];
            $types[] = [
                'name' => 'SIMPIFIED INVOICE DEBIT NOTE',
                'bill_type' => InvoiceTypeCode::DEBIT_NOTE,
                'invoice_type' => InvoiceTypeCode::SIMPIFIED_TAX_INVOICE,
            ];
            $types[] = [
                'name' => 'SIMPIFIED INVOICE CREDIT NOTE',
                'bill_type' => InvoiceTypeCode::CREDIT_NOTE,
                'invoice_type' => InvoiceTypeCode::SIMPIFIED_TAX_INVOICE,
            ];

        }
        if ($egs_invoice_type == 11 || $egs_invoice_type == 10) {
            $types[] = [
                'name' => 'STANDARD INVOICE',
                'bill_type' => InvoiceTypeCode::INVOICE,
                'invoice_type' => InvoiceTypeCode::TAX_INVOICE,
            ];
            $types[] = [
                'name' => 'STANDARD INVOICE DEBIT NOTE',
                'bill_type' => InvoiceTypeCode::DEBIT_NOTE,
                'invoice_type' => InvoiceTypeCode::TAX_INVOICE,
            ];
            $types[] = [
                'name' => 'STANDARD INVOICE CREDIT NOTE',
                'bill_type' => InvoiceTypeCode::CREDIT_NOTE,
                'invoice_type' => InvoiceTypeCode::TAX_INVOICE,
            ];

        }

        foreach ($types as $type) {
            $uuid = Str::uuid();
            $invoice_data['uuid'] = $uuid;
            $invoice_data['bill_type'] = $type['bill_type'];
            $invoice_data['invoice_type'] = $type['invoice_type'];

            $signed_invoice = $EInvoice->GenrateInvoice($invoice_data, $compliance_request['issued_certificate']);
            $validator = $egs->checkInvoiceCompliance($signed_invoice, $uuid);

            if ($validator['error']) {
                $compliance_result['failed'][] = ['type' => $type['name'], 'errorMessages' => $validator['info']['validationResults']['errorMessages']];
            } else {
                $compliance_result['passed'][] = ['type' => $type['name'], 'errorMessages' => $validator['info']['validationResults']['errorMessages'], 'warningMessages' => $validator['info']['validationResults']['warningMessages']];
            }
        }

        return $compliance_result;
    }

    private function get_egs_data_and_validate($uuid)
    {
        $store_data = Configuration::find(['company_name', 'egs_invoice_type', 'egs_branch_name', 'egs_common_name', 'egs_branch_industry', 'vat_number', 'egs_street', 'egs_building_number', 'egs_city'])->pluck('value', 'key');
        if (empty($store_data['egs_building_number'])) {
            throw new Error("Missing egs building number status.");
        } else if (empty($store_data['egs_street'])) {
            throw new Error("Missing egs Street Number.");
        } else if (empty($store_data['egs_city'])) {
            throw new Error("Missing egs City.");
        } else if (empty($store_data['vat_number'])) {
            throw new Error("Missing egs VAT NUMBER.");
        } else if (empty($store_data['egs_branch_industry'])) {
            throw new Error("Missing egs Branch Industry Type.");
        } else if (empty($store_data['egs_common_name'])) {
            throw new Error("Missing egs Common Name.");
        } else if (empty($store_data['egs_branch_name'])) {
            throw new Error("Missing egs Branch Name.");
        } else if (empty($store_data['egs_invoice_type'])) {
            throw new Error("Missing egs Invoice Type.");
        } else if (empty($store_data['company_name'])) {
            throw new Error("Missing egs Company Name.");
        }

        $is_production = env("PRODUCTION") == 1 ? "ZATCA-Code-Signing" : "TSTZATCA-Code-Signing";
        $egs_serial_number = "1-" . env('EGS_SOLUTION_NAME') . "|2-" . env('EGS_MODEL') . "|3-" . $uuid;
        $egs_branch_location = "{$store_data['egs_building_number']} {$store_data['egs_street']}, {$store_data['egs_city']}";
        $egs_vat_number = $store_data['vat_number'];
        $egs_branch_industry = $store_data['egs_branch_industry'];
        $egs_custom_id = $store_data['egs_common_name'];
        $egs_branch_name = $store_data['egs_branch_name'];
        $egs_invoice_type = $store_data['egs_invoice_type'];
        $egs_vat_name = $store_data['company_name'];

        return [
            "uuid" => $uuid,
            "private_key_pass" => '',
            "is_production" => $is_production,
            "egs_serial_number" => $egs_serial_number,
            "egs_branch_location" => $egs_branch_location,
            "egs_vat_number" => $egs_vat_number,
            "egs_branch_industry" => $egs_branch_industry,
            "egs_custom_id" => $egs_custom_id,
            "egs_branch_name" => $egs_branch_name,
            "egs_invoice_type" => $egs_invoice_type,
            "egs_vat_name" => $egs_vat_name,
        ];

    }
}
