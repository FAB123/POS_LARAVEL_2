<?php

namespace App\Http\Controllers;

use App\GAZT\EInvoice\EGenerator;
use App\GAZT\EInvoice\EInvoice;
use App\GAZT\Xml\InvoiceTypeCode;
use App\Models\Configurations\Configuration;
use App\Models\GaztData;
use DOMDocument;
use Error;
use Illuminate\Http\Request as Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ZatkaController extends Controller
{
    public function test(Request $request)
    {
        try {
            $egs_data = $this->get_egs_info();
            $invoice_data = $this->get_invoice_data();

            $EInvoice = new EInvoice($egs_data['egs_info']);
            $signed_invoice = $EInvoice->GenrateInvoice($invoice_data, $egs_data['certificate']);

            $egs = new EGenerator($egs_data['egs_info']);

            $dom = new DOMDocument();
            $dom->loadXML($signed_invoice['invoice']);
            $dom->save('aa.xml');

            Storage::put('public/aa.xml', $signed_invoice['invoice']);

            return $egs->report_invoice($signed_invoice['invoice'], $signed_invoice['invoice_hash'], $invoice_data['uuid']);
            // return $egs->clearance_invoice($signed_invoice['invoice'], $signed_invoice['invoice_hash'], $uuid);
        } catch (\Throwable$th) {
            return response()->json([
                'error' => true,
                "message" => $th->getMessage(),
            ], 200);
        }
    }

    private function get_egs_info()
    {
        $egs_data = GaztData::latest()->first();
        $company_data = Configuration::find(['egs_city',
            'egs_city_subdivision',
            'egs_postal_zone',
            'egs_building_number',
            'egs_street',
            'egs_plot_identification',
            'vat_number',
            'company_name',
            'egs_crn_number'])->pluck('value', 'key');
        if (empty($egs_data['production_certificate']) ||
            empty($egs_data['production_key']) ||
            empty($egs_data['hash']) ||
            empty($egs_data['issuer']) ||
            empty($egs_data['serial_number']) ||
            empty($egs_data['public_key']) ||
            empty($egs_data['private_key']) ||
            empty($egs_data['signature']) ||
            empty($company_data['egs_crn_number']) ||
            empty($company_data['company_name']) ||
            empty($company_data['vat_number']) ||
            empty($company_data['egs_city']) ||
            empty($company_data['egs_city_subdivision']) ||
            empty($company_data['egs_street']) ||
            empty($company_data['egs_plot_identification']) ||
            empty($company_data['egs_building_number']) ||
            empty($company_data['egs_postal_zone'])
        ) {
            throw new Error("missing required information, please confirm all required data is saved.");
        }
        $egs_info = [
            "cert_info" => [
                'production_certificate' => $egs_data['production_certificate'], //autheticate api
                'production_key' => $egs_data['production_key'], //autheticate api
                'hash' => $egs_data['hash'], //certificate hash invoice xml parser
                'issuer' => $egs_data['issuer'], //invoice xml parser
                'serial_number' => $egs_data['serial_number'], //invoice xml parser
                'public_key' => $egs_data['public_key'],
                "private_key" => $egs_data['private_key'],
                'signature' => $egs_data['signature'], //qrcode
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
        return ['egs_info' => $egs_info, 'certificate' => $egs_data['production_certificate']];
    }

    public function get_invoice_data()
    {
        $uuid = Str::uuid();
        $invoice_data = [
            "uuid" => $uuid,
            'bill_type' => InvoiceTypeCode::INVOICE,
            'invoice_type' => InvoiceTypeCode::TAX_INVOICE,
            // "file_name" => "vat + time + invoicenumber",
            "file_name" => "test",
            "invoice_counter" => 1,
            'invoice_number' => 'SME00062',
            "previous_invoice_hash" => "NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==",
            "trans_time" => new \DateTime(),
            'invoice_reference_commands' => 'Order Canceled',
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
                    'price' => 10,
                    'tax' => 7.2,
                    'rounding_amount' => 55.20, //amount without discount  include tax
                    'qty' => 5,
                    'unit_code' => "PCE",
                    'total_include_discount' => 48, //amount without discount without tax
                ],
                [
                    'item_name' => 'ALFA',
                    'price' => 10,
                    'tax' => 7.2,
                    'rounding_amount' => 55.20, //amount without discount  include tax
                    'qty' => 5,
                    'unit_code' => "PCE",
                    'total_include_discount' => 48, //amount without discount without tax
                ],
            ],
        ];
        return $invoice_data;
    }
}
