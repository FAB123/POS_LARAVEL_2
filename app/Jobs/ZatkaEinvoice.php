<?php

namespace App\Jobs;

use App\Models\Configurations\Configuration;
use App\Models\GaztData;
use App\Models\Sales\Sale;
use App\Models\Sales\SalesItem;
use Error;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ZatkaEinvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $invoice_data;
    public $invoice_id;
    public $invoice_type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($invoice_id, $invoice_type)
    {
        $this->invoice_id = $invoice_id;
        $this->invoice_type = $invoice_type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $invoice_data = $this->get_invoice_data();
        // "invoice_counter" => 1,
        // "previous_invoice_hash" => "NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==",

        // $egs_data = $this->get_egs_info();
        // $invoice_data = $this->invoice_data;

        // $invoice = new EInvoice($egs_data['egs_info']);
        // $signed_invoice = $invoice->GenrateInvoice($invoice_data, $egs_data['certificate']);
        // $egs = new EGenerator($egs_data['egs_info']);

        // $response = $egs->report_invoice($signed_invoice['invoice'], $signed_invoice['invoice_hash'], $invoice_data['uuid']);
        // Storage::put("Einvoices/{$egs_data['egs_info']['VAT_number']}{$invoice_data['filename']}.xml", $signed_invoice['invoice']);
        // info($response);
        // return $egs->clearance_invoice($signed_invoice['invoice'], $signed_invoice['invoice_hash'], $uuid);

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

    private function get_invoice_data()
    {
        $invoice_type = $this->invoice_type;
        $invoice_id = $this->invoice_id;

        //fetch data invoice data
        if ($invoice_type == 383) {

        } else {
            $sales_data = $this->fetch_sale($invoice_id);
            info($sales_data);
        }
        // info($invoice_data->generate());

        // $invoice_data = new InvoiceData();
        // $invoice_data->set_invoice_number($sales->sale_id);
        // $invoice_data->set_trans_time($sales->created_at);
        // $invoice_data->set_bill_type($sales->bill_type == 'B2C' ? InvoiceTypeCode::SIMPIFIED_TAX_INVOICE : InvoiceTypeCode::TAX_INVOICE);
        // $invoice_data->set_invoice_type($invoice_type);
        // $invoice_data->set_items($xml_item_list);

        // if ($customer_id) {
        //     $customer_data = Customer::select('name',
        //         'company_name', 'identity_type', 'party_id', 'city',
        //         'city_sub_division', 'street', 'additional_street',
        //         'building_number', 'plot_identification', 'state', 'zip')
        //         ->join('customer_details', 'customer_details.customer_id', 'customers.customer_id')
        //         ->find($customer_id);

        //     $client_data = [
        //         'name' => $customer_data['company_name'] ? $customer_data['company_name'] : $customer_data['name'],
        //         'street_name' => $customer_data['street'],
        //         'additional_street_name' => $customer_data['additional_street'],
        //         'building_number' => $customer_data['building_number'],
        //         'plot_identification' => $customer_data['plot_identification'],
        //         'city' => $customer_data['city'],
        //         'city_sub_division_name' => $customer_data['city_sub_division'],
        //         'postal_zone' => $customer_data['zip'],
        //         'party_identification_type' => $customer_data['identity_type'],
        //         'party_identification_id' => $customer_data['party_id'],
        //     ];
        //     $invoice_data->set_client_data($client_data);
        // }

        // info($invoice_data->generate());
    }

    private function fetch_sale($sale_id)
    {
        $sales_data = Sale::with([
            'customer' => function ($query) {
                $query->leftJoin('customer_details', 'customers.customer_id', 'customer_details.customer_id');
            },
        ])->find($sale_id);

        $sales_items = SalesItem::select('sales_items.sale_id as sale_id', 'sales_items.item_id as item_id', 'sales_items.*', 'items.item_name', 'store_units.unit_name_en')
            ->with([
                'tax_details' => function ($query) use ($sale_id) {
                    $query->where('sale_id', $sale_id)->select(['percent', 'amount', 'item_id']);
                },
            ])
            ->join('items', 'items.item_id', 'sales_items.item_id')
            ->join('store_units', 'items.unit_type', 'store_units.unit_id')
            ->where('sale_id', $sale_id)->get();

        $sales_data["items"] = $sales_items->map(function ($item, $key) {

            if ($item->discount_type == 'P') {
                $discount = ($item->discount / 100) * ($item->item_unit_price * $item->sold_quantity);
            } else {
                $discount = $item->discount;
            }

            return [
                'item_name' => $item->item_name,
                'unit_code' => $item->unit_name_en,
                'qty' => $item->sold_quantity,
                'tax' => $item->tax_details->sum('amount'),
                'price' => number_format(($item->item_sub_total + $item->discount) / $item->sold_quantity, 2),
                'rounding_amount' => number_format($item->item_sub_total + $item->tax_details->sum('amount'), 2),
                'discount' => $discount,
                'total_include_discount' => $item->item_sub_total,
                'tax_percent' => $item->tax_details->sum('percent'),
            ];
        });
        return $sales_data;
    }
}
