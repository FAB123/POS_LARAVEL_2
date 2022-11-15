<?php
namespace App\GAZT\EInvoice;

use Illuminate\Support\Str;

class InvoiceData
{
    private $uuid;
    private $bill_type;
    private $invoice_type;
    private $invoice_number;
    private $trans_time;
    private $invoice_reference_commands;
    private $invoice_reference;
    private $client_data;
    private $cart_total;
    private $items;

    public function __construct()
    {
        $this->uuid = Str::uuid();
    }

    public function set_bill_type($bill_type)
    {
        $this->bill_type = $bill_type;
    }

    public function set_invoice_type($invoice_type)
    {
        $this->invoice_type = $invoice_type;
    }

    public function set_invoice_number($invoice_number)
    {
        $this->invoice_number = $invoice_number;
    }

    public function set_trans_time($trans_time)
    {
        $this->trans_time = $trans_time;
    }

    public function set_invoice_reference_commands($invoice_reference_commands)
    {
        $this->invoice_reference_commands = $invoice_reference_commands;
    }

    public function set_invoice_reference($invoice_reference)
    {
        $this->invoice_reference = $invoice_reference;
    }

    public function set_client_data($client_data)
    {
        $this->client_data = $client_data;
    }

    public function set_cart_total($cart_total)
    {
        $this->cart_total = $cart_total;
    }

    public function set_items($items)
    {
        $this->items = $items;
    }

    public function generate()
    {
        return [
            "uuid" => $this->uuid,
            'bill_type' => $this->bill_type,
            'invoice_type' => $this->invoice_type,
            "filename" => "_{$this->trans_time->format('YmdHis')}_{$this->invoice_number}",
            'invoice_number' => $this->invoice_number,
            "trans_time" => $this->trans_time,
            'invoice_reference_commands' => $this->invoice_reference_commands,
            'invoice_reference' => $this->invoice_reference,
            "client_data" => $this->client_data,
            'cart_total' => $this->cart_total,
            'items' => $this->items,
        ];

    }

}
