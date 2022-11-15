<?php

namespace App\GAZT\Xml;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class BillingReference implements XmlSerializable
{
    private $id;
    // private $salesOrderId;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return BillingReference
     */
    public function setId(string $id): BillingReference
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getSalesOrderId(): string
    {
        return $this->salesOrderId;
    }

    /**
     * @param string $salesOrderId
     * @return OrderReference
     */
    public function setSalesOrderId(string $salesOrderId): BillingReference
    {
        $this->salesOrderId = $salesOrderId;
        return $this;
    }

    /**
     * The xmlSerialize method is called during xml writing.
     *
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([Schema::CAC . 'InvoiceDocumentReference' =>
            [Schema::CBC . 'ID' => 'Invoice Number:' . $this->id],
            // if ($this->salesOrderId !== null) {
            //     $writer->write([ Schema::CBC . 'SalesOrderID' => $this->salesOrderId ]);
            // }
        ]);

    }
}
