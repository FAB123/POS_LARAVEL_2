<?php
namespace App\helpers;

class GenerateTVL
{
    // protected $company;
    // protected $vatno;
    // protected $time;
    // protected $total;
    // protected $vat;

    public function changeDateFormate($date, $date_format)
    {
        return \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format($date_format);
    }

    // var_dump(bin2hex('Ahmed Mohamed AL Ahmady'));

    public function generate($tags)
    {
        $generatedString = [];
        $tag_count = 1;
        foreach ($tags as $tag) {
            $generatedString[] = $this->toString($tag_count, $this->getLength($tag), $tag);
            // $generatedString[] = pack("H*", sprintf("%02X", (string) "$tag")) .
            // pack("H*", sprintf("%02X", strlen((string) "$tag"))) .
            // (string) "$tag";
            $tag_count++;
        }
        return ($this->toBase64($generatedString));
    }

    public function toString($tag, $length, $value)
    {
        return $this->toHex($tag) . $this->toHex($length) . ($value);
    }

    public function getLength($value)
    {
        return strlen($value);
    }

    protected function toHex($value)
    {
        return pack("H*", sprintf("%02X", $value));
    }

    // protected function toHexValue($value)
    // {
    //     return unpack("H*", $value)[1];
    // }

    public function toTLV($generatedString): string
    {
        return implode('', array_map(function ($tag) {
            return (string) $tag;
        }, $generatedString));
    }

    public function toBase64($generatedString): string
    {
        return base64_encode($this->toTLV($generatedString));
    }

}
