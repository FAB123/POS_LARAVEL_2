<?php

namespace App\GAZT\EInvoice;

use Error;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class EGenerator
{
    private $egs_info;

    public function __construct($egs_info)
    {
        $this->egs_info = $egs_info;
    }

    public function generateNewKeysAndCSR()
    {
        try {
            $new_private_key = $this->generateSecp256k1KeyPair();
            $this->egs_info['private_key'] = $new_private_key;
            $new_csr = $this->generateCSR();
            $this->egs_info['csr'] = $new_csr['csr'];
            return [
                'private_key' => $new_private_key,
                'public_key' => $new_csr['public_key'],
            ];
        } catch (\Throwable$e) {
            throw $e;
        }
    }

    private function generateSecp256k1KeyPair()
    {
        try {
            $result = $this->run_openssl(['openssl', 'ecparam', '-name', 'secp256k1', '-genkey']);
            $private_key = explode("-----END EC PARAMETERS-----", $result);
            return $private_key[1];
        } catch (\Throwable$e) {
            throw $e;
        }
    }

    private function generateCSR()
    {
        try {
            $uuid = $this->egs_info["uuid"];
            if (!$this->egs_info['private_key']) {
                throw new Error("EGS has no private key");
            }
            $private_key_file = "/tmp/$uuid.pem";
            $csr_config_file = "/tmp/$uuid.cnf";
            Storage::put($private_key_file, $this->egs_info['private_key']);
            $csr_file = $this->gencsrfile();
            Storage::put($csr_config_file, $csr_file);
            $csr = $this->run_openssl(["openssl", "req", "-new", "-sha256", "-key", Storage::path($private_key_file), "-config", Storage::path($csr_config_file)]);

            $public_key = $this->run_openssl(["openssl", "ec", "-in", Storage::path($private_key_file), "-pubout", "-conv_form", "compressed"]);

            $public_key = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\r\n", "\n"], ['', '', "\n", '',
            ], $public_key);

            return [
                'csr' => $csr,
                'public_key' => bin2hex($public_key),
            ];
        } catch (\Throwable$e) {
            throw $e;
        }
    }

    private function gencsrfile()
    {
        $data = $this->egs_info;

        $template = '# ------------------------------------------------------------------
        # Default section for "req" command options
        # ------------------------------------------------------------------
        [req]
        # Password for reading in existing private key file
        # input_password = SET_PRIVATE_KEY_PASS
        # Prompt for DN field values and CSR attributes in ASCII
        prompt = no
        utf8 = no
        # Section pointer for DN field options
        distinguished_name = my_req_dn_prompt
        # Extensions
        req_extensions = v3_req
        [ v3_req ]
        #basicConstraints=CA:FALSE
        #keyUsage = digitalSignature, keyEncipherment
        # Production or Testing Template (TSTZATCA-Code-Signing - ZATCA-Code-Signing)
        1.3.6.1.4.1.311.20.2 = ASN1:UTF8String:SET_PRODUCTION_VALUE
        subjectAltName=dirName:dir_sect
        [ dir_sect ]
        # EGS Serial number (1-SolutionName|2-ModelOrVersion|3-serialNumber)
        SN = SET_EGS_SERIAL_NUMBER
        # VAT Registration number of TaxPayer (Organization identifier [15 digits begins with 3 and ends with 3])
        UID = SET_VAT_REGISTRATION_NUMBER
        # Invoice type (TSCZ)(1 = supported, 0 not supported) (Tax, Simplified, future use, future use)
        title = SET_EGS_INVOICE_TYPE
        # Location (branch address or website)
        registeredAddress = SET_BRANCH_LOCATION
        # Industry (industry sector name)
        businessCategory = SET_BRANCH_INDUSTRY
        # ------------------------------------------------------------------
        # Section for prompting DN field values to create "subject"
        # ------------------------------------------------------------------
        [my_req_dn_prompt]
        # Common name (EGS TaxPayer PROVIDED ID [FREE TEXT])
        commonName = SET_COMMON_NAME
        # Organization Unit (Branch name)
        organizationalUnitName = SET_BRANCH_NAME
        # Organization name (Tax payer name)
        organizationName = SET_TAXPAYER_NAME
        # ISO2 country code is required with US as default
        countryName = SA';

        $template = str_replace("        ", "", $template);
        $template = str_replace("SET_PRIVATE_KEY_PASS", $data['private_key_pass'], $template);
        $template = str_replace("SET_PRODUCTION_VALUE", $data['is_production'], $template);
        $template = str_replace("SET_EGS_SERIAL_NUMBER", $data['egs_serial_number'], $template);
        $template = str_replace("SET_VAT_REGISTRATION_NUMBER", $data['egs_vat_number'], $template);
        $template = str_replace("SET_BRANCH_LOCATION", $data['egs_branch_location'], $template);
        $template = str_replace("SET_BRANCH_INDUSTRY", $data['egs_branch_industry'], $template);
        $template = str_replace("SET_COMMON_NAME", $data['egs_custom_id'], $template);
        $template = str_replace("SET_BRANCH_NAME", $data['egs_branch_name'], $template);
        $template = str_replace("SET_TAXPAYER_NAME", $data['egs_vat_name'], $template);
        $template = str_replace("SET_EGS_INVOICE_TYPE", $data['egs_invoice_type'], $template);

        return $template;
    }

    // public function signInvoice($invoice_data, $type)
    // {
    //     try {
    //         $certificate = $this->egs_info['production'] ? $this->egs_info['production_certificate'] : $this->egs_info['compliance_certificate'];
    //         if (!$certificate || !$this->egs_info['private_key']) {
    //             throw new Error("EGS is missing a certificate/private key to sign the invoice.");
    //         }
    //         $EGenerator = new EInvoice($this->egs_info);
    //         return $EGenerator->GenrateInvoice($invoice_data, $certificate, $type);
    //     } catch (\Throwable$th) {
    //         throw $th;
    //     }

    // }

    //api calls

    public function issueComplianceCertificate($otp)
    {
        try {
            if (!$this->egs_info['csr']) {
                throw new Error("EGS needs to generate a CSR first.");
            } else {
                $response = Http::withHeaders([
                    'Accept-Version' => 'V2',
                    'OTP' => $otp,
                ])->post(env('GAZT_BASE_URL') . "/compliance", [
                    'csr' => base64_encode($this->egs_info['csr']),
                ]);
                if ($response->successful()) {
                    $data = $response->json();
                    $issued_certificate = base64_decode($data['binarySecurityToken']);
                    $this->egs_info['compliance_certificate'] = $issued_certificate;
                    $this->egs_info['compliance_api_secret'] = $data['secret'];
                    return [
                        'error' => false,
                        'request_id' => $data['requestID'],
                        'issued_certificate' => $issued_certificate,
                        'info' => $response->json(),
                    ];
                } else if ($response->clientError()) {
                    return [
                        'error' => true,
                        'info' => $response->json(),
                    ];
                } else {
                    return [
                        'error' => true,
                        'info' => $response->json(),
                    ];
                }
            }
        } catch (\Throwable$th) {
            throw $th;
        }
    }

    public function checkInvoiceCompliance($signed_invoice_string, $uuid)
    {
        if (!$this->egs_info['compliance_certificate'] || !$this->egs_info['compliance_api_secret']) {
            throw new Error("EGS is missing a certificate/private key to sign the invoice.");
        } else {
            $response = Http::
                withBasicAuth(base64_encode($this->egs_info['compliance_certificate']), $this->egs_info['compliance_api_secret'])
                ->withHeaders([
                    'Accept-Version' => 'V2',
                    "Accept-Language" => "en",
                ])
                ->post(env('GAZT_BASE_URL') . "/compliance/invoices", [
                    "invoiceHash" => $signed_invoice_string['invoice_hash'],
                    "uuid" => $uuid,
                    "invoice" => base64_encode($signed_invoice_string['invoice']),
                ]);

            if ($response->successful()) {
                return [
                    'error' => false,
                    'info' => $response->json(),
                ];
            } else if ($response->clientError()) {
                return [
                    'error' => true,
                    'info' => $response->json(),
                ];
            } else {
                return [
                    'error' => true,
                    'info' => $response->json(),
                ];
            }

        }
    }

    public function issueProductionCertificate($compliance_request_id)
    {
        if (!$this->egs_info['compliance_certificate'] || !$this->egs_info['compliance_api_secret']) {
            throw new Error("EGS is missing a certificate/private key to sign the invoice.");
        } else {
            $response = Http::
                withBasicAuth(base64_encode($this->egs_info['compliance_certificate']), $this->egs_info['compliance_api_secret'])
                ->withHeaders([
                    'Accept-Version' => 'V2',
                ])
                ->post(env('GAZT_BASE_URL') . "/production/csids", [
                    "compliance_request_id" => $compliance_request_id,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $production_certificate = base64_decode($data['binarySecurityToken']);
                $production_api_secret = $data['secret'];

                return [
                    'error' => false,
                    'production_certificate' => $production_certificate,
                    'production_api_secret' => $production_api_secret,
                    'request_id' => $data['requestID'],
                    'info' => $response->json(),
                ];
            } else if ($response->clientError()) {
                return [
                    'error' => true,
                    'info' => $response->json(),
                ];
            } else {
                return [
                    'error' => true,
                    'info' => $response->json(),
                ];
            }
        }

    }

    public function report_invoice($signed_invoice_string, $invoice_hash, $uuid)
    {
        if (!$this->egs_info['cert_info']['production_certificate'] || !$this->egs_info['cert_info']['production_key']) {
            throw new Error("EGS is missing a certificate/private key to report the invoice.");
        } else {
            $response = Http::
                withBasicAuth(base64_encode($this->egs_info['cert_info']['production_certificate']), $this->egs_info['cert_info']['production_key'])
                ->withHeaders([
                    'Accept-Version' => 'V2',
                    "Accept-Language" => "en",
                    "Clearance-Status" => "0",
                ])
                ->post(env('GAZT_BASE_URL') . "/invoices/reporting/single", [
                    "invoiceHash" => $invoice_hash,
                    "uuid" => $uuid,
                    "invoice" => base64_encode($signed_invoice_string),
                ]);
            if ($response->successful()) {
                echo 'Reporting invoice...';
                return $response->json();
            } else if ($response->clientError()) {
                echo 'Error in reporting invoice.';
                return $response->json();
            } else {
                return $response->json();
            }
        }
    }

    public function clearance_invoice($signed_invoice_string, $invoice_hash, $uuid)
    {
        if (!$this->egs_info['cert_info']['production_certificate'] || !$this->egs_info['cert_info']['production_key']) {
            throw new Error("EGS is missing a certificate/private key to report the invoice.");
        } else {
            $response = Http::
                withBasicAuth(base64_encode($this->egs_info['cert_info']['production_certificate']), $this->egs_info['cert_info']['production_key'])
                ->withHeaders([
                    'Accept-Version' => 'V2',
                    "Accept-Language" => "en",
                    "Clearance-Status" => "0",
                ])
                ->post(env('GAZT_BASE_URL') . "/invoices/clearance/single", [
                    "invoiceHash" => $invoice_hash,
                    "uuid" => $uuid,
                    "invoice" => base64_encode($signed_invoice_string),
                ]);
            if ($response->successful()) {
                echo 'Clearance invoice...';
                return $response->json();
            } else if ($response->clientError()) {
                echo 'Error in reporting invoice.';
                return $response;
            } else {
                return 'Server error';
            }
        }
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
}
