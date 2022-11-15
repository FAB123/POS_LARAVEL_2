<?php

namespace App\GAZT\Xml;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class GaztCrypto implements XmlSerializable
{
    private $invoiceHash;
    private $signed_properties_hash;
    private $digital_signature;
    private $certificate;
    private $signed_properties_xml;

    /**
     * @return string
     */
    public function getInvoiceHash(): string
    {
        return $this->invoiceHash;
    }

    /**
     * @param string $invoiceHash
     * @return UBLExtension
     */
    public function setInvoiceHash(string $invoiceHash): GaztCrypto
    {
        $this->invoiceHash = $invoiceHash;
        return $this;
    }

    /**
     * @return string
     */
    public function getSignedPropertiesHash(): string
    {
        return $this->signed_properties_hash;
    }

    /**
     * @param string $signed_properties_hash
     * @return UBLExtension
     */
    public function setSignedPropertiesHash(string $signed_properties_hash): GaztCrypto
    {
        $this->signed_properties_hash = $signed_properties_hash;
        return $this;
    }

    /**
     * @return string
     */
    public function getDigitalSignature(): string
    {
        return $this->digital_signature;
    }

    /**
     * @param string $digital_signature
     * @return UBLExtension
     */
    public function setDigitalSignature(string $digital_signature): GaztCrypto
    {
        $this->digital_signature = $digital_signature;
        return $this;
    }

    /**
     * @return string
     */
    public function getCertificate(): string
    {
        return $this->certificate;
    }

    /**
     * @param string $certificate
     * @return UBLExtension
     */
    public function setCertificate(string $certificate): GaztCrypto
    {
        $this->certificate = $certificate;
        return $this;
    }

    /**
     * @return string
     */
    public function getSignedPropertiesXml(): string
    {
        return $this->signed_properties_xml;
    }

    /**
     * @param string $signed_properties_xml
     * @return UBLExtension
     */
    public function setSignedPropertiesXml(string $signed_properties_xml): GaztCrypto
    {
        $this->signed_properties_xml = $signed_properties_xml;
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
        $writer->write([
            [Schema::SIG . 'UBLDocumentSignatures' => $this->extensionURI],
        ]);
    }
}
