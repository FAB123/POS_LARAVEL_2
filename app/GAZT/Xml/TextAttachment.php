<?php

namespace App\GAZT\Xml;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class TextAttachment implements XmlSerializable
{
    private $attachment_text;
    private $mime_type;

    /**
     * @return string
     */
    public function getMimeType(): ?string
    {
        return $this->mime_type;
    }

    /**
     * @param mixed $mime_type
     * @return TextAttachment
     */
    public function setMimeType($mime_type): TextAttachment
    {
        $this->mime_type = $mime_type;
        return $this;
    }

    /**
     * @return string
     */
    public function getAttachmentText(): ?string
    {
        return $this->attachment_text;
    }

    /**
     * @param mixed $attachment_text
     * @return TextAttachment
     */
    public function setAttachmentText($attachment_text): TextAttachment
    {
        $this->attachment_text = $attachment_text;
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
            'name' => Schema::CBC . 'EmbeddedDocumentBinaryObject',
            'value' => $this->attachment_text,
            'attributes' => [
                'mimeCode' => $this->mime_type,
            ],
        ]);
    }
}
