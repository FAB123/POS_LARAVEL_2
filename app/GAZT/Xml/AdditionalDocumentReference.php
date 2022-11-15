<?php

namespace App\GAZT\Xml;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class AdditionalDocumentReference implements XmlSerializable
{
    private $id;
    private $documentType;
    private $attachment;
    private $uuid;

    /**
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return AdditionalDocumentReference
     */
    public function setId(string $id): AdditionalDocumentReference
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getUUID(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @return AdditionalDocumentReference
     */
    public function setUUID(string $uuid): AdditionalDocumentReference
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @return string
     */
    public function getDocumentType(): ?string
    {
        return $this->documentType;
    }

    /**
     * @param string $documentType
     * @return AdditionalDocumentReference
     */
    public function setDocumentType(string $documentType): AdditionalDocumentReference
    {
        $this->documentType = $documentType;
        return $this;
    }

    /**
     * @return TextAttachment
     */
    public function getAttachment(): ?TextAttachment
    {
        return $this->attachment;
    }

    /**
     * @param TextAttachment $attachment
     * @return AdditionalDocumentReference
     */
    public function setAttachment(TextAttachment $attachment): AdditionalDocumentReference
    {
        $this->attachment = $attachment;
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
        $writer->write([Schema::CBC . 'ID' => $this->id]);
        if ($this->documentType !== null) {
            $writer->write([
                Schema::CAC . 'DocumentType' => $this->documentType,
            ]);
        }
        if ($this->uuid !== null) {
            $writer->write([
                Schema::CBC . 'UUID' => $this->uuid,
            ]);
        }
        if ($this->attachment !== null) {
            $writer->write([Schema::CAC . 'Attachment' => $this->attachment]);
        }
    }
}
