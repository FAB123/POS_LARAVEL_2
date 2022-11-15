<?php

namespace App\GAZT\Xml;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Party implements XmlSerializable
{
    private $name;
    private $partyIdentificationId;
    private $postalAddress;
    private $physicalLocation;
    private $contact;
    private $partyTaxScheme;
    private $legalEntity;
    private $partyIdentificationType;

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Party
     */
    public function setName(?string $name): Party
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getPartyIdentificationType(): ?string
    {
        return $this->partyIdentificationType;
    }

    /**
     * @param string $partyIdentificationType
     * @return Party
     */
    public function setPartyIdentificationType(?string $partyIdentificationType): Party
    {
        $this->partyIdentificationType = $partyIdentificationType;
        return $this;
    }

    /**
     * @return string
     */
    public function getPartyIdentificationId(): ?string
    {
        return $this->partyIdentificationId;
    }

    /**
     * @param string $partyIdentificationId
     * @return Party
     */
    public function setPartyIdentificationId(?string $partyIdentificationId): Party
    {
        $this->partyIdentificationId = $partyIdentificationId;
        return $this;
    }

    /**
     * @return Address
     */
    public function getPostalAddress(): ?Address
    {
        return $this->postalAddress;
    }

    /**
     * @param Address $postalAddress
     * @return Party
     */
    public function setPostalAddress(?Address $postalAddress): Party
    {
        $this->postalAddress = $postalAddress;
        return $this;
    }

    /**
     * @return LegalEntity
     */
    public function getLegalEntity(): ?LegalEntity
    {
        return $this->legalEntity;
    }

    /**
     * @param LegalEntity $legalEntity
     * @return Party
     */
    public function setLegalEntity(?LegalEntity $legalEntity): Party
    {
        $this->legalEntity = $legalEntity;
        return $this;
    }

    /**
     * @return Address
     */
    public function getPhysicalLocation(): ?Address
    {
        return $this->physicalLocation;
    }

    /**
     * @param Address $physicalLocation
     * @return Party
     */
    public function setPhysicalLocation(?Address $physicalLocation): Party
    {
        $this->physicalLocation = $physicalLocation;
        return $this;
    }

    /**
     * @return PartyTaxScheme
     */
    public function getPartyTaxScheme(): ?PartyTaxScheme
    {
        return $this->partyTaxScheme;
    }

    /**
     * @param PartyTaxScheme $partyTaxScheme
     * @return Party
     */
    public function setPartyTaxScheme(PartyTaxScheme $partyTaxScheme)
    {
        $this->partyTaxScheme = $partyTaxScheme;
        return $this;
    }

    /**
     * @return Contact
     */
    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    /**
     * @param Contact $contact
     * @return Party
     */
    public function setContact(?Contact $contact): Party
    {
        $this->contact = $contact;
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
        if ($this->partyIdentificationId !== null) {
            $writer->write([
                Schema::CAC . 'PartyIdentification' => [
                    [
                        "name" => Schema::CBC . 'ID',
                        "value" => $this->partyIdentificationId,
                        "attributes" => [
                            "schemeID" => $this->partyIdentificationType,
                        ],
                    ],
                ],
            ]);
        }

        if ($this->name !== null) {
            $writer->write([
                Schema::CAC . 'PartyName' => [
                    Schema::CBC . 'Name' => $this->name,
                ],
            ]);
        }
        if ($this->postalAddress !== null) {
            $writer->write([

                Schema::CAC . 'PostalAddress' => $this->postalAddress,
            ]);
        }

        if ($this->physicalLocation !== null) {
            $writer->write([
                Schema::CAC . 'PhysicalLocation' => [Schema::CAC . 'Address' => $this->physicalLocation],
            ]);
        }

        if ($this->partyTaxScheme !== null) {
            $writer->write([
                Schema::CAC . 'PartyTaxScheme' => $this->partyTaxScheme,
            ]);
        }

        if ($this->legalEntity !== null) {
            $writer->write([
                Schema::CAC . 'PartyLegalEntity' => $this->legalEntity,
            ]);
        }

        if ($this->contact !== null) {
            $writer->write([
                Schema::CAC . 'Contact' => $this->contact,
            ]);
        }
    }
}
