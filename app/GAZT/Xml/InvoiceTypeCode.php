<?php

namespace App\GAZT\Xml;

/**
 * All possible Unit Codes that can be used
 * To extend, see also: http://tfig.unece.org/contents/recommendation-20.htm
 */
class InvoiceTypeCode
{

    // const CORRECTED_INVOICE = 384;
    // const SELF_BILLING_INVOICE = 389;
    const INVOICE = 388;
    const CREDIT_NOTE = 381;
    const DEBIT_NOTE = 383;
    const TAX_INVOICE = "0100000";
    const SIMPIFIED_TAX_INVOICE = "0200000";
}
