<?php

namespace App\Services;

use App\Models\Company;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para manejo de certificados digitales P12/PFX
 * Implementa firma digital XML según especificaciones SIFEN
 */
class SifenCertificateService
{
    private $certificatesPath;

    public function __construct()
    {
        $this->certificatesPath = storage_path('certificates');
        
        // Crear directorio si no existe
        if (!file_exists($this->certificatesPath)) {
            mkdir($this->certificatesPath, 0755, true);
        }
    }

    /**
     * Obtiene datos del certificado de la empresa
     */
    public function getCertificateData(Company $company)
    {
        if (!$company->cert_path || !file_exists($company->cert_path)) {
            throw new Exception('Certificado no encontrado para la empresa: ' . $company->business_name);
        }

        $certPassword = $company->cert_password ?? '';
        
        // Leer el certificado P12/PFX
        $p12Data = file_get_contents($company->cert_path);
        $certs = [];
        
        if (!openssl_pkcs12_read($p12Data, $certs, $certPassword)) {
            throw new Exception('Error al leer certificado P12. Verifique la contraseña.');
        }

        // Crear archivos temporales para cert y key
        $certPath = $this->certificatesPath . '/temp_cert_' . $company->id . '.pem';
        $keyPath = $this->certificatesPath . '/temp_key_' . $company->id . '.pem';
        
        file_put_contents($certPath, $certs['cert']);
        file_put_contents($keyPath, $certs['pkey']);

        return [
            'cert_path' => $certPath,
            'key_path' => $keyPath,
            'cert_data' => $certs['cert'],
            'private_key' => $certs['pkey'],
            'ca_chain' => $certs['extracerts'] ?? []
        ];
    }

    /**
     * Firma un XML con el certificado de la empresa
     * Implementa XML Signature según especificación SIFEN
     */
    public function signXML(Company $company, $xmlContent)
    {
        try {
            $certificateData = $this->getCertificateData($company);
            
            // Cargar el XML
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xmlContent);

            // Crear la firma XML
            $signature = $this->createXMLSignature($dom, $certificateData);
            
            return $dom->saveXML();
        } catch (Exception $e) {
            Log::error('Error firmando XML: ' . $e->getMessage());
            throw new Exception('Error en firma digital: ' . $e->getMessage());
        }
    }

    /**
     * Crea la estructura XML Signature según especificación
     */
    private function createXMLSignature(\DOMDocument $dom, $certificateData)
    {
        // Obtener el elemento raíz (rDE)
        $rootElement = $dom->documentElement;
        
        // Crear el elemento Signature
        $signatureElement = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Signature');
        $rootElement->appendChild($signatureElement);

        // SignedInfo
        $signedInfo = $dom->createElement('SignedInfo');
        $signatureElement->appendChild($signedInfo);

        // CanonicalizationMethod
        $canonicalizationMethod = $dom->createElement('CanonicalizationMethod');
        $canonicalizationMethod->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
        $signedInfo->appendChild($canonicalizationMethod);

        // SignatureMethod
        $signatureMethod = $dom->createElement('SignatureMethod');
        $signatureMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');
        $signedInfo->appendChild($signatureMethod);

        // Reference
        $reference = $dom->createElement('Reference');
        $reference->setAttribute('URI', '');
        $signedInfo->appendChild($reference);

        // Transforms
        $transforms = $dom->createElement('Transforms');
        $reference->appendChild($transforms);

        $transform = $dom->createElement('Transform');
        $transform->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        $transforms->appendChild($transform);

        // DigestMethod
        $digestMethod = $dom->createElement('DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');
        $reference->appendChild($digestMethod);

        // Calcular DigestValue
        $canonicalXML = $this->canonicalizeXML($dom);
        $digestValue = base64_encode(sha1($canonicalXML, true));
        $digestValueElement = $dom->createElement('DigestValue', $digestValue);
        $reference->appendChild($digestValueElement);

        // Calcular SignatureValue
        $signedInfoCanonical = $this->canonicalizeElement($signedInfo);
        $privateKey = openssl_pkey_get_private($certificateData['private_key']);
        
        $signature = '';
        openssl_sign($signedInfoCanonical, $signature, $privateKey, 'sha1');
        $signatureValue = base64_encode($signature);
        
        $signatureValueElement = $dom->createElement('SignatureValue', $signatureValue);
        $signatureElement->appendChild($signatureValueElement);

        // KeyInfo
        $keyInfo = $dom->createElement('KeyInfo');
        $signatureElement->appendChild($keyInfo);

        // X509Data
        $x509Data = $dom->createElement('X509Data');
        $keyInfo->appendChild($x509Data);

        // Extraer certificado sin headers
        $certData = $certificateData['cert_data'];
        $certData = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r"], '', $certData);
        
        $x509Certificate = $dom->createElement('X509Certificate', trim($certData));
        $x509Data->appendChild($x509Certificate);

        return $signatureElement;
    }

    /**
     * Canonicaliza XML para firma
     */
    private function canonicalizeXML(\DOMDocument $dom)
    {
        return $dom->C14N();
    }

    /**
     * Canonicaliza un elemento específico
     */
    private function canonicalizeElement(\DOMElement $element)
    {
        return $element->C14N();
    }

    /**
     * Valida un certificado P12
     */
    public function validateCertificate($certPath, $password = '')
    {
        try {
            if (!file_exists($certPath)) {
                return ['valid' => false, 'error' => 'Archivo de certificado no encontrado'];
            }

            $p12Data = file_get_contents($certPath);
            $certs = [];
            
            if (!openssl_pkcs12_read($p12Data, $certs, $password)) {
                return ['valid' => false, 'error' => 'Error al leer certificado. Verifique la contraseña.'];
            }

            // Verificar validez del certificado
            $certInfo = openssl_x509_parse($certs['cert']);
            $now = time();
            
            if ($now < $certInfo['validFrom_time_t']) {
                return ['valid' => false, 'error' => 'Certificado aún no es válido'];
            }
            
            if ($now > $certInfo['validTo_time_t']) {
                return ['valid' => false, 'error' => 'Certificado expirado'];
            }

            return [
                'valid' => true,
                'subject' => $certInfo['subject'],
                'issuer' => $certInfo['issuer'],
                'valid_from' => date('Y-m-d H:i:s', $certInfo['validFrom_time_t']),
                'valid_to' => date('Y-m-d H:i:s', $certInfo['validTo_time_t']),
                'serial_number' => $certInfo['serialNumber']
            ];
        } catch (Exception $e) {
            return ['valid' => false, 'error' => 'Error validando certificado: ' . $e->getMessage()];
        }
    }

    /**
     * Guarda un certificado P12 para una empresa
     */
    public function storeCertificate(Company $company, $certFile, $password = '')
    {
        // Validar primero
        $validation = $this->validateCertificate($certFile->path(), $password);
        if (!$validation['valid']) {
            throw new Exception($validation['error']);
        }

        // Crear directorio específico para la empresa
        $companyDir = $this->certificatesPath . '/company_' . $company->id;
        if (!file_exists($companyDir)) {
            mkdir($companyDir, 0755, true);
        }

        // Guardar certificado
        $certPath = $companyDir . '/certificate.p12';
        $certFile->move($companyDir, 'certificate.p12');

        // Actualizar empresa
        $company->update([
            'cert_path' => $certPath,
            'cert_password' => $password,
            'cert_valid_from' => $validation['valid_from'],
            'cert_valid_to' => $validation['valid_to']
        ]);

        return $certPath;
    }

    /**
     * Limpia archivos temporales de certificados
     */
    public function cleanupTemporaryFiles(Company $company)
    {
        $certPath = $this->certificatesPath . '/temp_cert_' . $company->id . '.pem';
        $keyPath = $this->certificatesPath . '/temp_key_' . $company->id . '.pem';
        
        if (file_exists($certPath)) {
            unlink($certPath);
        }
        
        if (file_exists($keyPath)) {
            unlink($keyPath);
        }
    }
}
