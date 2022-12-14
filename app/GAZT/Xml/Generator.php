<?php

namespace App\GAZT\Xml;

use Sabre\Xml\Service;

class Generator
{
    public static $currencyID;

    public static function invoice(Invoice $invoice, $currencyId = 'SAR')
    {
        self::$currencyID = $currencyId;

        $xmlService = new Service();

        $xmlService->namespaceMap = [
            'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2' => '',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2' => 'cac',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2' => 'cbc',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2' => 'ext',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2' => 'sig',
            'urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2' => 'sac',
            'urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2' => 'sbc',
            'http://www.w3.org/2000/09/xmldsig#' => 'ds',
            'http://uri.etsi.org/01903/v1.3.2#' => 'xades',
        ];

        return $xmlService->write('Invoice', [
            $invoice,
        ]);
    }
}
