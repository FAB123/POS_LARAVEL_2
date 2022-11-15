<?php

namespace App\GAZT\EInvoice;

use App\GAZT\Xml\AdditionalDocumentReference;
use App\GAZT\Xml\Address;
use App\GAZT\Xml\AllowanceCharge;
use App\GAZT\Xml\BillingReference;
use App\GAZT\Xml\ClassifiedTaxCategory;
use App\GAZT\Xml\Country;
use App\GAZT\Xml\ExtensionContent;
use App\GAZT\Xml\Generator;
use App\GAZT\Xml\Invoice as XmlInvoice;
use App\GAZT\Xml\InvoiceLine;
use App\GAZT\Xml\InvoiceTypeCode;
use App\GAZT\Xml\Item;
use App\GAZT\Xml\LegalEntity;
use App\GAZT\Xml\LegalMonetaryTotal;
use App\GAZT\Xml\Party;
use App\GAZT\Xml\PartyTaxScheme;
use App\GAZT\Xml\PaymentMeans;
use App\GAZT\Xml\Price;
use App\GAZT\Xml\Signature;
use App\GAZT\Xml\SignatureInformation;
use App\GAZT\Xml\TaxCategory;
use App\GAZT\Xml\TaxScheme;
use App\GAZT\Xml\TaxSubTotal;
use App\GAZT\Xml\TaxTotal;
use App\GAZT\Xml\TextAttachment;
use App\GAZT\Xml\UBLDocumentSignatures;
use App\GAZT\Xml\UBLExtension;
use App\GAZT\Xml\UBLExtensions;
use App\GAZT\Xml\UnitCode;
use App\helpers\GenerateTVL;
use DateTime;
use Error;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class EInvoice
{
    private $egs_info;

    public function __construct($egs_info)
    {
        $this->egs_info = $egs_info;
    }

    public function GenrateInvoice($invoice_data, $certificate_string)
    {
        // 1: Invoice Hash
        $unsigned_invoice_xml = $this->generate_invoice($invoice_data, false);
        $invoice_template = $this->generate_invoice($invoice_data, 'template');

        //1: Generate Invoice Hash
        $invoice_hash = $this->generate_invoice_hash($unsigned_invoice_xml);

        //2: Generate Digital Signature
        $digital_signature = $this->createInvoiceDigitalSignature($invoice_hash);

        //4. Generate Signed Properties Hash
        $signed_properties_with_hash = $this->generate_signed_properties_hash($invoice_template, $this->egs_info['cert_info'], $invoice_data['trans_time']->format(DateTime::ATOM));

        // 5: QR
        $seller_name = $this->egs_info['VAT_name'];
        $vat_number = $this->egs_info['VAT_number'];
        $invoice_total = "1108.90";
        $vat_total = "144.90";

        $formatted_datetime = $invoice_data['trans_time']->format('Y-m-d\TH:i:s\Z');

        $tvl_generator = new GenerateTVL;

        $qr = $tvl_generator->generate([
            $seller_name,
            $vat_number,
            $formatted_datetime,
            $invoice_total,
            $vat_total,
            $invoice_hash,
            $digital_signature,
            base64_decode($this->egs_info['cert_info']['public_key']),
            hex2bin(base64_decode($this->egs_info['cert_info']['signature'], false)),
        ]);

        // 5: Populate the 6: Populate The UBL Extensions Output
        $signed_xml = $this->populate_ubl_extension($signed_properties_with_hash, $certificate_string, $digital_signature, $invoice_hash, $qr);

        $signed_invoice = [
            'invoice' => $signed_xml,
            "invoice_hash" => $invoice_hash,
            'qr' => $qr,
        ];
        return $signed_invoice;
    }

    //generate invoice hash
    private function generate_invoice_hash($unsigned_invoice_xml)
    {
        $xml_version = '<?xml version="1.0"?>';
        $canonicalized = trim($unsigned_invoice_xml, $xml_version);
        $dom = new \DOMDocument;
        $dom->loadXML($unsigned_invoice_xml);
        $canonicalized = $dom->C14N();

        $unsigned_invoice_xml = str_replace("<cbc:ProfileID>", "\n <cbc:ProfileID>", $canonicalized);
        $unsigned_invoice_xml = str_replace("<cac:AccountingSupplierParty>", "\n \n <cac:AccountingSupplierParty>", $unsigned_invoice_xml);

        return base64_encode(hash('sha256', $unsigned_invoice_xml, true));
    }

    //generate invoice security properties hash
    private function generate_signed_properties_hash($invoice, $cert_info, $time)
    {
        $template = '<xades:SignedProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="xadesSignedProperties">
         <xades:SignedSignatureProperties>
          <xades:SigningTime>SET_SIGN_TIMESTAMP</xades:SigningTime>
          <xades:SigningCertificate>
           <xades:Cert>
            <xades:CertDigest>
             <ds:DigestMethod xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
             <ds:DigestValue xmlns:ds="http://www.w3.org/2000/09/xmldsig#">SET_CERTIFICATE_HASH</ds:DigestValue>
            </xades:CertDigest>
            <xades:IssuerSerial>
             <ds:X509IssuerName xmlns:ds="http://www.w3.org/2000/09/xmldsig#">SET_CERTIFICATE_ISSUER</ds:X509IssuerName>
             <ds:X509SerialNumber xmlns:ds="http://www.w3.org/2000/09/xmldsig#">SET_CERTIFICATE_SERIAL_NUMBER</ds:X509SerialNumber>
            </xades:IssuerSerial>
           </xades:Cert>
          </xades:SigningCertificate>
         </xades:SignedSignatureProperties>
         </xades:SignedProperties>';

        $template = str_replace('SET_CERTIFICATE_HASH', $cert_info['hash'], $template);
        $template = str_replace('SET_SIGN_TIMESTAMP', $time, $template);
        $template = str_replace('SET_CERTIFICATE_SERIAL_NUMBER', $cert_info['serial_number'], $template);
        $template = str_replace('SET_CERTIFICATE_ISSUER', $cert_info['issuer'], $template);

        // Generate Signed Properties Hash
        $signed_properties_hash = base64_encode(hash('sha256', $template, false));
        $invoice = str_replace('<xades:SignedProperties/>', $template, $invoice);
        return [
            'signed_properties_hash' => $signed_properties_hash,
            'invoice' => $invoice,
        ];
    }

    //populate contents after hasing
    private function populate_ubl_extension($signed_properties_with_hash, $certificate, $digital_signature, $invoice_hash, $qr)
    {
        $invoice = $signed_properties_with_hash['invoice'];
        $signed_properties_hash = $signed_properties_with_hash['signed_properties_hash'];
        $invoice = str_replace('SET_CERTIFICATE', $certificate, $invoice);
        $invoice = str_replace('SET_DIGITAL_SIGNATURE', $digital_signature, $invoice);
        $invoice = str_replace('SET_SIGNED_PROPERTIES_HASH', $signed_properties_hash, $invoice);
        $invoice = str_replace('SET_INVOICE_HASH', $invoice_hash, $invoice);
        $invoice = str_replace('SET_QR_CODE', $qr, $invoice);
        return $invoice;
    }

    public function getCertificateInfo($certificate_string)
    {
        $wrapped_certificate_string = "-----BEGIN CERTIFICATE-----\n{$certificate_string}\n-----END CERTIFICATE-----";
        $hash = $this->getCertificateHash($certificate_string);
        $issuer = openssl_x509_parse($wrapped_certificate_string);
        $private_key_file = "/tmp/tmp-" . $this->egs_info['uuid'] . ".pem";
        Storage::put($private_key_file, $wrapped_certificate_string);
        $result = $this->run_openssl(["openssl", "x509", "-in", Storage::path($private_key_file), "-text", "-noout", "-certopt", "ca_default", "-certopt", "no_validity", "-certopt", "no_serial", "-certopt", "no_subject", "-certopt", "no_extensions", "-certopt", "no_signame"]);
        Storage::delete($private_key_file);

        $signature = str_replace(":", "", preg_replace('/\s+/', '', str_replace("Signature Algorithm: ecdsa-with-SHA256", "", $result)));
        // info($issuer['issuer']['CN']);
        // http_build_query($issuer['issuer'])
        $public_key = openssl_pkey_get_details(openssl_pkey_get_public($wrapped_certificate_string))['key'];

        $public_key = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\r\n", "\n"], ['', '', "\n", '',
        ], $public_key);

        return [
            "hash" => $hash,
            "issuer" => "CN={$issuer['issuer']['CN']}",
            "serial_number" => $this->bchexdec($issuer['serialNumberHex']),
            "public_key" => $public_key,
            "signature" => base64_encode($signature),
        ];
    }

    private function bchexdec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

    private function createInvoiceDigitalSignature($invoice_hash)
    {
        if (!$this->egs_info['cert_info']['private_key']) {
            throw new Error("EGS has no private key");
        }
        openssl_sign($invoice_hash, $signature, $this->egs_info['cert_info']['private_key'], 'sha256');
        return base64_encode($signature);
    }

    private function getCertificateHash($certificate_string)
    {
        $certificate_hash = hash("sha256", $certificate_string, false);
        return base64_encode($certificate_hash);
    }

    private function run_openssl($command)
    {
        $process = new Process($command);
        try {
            $process->mustRun();
            $result = $process->getOutput();
            return $result;
        } catch (ProcessFailedException $exception) {
            return $exception->getMessage();
        }
    }

    public function generate_invoice($invoice_data, $mode)
    {
        // Tax scheme
        $taxScheme = (new TaxScheme())
            ->setId("VAT");

        $country = (new Country())
            ->setIdentificationCode('SA');

        //additional documents
        $additional_documents = [];

        // invoice counter value
        $additional_documents[] = (new AdditionalDocumentReference())
            ->setId('ICV')
            ->setUUID($invoice_data['invoice_counter']);

        //Previus Invoice Hash
        $pih_attachment = (new TextAttachment)
            ->setMimeType('text/plain')
            ->setAttachmentText($invoice_data['previous_invoice_hash']);

        $additional_documents[] = (new AdditionalDocumentReference())
            ->setId('PIH')
            ->setAttachment($pih_attachment);

        if ($mode == 'template') {
            //QR CODE TEMPLATES
            $qr_attachment = (new TextAttachment)
                ->setMimeType('text/plain')
                ->setAttachmentText('SET_QR_CODE');

            $additional_documents[] = (new AdditionalDocumentReference())
                ->setId('QR')
                ->setAttachment($qr_attachment);

            //signature
            $signature = (new Signature)
                ->setId('urn:oasis:names:specification:ubl:signature:Invoice')
                ->setSignatureMethod('urn:oasis:names:specification:ubl:dsig:enveloped:xades');

        }

        // Full address
        $address = (new Address())
            ->setStreetName($this->egs_info['location']['street'])
            ->setBuildingNumber($this->egs_info['location']['building'])
            ->setPlotIdentification($this->egs_info['location']['plot_identification'])
            ->setCitySubdivisionName($this->egs_info['location']['city_subdivision'])
            ->setCityName($this->egs_info['location']['city'])
            ->setPostalZone($this->egs_info['location']['postal_zone'])
            ->setCountry($country);

        $legalEt = (new LegalEntity())
            ->setRegistrationName($this->egs_info['VAT_name']);

        $supplierTaxScheme = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme)
            ->setCompanyId($this->egs_info['VAT_number']);

        // Supplier company node
        $supplierCompany = (new Party())
            ->setPartyIdentificationType('CR')
            ->setPartyIdentificationId($this->egs_info['CRN_number'])
            ->setLegalEntity($legalEt)
            ->setPartyTaxScheme($supplierTaxScheme)
            ->setPostalAddress($address);

        // FILL IF CUSTOMER
        // Client company node

        $client_address = (new Address())
            ->setStreetName($invoice_data['client_data']['street_name'])
            ->setAdditionalStreetName($invoice_data['client_data']['additional_street_name'])
            ->setBuildingNumber($invoice_data['client_data']['building_number'])
            ->setPlotIdentification($invoice_data['client_data']['plot_identification'])
            ->setCitySubdivisionName($invoice_data['client_data']['city_sub_division_name'])
            ->setCityName($invoice_data['client_data']['city'])
            ->setPostalZone($invoice_data['client_data']['postal_zone'])
            ->setCountrySubentity($invoice_data['client_data']['country_subentity'])
            ->setCountry($country);

        $clientTaxScheme = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme);

        //Client contact node
        $clientContact = (new LegalEntity())
            ->setRegistrationName($invoice_data['client_data']['name']);

        // Client company node
        $clientCompany = (new Party())
            ->setPartyIdentificationType($invoice_data['client_data']['party_identification_type'])
            ->setPartyIdentificationId($invoice_data['client_data']['party_identification_id'])
            // ->setName('My client')
            ->setPostalAddress($client_address)
            // ->setContact($clientContact)
            ->setLegalEntity($clientContact)
            ->setPartyTaxScheme($clientTaxScheme);

        $legalMonetaryTotal = (new LegalMonetaryTotal())
            ->setLineExtensionAmount($invoice_data['cart_total']['total_without_discount'])
            ->setTaxExclusiveAmount($invoice_data['cart_total']['total_after_discount'])
            ->setTaxInclusiveAmount($invoice_data['cart_total']['total_with_vat'])
            ->setPrepaidAmount($invoice_data['cart_total']['prepaid_amount'])
            ->setPayableAmount($invoice_data['cart_total']['payable_amount'])
            ->setAllowanceTotalAmount($invoice_data['cart_total']['discount']);

        $classifiedTax = (new ClassifiedTaxCategory())
            ->setPercent(15)
            ->setTaxScheme($taxScheme);

        $invoiceLines = [];

        foreach ($invoice_data['items'] as $item) {
            $item_count = 1;
            // Product
            $productItem = (new Item())
                ->setName($item['item_name'])
                ->setClassifiedTaxCategory($classifiedTax);

            // Price
            $price = (new Price())
                ->setBaseQuantity(1)
                ->setUnitCode(UnitCode::UNIT)
                ->setPriceAmount($item['price']);

            // Invoice Line tax totals
            $lineTaxTotal = (new TaxTotal())
                ->setTaxAmount($item['tax'])
                ->setRoundingAmount($item['rounding_amount']);

            $invoiceLines[] = (new InvoiceLine())
                ->setUnitCode($item['unit_code'])
                ->setId($item_count)
                ->setItem($productItem)
                ->setPrice($price)
                ->setTaxTotal($lineTaxTotal)
                ->setLineExtensionAmount($item['total_include_discount'])
                ->setInvoicedQuantity($item['qty']);
            $item_count++;
        }

        // Total Taxes
        $taxCategory = (new TaxCategory())
            ->setPercent(15)
            ->setTaxScheme($taxScheme);

        $taxSubTotal = (new TaxSubTotal())
            ->setTaxableAmount($invoice_data['cart_total']['total_without_discount'])
            ->setTaxAmount($invoice_data['cart_total']['tax_amount'])
            ->setTaxCategory($taxCategory);

        $taxTotal = (new TaxTotal())
            ->addTaxSubTotal($taxSubTotal)
            ->setTaxAmount($invoice_data['cart_total']['tax_amount']);

        $invoiceTaxTotal = (new TaxTotal())
            ->setTaxAmount($invoice_data['cart_total']['tax_amount']);

        if ($mode == 'template') {
            $sign = (new SignatureInformation)
                ->setReferencedSignatureID("urn:oasis:names:specification:ubl:signature:Invoice")
                ->setID('urn:oasis:names:specification:ubl:signature:1');

            $ublDecoment = (new UBLDocumentSignatures)
                ->setSignatureInformation($sign);

            $extensionContent = (new ExtensionContent)
                ->setUBLDocumentSignatures($ublDecoment);

            $UBLExtension[] = (new UBLExtension)
                ->setExtensionURI('urn:oasis:names:specification:ubl:dsig:enveloped:xades')
                ->setExtensionContent($extensionContent);

            $UBLExtensions = (new UBLExtensions)
                ->setUBLExtensions($UBLExtension);
        }
        
        $allowance_charges = (new AllowanceCharge)
            ->setChargeIndicator(false)
            ->setAllowanceChargeReason('discount')
            ->setAmount($invoice_data['cart_total']['discount'])
            ->setTaxCategory($taxCategory);

        if ($invoice_data['bill_type'] == InvoiceTypeCode::CREDIT_NOTE ||
            $invoice_data['bill_type'] == InvoiceTypeCode::DEBIT_NOTE) {
            $payment_means = (new PaymentMeans)
                ->setInstructionNote($invoice_data['invoice_reference_commands']);
            $invoice_reference_id = (new BillingReference)
                ->setId($invoice_data['invoice_reference']);
            if ($mode == 'template') {
                // Invoice object
                $invoice = (new XmlInvoice())
                    ->setUBLExtensions($UBLExtensions)
                    ->setInvoiceBillTypeCode($invoice_data['bill_type'])
                    ->setInvoiceTypeCode($invoice_data['invoice_type'])
                    ->setPaymentMeans($payment_means)
                    ->setBillingReference($invoice_reference_id)
                    ->setUUID($invoice_data['uuid'])
                    ->setId($invoice_data['invoice_number'])
                    ->Signature($signature)
                    ->setIssueDate($invoice_data['trans_time'])
                    ->setIssueTime($invoice_data['trans_time'])
                    ->setAccountingSupplierParty($supplierCompany)
                    ->setAccountingCustomerParty($clientCompany)
                    ->setInvoiceLines($invoiceLines)
                    ->setAllowanceCharges([$allowance_charges])
                    ->setAdditionalDocumentReference($additional_documents)
                    ->setLegalMonetaryTotal($legalMonetaryTotal)
                    ->setTaxTotal($taxTotal)
                    ->setInvoiceTaxTotal($invoiceTaxTotal);
            } else {
                $invoice = (new XmlInvoice())
                    ->setUUID($invoice_data['uuid'])
                    ->setInvoiceBillTypeCode($invoice_data['bill_type'])
                    ->setInvoiceTypeCode($invoice_data['invoice_type'])
                    ->setPaymentMeans($payment_means)
                    ->setBillingReference($invoice_reference_id)
                    ->setId($invoice_data['invoice_number'])
                    ->setIssueDate($invoice_data['trans_time'])
                    ->setIssueTime($invoice_data['trans_time'])
                    ->setAccountingSupplierParty($supplierCompany)
                    ->setAccountingCustomerParty($clientCompany)
                    ->setInvoiceLines($invoiceLines)
                    ->setAllowanceCharges([$allowance_charges])
                    ->setAdditionalDocumentReference($additional_documents)
                    ->setLegalMonetaryTotal($legalMonetaryTotal)
                    ->setTaxTotal($taxTotal)
                    ->setInvoiceTaxTotal($invoiceTaxTotal);

            }
        } else {
            if ($mode == 'template') {
                // Invoice object
                $invoice = (new XmlInvoice())
                    ->setUBLExtensions($UBLExtensions)
                    ->setInvoiceBillTypeCode($invoice_data['bill_type'])
                    ->setInvoiceTypeCode($invoice_data['invoice_type'])
                    ->setUUID($invoice_data['uuid'])
                    ->setId($invoice_data['invoice_number'])
                    ->Signature($signature)
                    ->setIssueDate($invoice_data['trans_time'])
                    ->setIssueTime($invoice_data['trans_time'])
                    ->setAccountingSupplierParty($supplierCompany)
                    ->setAccountingCustomerParty($clientCompany)
                    ->setInvoiceLines($invoiceLines)
                    ->setAllowanceCharges([$allowance_charges])
                    ->setAdditionalDocumentReference($additional_documents)
                    ->setLegalMonetaryTotal($legalMonetaryTotal)
                    ->setTaxTotal($taxTotal)
                    ->setInvoiceTaxTotal($invoiceTaxTotal);
            } else {
                $invoice = (new XmlInvoice())
                    ->setUUID($invoice_data['uuid'])
                    ->setInvoiceBillTypeCode($invoice_data['bill_type'])
                    ->setInvoiceTypeCode($invoice_data['invoice_type'])

                    ->setId($invoice_data['invoice_number'])
                    ->setIssueDate($invoice_data['trans_time'])
                    ->setIssueTime($invoice_data['trans_time'])
                    ->setAccountingSupplierParty($supplierCompany)
                    ->setAccountingCustomerParty($clientCompany)
                    ->setInvoiceLines($invoiceLines)
                    ->setAllowanceCharges([$allowance_charges])
                    ->setAdditionalDocumentReference($additional_documents)
                    ->setLegalMonetaryTotal($legalMonetaryTotal)
                    ->setTaxTotal($taxTotal)
                    ->setInvoiceTaxTotal($invoiceTaxTotal);

            }
        }

        $generator = new Generator();

        return $generator->invoice($invoice);

    }

    // public function simplified_invoice_note($invoice_data, $mode)
    // {
    //     // Tax scheme
    //     $taxScheme = (new TaxScheme())
    //         ->setId("VAT");

    //     $country = (new Country())
    //         ->setIdentificationCode('SA');

    //     //additional documents
    //     $additional_documents = [];

    //     // invoice counter value
    //     $additional_documents[] = (new AdditionalDocumentReference())
    //         ->setId('ICV')
    //         ->setUUID($invoice_data['invoice_counter']);

    //     //Previus Invoice Hash
    //     $pih_attachment = (new TextAttachment)
    //         ->setMimeType('text/plain')
    //         ->setAttachmentText($invoice_data['previous_invoice_hash']);

    //     $additional_documents[] = (new AdditionalDocumentReference())
    //         ->setId('PIH')
    //         ->setAttachment($pih_attachment);

    //     if ($mode == 'template') {
    //         //QR CODE TEMPLATES
    //         $qr_attachment = (new TextAttachment)
    //             ->setMimeType('text/plain')
    //             ->setAttachmentText('SET_QR_CODE');

    //         $additional_documents[] = (new AdditionalDocumentReference())
    //             ->setId('QR')
    //             ->setAttachment($qr_attachment);

    //         //signature
    //         $signature = (new Signature)
    //             ->setId('urn:oasis:names:specification:ubl:signature:Invoice')
    //             ->setSignatureMethod('urn:oasis:names:specification:ubl:dsig:enveloped:xades');

    //     }

    //     // Full address
    //     $address = (new Address())
    //         ->setStreetName($this->egs_info['location']['street'])
    //         ->setBuildingNumber($this->egs_info['location']['building'])
    //         ->setPlotIdentification($this->egs_info['location']['plot_identification'])
    //         ->setCitySubdivisionName($this->egs_info['location']['city_subdivision'])
    //         ->setCityName($this->egs_info['location']['city'])
    //         ->setPostalZone($this->egs_info['location']['postal_zone'])
    //         ->setCountry($country);

    //     $legalEt = (new LegalEntity())
    //         ->setRegistrationName($this->egs_info['VAT_name']);

    //     $supplierTaxScheme = (new PartyTaxScheme())
    //         ->setTaxScheme($taxScheme)
    //         ->setCompanyId($this->egs_info['VAT_number']);

    //     // Supplier company node
    //     $supplierCompany = (new Party())
    //         ->setPartyIdentificationType('CR')
    //         ->setPartyIdentificationId($this->egs_info['CRN_number'])
    //         ->setLegalEntity($legalEt)
    //         ->setPartyTaxScheme($supplierTaxScheme)
    //         ->setPostalAddress($address);

    //     // FILL IF CUSTOMER
    //     // Client company node

    //     $client_address = (new Address())
    //         ->setStreetName($invoice_data['client_data']['street_name'])
    //         ->setAdditionalStreetName($invoice_data['client_data']['additional_street_name'])
    //         ->setBuildingNumber($invoice_data['client_data']['building_number'])
    //         ->setPlotIdentification($invoice_data['client_data']['plot_identification'])
    //         ->setCitySubdivisionName($invoice_data['client_data']['city_sub_division_name'])
    //         ->setCityName($invoice_data['client_data']['city'])
    //         ->setPostalZone($invoice_data['client_data']['postal_zone'])
    //         ->setCountrySubentity($invoice_data['client_data']['country_subentity'])
    //         ->setCountry($country);

    //     $clientTaxScheme = (new PartyTaxScheme())
    //         ->setTaxScheme($taxScheme);

    //     //Client contact node
    //     $clientContact = (new LegalEntity())
    //         ->setRegistrationName($invoice_data['client_data']['name']);

    //     // Client company node
    //     $clientCompany = (new Party())
    //         ->setPartyIdentificationType($invoice_data['client_data']['party_identification_type'])
    //         ->setPartyIdentificationId($invoice_data['client_data']['party_identification_id'])
    //         // ->setName('My client')
    //         ->setPostalAddress($client_address)
    //         // ->setContact($clientContact)
    //         ->setLegalEntity($clientContact)
    //         ->setPartyTaxScheme($clientTaxScheme);

    //     $legalMonetaryTotal = (new LegalMonetaryTotal())
    //         ->setLineExtensionAmount($invoice_data['cart_total']['total_without_discount'])
    //         ->setTaxExclusiveAmount($invoice_data['cart_total']['total_after_discount'])
    //         ->setTaxInclusiveAmount($invoice_data['cart_total']['total_with_vat'])
    //         ->setPrepaidAmount($invoice_data['cart_total']['prepaid_amount'])
    //         ->setPayableAmount($invoice_data['cart_total']['payable_amount'])
    //         ->setAllowanceTotalAmount($invoice_data['cart_total']['discount']);

    //     $classifiedTax = (new ClassifiedTaxCategory())
    //         ->setPercent(15)
    //         ->setTaxScheme($taxScheme);

    //     $invoiceLines = [];

    //     foreach ($invoice_data['items'] as $item) {
    //         $item_count = 1;
    //         // Product
    //         $productItem = (new Item())
    //             ->setName($item['item_name'])
    //             ->setClassifiedTaxCategory($classifiedTax);

    //         // Price
    //         $price = (new Price())
    //             ->setBaseQuantity(1)
    //             ->setUnitCode(UnitCode::UNIT)
    //             ->setPriceAmount($item['price']);

    //         // Invoice Line tax totals
    //         $lineTaxTotal = (new TaxTotal())
    //             ->setTaxAmount($item['tax'])
    //             ->setRoundingAmount($item['rounding_amount']);

    //         $invoiceLines[] = (new InvoiceLine())
    //             ->setUnitCode($item['unit_code'])
    //             ->setId($item_count)
    //             ->setItem($productItem)
    //             ->setPrice($price)
    //             ->setTaxTotal($lineTaxTotal)
    //             ->setLineExtensionAmount($item['total_include_discount'])
    //             ->setInvoicedQuantity($item['qty']);
    //         $item_count++;
    //     }

    //     // Total Taxes
    //     $taxCategory = (new TaxCategory())
    //         ->setPercent(15)
    //         ->setTaxScheme($taxScheme);

    //     $taxSubTotal = (new TaxSubTotal())
    //         ->setTaxableAmount($invoice_data['cart_total']['total_without_discount'])
    //         ->setTaxAmount($invoice_data['cart_total']['tax_amount'])
    //         ->setTaxCategory($taxCategory);

    //     $taxTotal = (new TaxTotal())
    //         ->addTaxSubTotal($taxSubTotal)
    //         ->setTaxAmount($invoice_data['cart_total']['tax_amount']);

    //     $invoiceTaxTotal = (new TaxTotal())
    //         ->setTaxAmount($invoice_data['cart_total']['tax_amount']);

    //     if ($mode == 'template') {
    //         $sign = (new SignatureInformation)
    //             ->setReferencedSignatureID("urn:oasis:names:specification:ubl:signature:Invoice")
    //             ->setID('urn:oasis:names:specification:ubl:signature:1');

    //         $ublDecoment = (new UBLDocumentSignatures)
    //             ->setSignatureInformation($sign);

    //         $extensionContent = (new ExtensionContent)
    //             ->setUBLDocumentSignatures($ublDecoment);

    //         $UBLExtension[] = (new UBLExtension)
    //             ->setExtensionURI('urn:oasis:names:specification:ubl:dsig:enveloped:xades')
    //             ->setExtensionContent($extensionContent);

    //         $UBLExtensions = (new UBLExtensions)
    //             ->setUBLExtensions($UBLExtension);
    //     }
    //     $allowance_charges = (new AllowanceCharge)
    //         ->setChargeIndicator(false)
    //         ->setAllowanceChargeReason('discount')
    //         ->setAmount($invoice_data['cart_total']['discount'])
    //         ->setTaxCategory($taxCategory);

    //     $payment_means = (new PaymentMeans)
    //         ->setInstructionNote('CANCEL');

    //     $invoice_reference_id = (new BillingReference)
    //         ->setId('SME00061');

    //     if ($mode == 'template') {
    //         // Invoice object
    //         $invoice = (new XmlInvoice())
    //             ->setUBLExtensions($UBLExtensions)
    //             ->setInvoiceBillTypeCode(InvoiceTypeCode::INVOICE)
    //             ->setInvoiceTypeCode(InvoiceTypeCode::SIMPIFIED_TAX_INVOICE)
    //             ->setUUID($invoice_data['uuid'])
    //             ->setId($invoice_data['invoice_number'])
    //             ->Signature($signature)
    //             ->setIssueDate($invoice_data['trans_time'])
    //             ->setIssueTime($invoice_data['trans_time'])
    //             ->setPaymentMeans($payment_means)
    //             ->setBillingReference($invoice_reference_id)
    //             ->setAccountingSupplierParty($supplierCompany)
    //             ->setAccountingCustomerParty($clientCompany)
    //             ->setInvoiceLines($invoiceLines)
    //             ->setAllowanceCharges([$allowance_charges])
    //             ->setAdditionalDocumentReference($additional_documents)
    //             ->setLegalMonetaryTotal($legalMonetaryTotal)
    //             ->setTaxTotal($taxTotal)
    //             ->setInvoiceTaxTotal($invoiceTaxTotal);
    //     } else {
    //         $invoice = (new XmlInvoice())
    //             ->setUUID($invoice_data['uuid'])
    //             ->setInvoiceBillTypeCode(InvoiceTypeCode::INVOICE)
    //             ->setInvoiceTypeCode(InvoiceTypeCode::SIMPIFIED_TAX_INVOICE)
    //             ->setId('SME00062')
    //             ->setIssueDate($invoice_data['trans_time'])
    //             ->setIssueTime($invoice_data['trans_time'])
    //             ->setPaymentMeans($payment_means)
    //             ->setBillingReference($invoice_reference_id)
    //             ->setAccountingSupplierParty($supplierCompany)
    //             ->setAccountingCustomerParty($clientCompany)
    //             ->setInvoiceLines($invoiceLines)
    //             ->setAllowanceCharges([$allowance_charges])
    //             ->setAdditionalDocumentReference($additional_documents)
    //             ->setLegalMonetaryTotal($legalMonetaryTotal)
    //             ->setTaxTotal($taxTotal)
    //             ->setInvoiceTaxTotal($invoiceTaxTotal);

    //     }
    //     $generator = new Generator();

    //     return $generator->invoice($invoice);

    // }

    // public function simplified_invoice_note1($invoice_data, $mode)
    // {
    //     // Tax scheme
    //     $taxScheme = (new TaxScheme())
    //         ->setId("VAT");

    //     $country = (new Country())
    //         ->setIdentificationCode('SA');

    //     //additional documents
    //     $additional_documents = [];

    //     // invoice counter value
    //     $additional_documents[] = (new AdditionalDocumentReference())
    //         ->setId('ICV')
    //         ->setUUID($invoice_data['invoice_counter']);

    //     //Previus Invoice Hash
    //     $pih_attachment = (new TextAttachment)
    //         ->setMimeType('text/plain')
    //         ->setAttachmentText($invoice_data['previous_invoice_hash']);

    //     $additional_documents[] = (new AdditionalDocumentReference())
    //         ->setId('PIH')
    //         ->setAttachment($pih_attachment);

    //     if ($mode == 'template') {
    //         //QR CODE TEMPLATES
    //         $qr_attachment = (new TextAttachment)
    //             ->setMimeType('text/plain')
    //             ->setAttachmentText('SET_QR_CODE');

    //         $additional_documents[] = (new AdditionalDocumentReference())
    //             ->setId('QR')
    //             ->setAttachment($qr_attachment);

    //         //signature
    //         $signature = (new Signature)
    //             ->setId('urn:oasis:names:specification:ubl:signature:Invoice')
    //             ->setSignatureMethod('urn:oasis:names:specification:ubl:dsig:enveloped:xades');

    //     }

    //     // Full address
    //     $address = (new Address())
    //         ->setStreetName($this->egs_info['location']['street'])
    //         ->setBuildingNumber($this->egs_info['location']['building'])
    //         ->setPlotIdentification($this->egs_info['location']['plot_identification'])
    //         ->setCitySubdivisionName($this->egs_info['location']['city_subdivision'])
    //         ->setCityName($this->egs_info['location']['city'])
    //         ->setPostalZone($this->egs_info['location']['postal_zone'])
    //         ->setCountry($country);

    //     $legalEt = (new LegalEntity())
    //         ->setRegistrationName($this->egs_info['VAT_name']);

    //     $supplierTaxScheme = (new PartyTaxScheme())
    //         ->setTaxScheme($taxScheme)
    //         ->setCompanyId($this->egs_info['VAT_number']);

    //     // Supplier company node
    //     $supplierCompany = (new Party())
    //         ->setPartyIdentificationType('CR')
    //         ->setPartyIdentificationId($this->egs_info['CRN_number'])
    //         ->setLegalEntity($legalEt)
    //         ->setPartyTaxScheme($supplierTaxScheme)
    //         ->setPostalAddress($address);

    //     // FILL IF CUSTOMER
    //     // Client company node
    //     $client_address = (new Address())
    //         ->setStreetName('baaoun')
    //         ->setAdditionalStreetName('sdsd')
    //         ->setBuildingNumber(3353)
    //         ->setPlotIdentification(3434)
    //         ->setCitySubdivisionName('fgff')
    //         ->setCityName('Dhurma')
    //         ->setPostalZone('34534')
    //         ->setCountrySubentity('ulhk')
    //         ->setCountry($country);

    //     $clientTaxScheme = (new PartyTaxScheme())
    //         ->setTaxScheme($taxScheme);

    //     //Client contact node
    //     $clientContact = (new LegalEntity())
    //         ->setRegistrationName('sdsa');

    //     // Client company node
    //     $clientCompany = (new Party())
    //         ->setPartyIdentificationType('NAT')
    //         ->setPartyIdentificationId("2345")
    //         // ->setName('My client')
    //         ->setPostalAddress($client_address)
    //         // ->setContact($clientContact);
    //         ->setLegalEntity($clientContact)
    //         ->setPartyTaxScheme($clientTaxScheme);

    //     $legalMonetaryTotal = (new LegalMonetaryTotal())
    //         ->setLineExtensionAmount(966)
    //         ->setTaxExclusiveAmount(964)
    //         ->setTaxInclusiveAmount(1108.9)
    //         ->setPrepaidAmount(0)
    //         ->setPayableAmount(1108.9)
    //         ->setAllowanceTotalAmount(2);

    //     $classifiedTax = (new ClassifiedTaxCategory())
    //         ->setPercent(15)
    //         ->setTaxScheme($taxScheme);
    //     // Product
    //     $productItem = (new Item())
    //         ->setName('Product Name')
    //         ->setClassifiedTaxCategory($classifiedTax);

    //     // Price
    //     $price = (new Price())
    //         ->setBaseQuantity(1)
    //         ->setUnitCode(UnitCode::UNIT)
    //         ->setPriceAmount(22);

    //     // Invoice Line tax totals
    //     $lineTaxTotal = (new TaxTotal())
    //         ->setTaxAmount(144.9)
    //         ->setRoundingAmount(1110.9);

    //     $invoiceLines = [];

    //     $invoiceLines[] = (new InvoiceLine())
    //         ->setUnitCode("PCE")
    //         ->setId(1)
    //         ->setItem($productItem)
    //         ->setPrice($price)
    //         ->setTaxTotal($lineTaxTotal)
    //         ->setLineExtensionAmount(966)
    //         ->setInvoicedQuantity(44);

    //     // Total Taxes
    //     $taxCategory = (new TaxCategory())
    //         ->setPercent(15)
    //         ->setTaxScheme($taxScheme);

    //     $taxSubTotal = (new TaxSubTotal())
    //         ->setTaxableAmount(966)
    //         ->setTaxAmount(144.9)
    //         ->setTaxCategory($taxCategory);

    //     $taxTotal = (new TaxTotal())
    //         ->addTaxSubTotal($taxSubTotal)
    //         ->setTaxAmount(144.9);

    //     $invoiceTaxTotal = (new TaxTotal())
    //         ->setTaxAmount(144.9);

    //     if ($mode == 'template') {
    //         $sign = (new SignatureInformation)
    //             ->setReferencedSignatureID("urn:oasis:names:specification:ubl:signature:Invoice")
    //             ->setID('urn:oasis:names:specification:ubl:signature:1');

    //         $ublDecoment = (new UBLDocumentSignatures)
    //             ->setSignatureInformation($sign);

    //         $extensionContent = (new ExtensionContent)
    //             ->setUBLDocumentSignatures($ublDecoment);

    //         $UBLExtension[] = (new UBLExtension)
    //             ->setExtensionURI('urn:oasis:names:specification:ubl:dsig:enveloped:xades')
    //             ->setExtensionContent($extensionContent);

    //         $UBLExtensions = (new UBLExtensions)
    //             ->setUBLExtensions($UBLExtension);
    //     }
    //     $allowance_charges = (new AllowanceCharge)
    //         ->setChargeIndicator(false)
    //         ->setAllowanceChargeReason('discount')
    //         ->setAmount(2)
    //         ->setTaxCategory($taxCategory);

    //     $payment_means = (new PaymentMeans)
    //         ->setInstructionNote('CANCEL');

    //     $invoice_reference_id = (new BillingReference)
    //         ->setId('SME00061');

    //     if ($mode == 'template') {
    //         // Invoice object
    //         $invoice = (new XmlInvoice())
    //             ->setUBLExtensions($UBLExtensions)
    //             ->setInvoiceBillTypeCode(InvoiceTypeCode::INVOICE)
    //             ->setInvoiceTypeCode(InvoiceTypeCode::SIMPIFIED_TAX_INVOICE)
    //             ->setUUID($invoice_data['uuid'])
    //             ->setId('SME00062')
    //             ->Signature($signature)
    //             ->setPaymentMeans($payment_means)
    //             ->setBillingReference($invoice_reference_id)
    //             ->setIssueDate(new \DateTime())
    //             ->setIssueTime((new \DateTime()))
    //             ->setAccountingSupplierParty($supplierCompany)
    //             ->setAccountingCustomerParty($clientCompany)
    //             ->setInvoiceLines($invoiceLines)
    //             ->setAllowanceCharges([$allowance_charges])
    //             ->setAdditionalDocumentReference($additional_documents)
    //             ->setLegalMonetaryTotal($legalMonetaryTotal)
    //             ->setTaxTotal($taxTotal)
    //             ->setInvoiceTaxTotal($invoiceTaxTotal);
    //     } else {
    //         $invoice = (new XmlInvoice())
    //             ->setUUID($invoice_data['uuid'])
    //             ->setInvoiceBillTypeCode(InvoiceTypeCode::INVOICE)
    //             ->setInvoiceTypeCode(InvoiceTypeCode::SIMPIFIED_TAX_INVOICE)
    //             ->setId('SME00062')
    //             ->setPaymentMeans($payment_means)
    //             ->setBillingReference($invoice_reference_id)
    //             ->setIssueDate(new \DateTime())
    //             ->setIssueTime((new \DateTime()))
    //             ->setAccountingSupplierParty($supplierCompany)
    //             ->setAccountingCustomerParty($clientCompany)
    //             ->setInvoiceLines($invoiceLines)
    //             ->setAllowanceCharges([$allowance_charges])
    //             ->setAdditionalDocumentReference($additional_documents)
    //             ->setLegalMonetaryTotal($legalMonetaryTotal)
    //             ->setTaxTotal($taxTotal)
    //             ->setInvoiceTaxTotal($invoiceTaxTotal);

    //     }
    //     $generator = new Generator();
    //     // $dom = new DOMDocument();
    //     // $dom->loadXML($generator->invoice($invoice));
    //     // $dom->save('/tmp/xxx.xml');

    //     return $generator->invoice($invoice);
    // }

}
