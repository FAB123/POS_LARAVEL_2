<?php

namespace App\GAZT\Xml;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class SignatureInformation implements XmlSerializable
{

    private $id;
    private $referencedSignatureID;
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
    public function setInvoiceHash(string $invoiceHash): SignatureInformation
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
    public function setSignedPropertiesHash(string $signed_properties_hash): SignatureInformation
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
    public function setDigitalSignature(string $digital_signature): SignatureInformation
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
    public function setCertificate(string $certificate): SignatureInformation
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
    public function setSignedPropertiesXml(string $signed_properties_xml): SignatureInformation
    {
        $this->signed_properties_xml = $signed_properties_xml;
        return $this;
    }

    /**
     * @param string $id
     * @return SignatureInformation
     */
    public function setID(string $id): SignatureInformation
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function gettID(): string
    {
        return $this->id;
    }

    /**
     * @param string $referencedSignatureID
     * @return SignatureInformation
     */
    public function setReferencedSignatureID(string $referencedSignatureID): SignatureInformation
    {
        $this->referencedSignatureID = $referencedSignatureID;
        return $this;
    }

    /**
     * @return string
     */
    public function getReferencedSignatureID(string $id): string
    {
        return $this->referencedSignatureID;
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
            [Schema::CBC . 'ID' => $this->id],
            [Schema::SBC . 'ReferencedSignatureID' => $this->referencedSignatureID],
            [Schema::DS . 'Signature' =>
                [
                    [
                        Schema::DS . 'SignedInfo' => [
                            [
                                "name" => Schema::DS . 'CanonicalizationMethod',
                                "attributes" => [
                                    "Algorithm" => "http://www.w3.org/2006/12/xml-c14n11",
                                ],
                            ],
                            [
                                "name" => Schema::DS . 'SignatureMethod',
                                "attributes" => [
                                    "Algorithm" => "http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256",
                                ],
                            ],
                            [
                                [
                                    "name" => Schema::DS . 'Reference',
                                    "attributes" => [
                                        "Id" => "invoiceSignedData",
                                        "URI" => "",
                                    ],
                                    "value" => [
                                        [
                                            "name" => Schema::DS . 'Transforms',
                                            "value" => [
                                                [
                                                    "name" => Schema::DS . 'Transform',
                                                    "attributes" => [
                                                        "Algorithm" => "http://www.w3.org/TR/1999/REC-xpath-19991116",
                                                    ],
                                                    "value" => [
                                                        "name" => Schema::DS . 'XPath',
                                                        "value" => "not(//ancestor-or-self::ext:UBLExtensions)",
                                                    ],
                                                ], [
                                                    "name" => Schema::DS . 'Transform',
                                                    "attributes" => [
                                                        "Algorithm" => "http://www.w3.org/TR/1999/REC-xpath-19991116",
                                                    ],
                                                    "value" => [
                                                        "name" => Schema::DS . 'XPath',
                                                        "value" => "not(//ancestor-or-self::cac:Signature)",
                                                    ],
                                                ],
                                                [
                                                    "name" => Schema::DS . 'Transform',
                                                    "attributes" => [
                                                        "Algorithm" => "http://www.w3.org/TR/1999/REC-xpath-19991116",
                                                    ],
                                                    "value" => [
                                                        "name" => Schema::DS . 'XPath',
                                                        "value" => 'not(//ancestor-or-self::cac:AdditionalDocumentReference[cbc:ID="Q"])',
                                                    ],
                                                ],
                                                [
                                                    "name" => Schema::DS . 'Transform',
                                                    "attributes" => [
                                                        "Algorithm" => "http://www.w3.org/2006/12/xml-c14n11",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "name" => Schema::DS . 'DigestMethod',
                                            "attributes" => [
                                                "Algorithm" => "http://www.w3.org/2001/04/xmlenc#sha256",
                                            ],
                                        ],
                                        [
                                            "name" => Schema::DS . 'DigestValue',
                                            "value" => "SET_INVOICE_HASH",
                                        ],
                                    ],
                                ], [
                                    "name" => Schema::DS . 'Reference',
                                    "attributes" => [
                                        "Type" => "http://www.w3.org/2000/09/xmldsig#SignatureProperties",
                                        "URI" => "#xadesSignedProperties",
                                    ],
                                    "value" => [
                                        [
                                            "name" => Schema::DS . 'DigestMethod',
                                            "attributes" => [
                                                "Algorithm" => "http://www.w3.org/2001/04/xmlenc#sha256",
                                            ],
                                        ],
                                        [
                                            "name" => Schema::DS . 'DigestValue',
                                            "value" => "SET_SIGNED_PROPERTIES_HASH",
                                        ],
                                    ],
                                ],
                            ],

                        ],
                    ],
                    [
                        [Schema::DS . 'SignatureValue' => 'SET_DIGITAL_SIGNATURE'],
                    ],
                    [
                        "name" => Schema::DS . 'KeyInfo',
                        "value" => [
                            "name" => Schema::DS . 'X509Data',
                            "value" => [
                                [Schema::DS . 'X509Certificate' => 'SET_CERTIFICATE'],
                            ],
                        ],
                    ],
                    [
                        "name" => Schema::DS . 'Object',
                        "value" => [
                            "name" => Schema::XADES . 'QualifyingProperties',
                            "attributes" => [
                                "Target" => "signature",
                            ],
                            "value" => [
                                "name" => Schema::XADES . 'SignedProperties',
                                // "attributes" => [
                                //     "Id" => "xadesSignedProperties",
                                // ],
                                //removed fro manual entry
                                // "value" => [
                                //     "name" => Schema::XADES . 'SignedSignatureProperties',
                                //     "value" => [
                                //         [Schema::XADES . 'SigningTime' => 'SET_SIGN_TIMESTAMP'],
                                //         [
                                //             "name" => Schema::XADES . 'SigningCertificate',
                                //             "value" =>
                                //             [
                                //                 "name" => Schema::XADES . 'Cert',
                                //                 "value" => [
                                //                     [
                                //                         "name" => Schema::XADES . 'CertDigest',
                                //                         'value' => [
                                //                             [
                                //                                 "name" => Schema::DS . 'DigestMethod',
                                //                                 "attributes" => [
                                //                                     'Algorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
                                //                                 ],
                                //                             ],
                                //                             [
                                //                                 'name' => Schema::DS . 'DigestValue',
                                //                                 'value' => 'SET_CERTIFICATE_HASH',
                                //                             ],
                                //                         ],
                                //                     ],
                                //                     [
                                //                         "name" => Schema::XADES . 'IssuerSerial',
                                //                         "value" => [
                                //                             [
                                //                                 [Schema::DS . 'X509IssuerName' => 'SET_CERTIFICATE_ISSUER'],
                                //                                 [Schema::DS . 'X509SerialNumber' => 'SET_CERTIFICATE_SERIAL_NUMBER'],
                                //                             ],
                                //                         ],
                                //                     ],

                                //                 ],

                                //             ],
                                //         ],
                                //     ],
                                // ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
