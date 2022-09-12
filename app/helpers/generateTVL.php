<?php

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

    public function generate($company, $vat_no, $trans_time, $total, $vat_amount)
    {
        $generatedString = ([
            $this->toString(1, $this->getLength($company), $company),
            $this->toString(2, $this->getLength($vat_no), $vat_no),
            $this->toString(3, $this->getLength($trans_time), $trans_time),
            $this->toString(4, $this->getLength($total), $total),
            $this->toString(5, $this->getLength($vat_amount), $vat_amount),
        ]);
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

    protected function toHexValue($value)
    {
        return unpack("H*", $value)[1];
    }

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
