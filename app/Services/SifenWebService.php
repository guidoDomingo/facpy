<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ElectronicDocument;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para integraciones con Web Services SIFEN
 * Implementa todos los servicios web requeridos según especificación
 */
class SifenWebService
{
    private $baseUrl;
    private $testUrl = 'https://sifen-test.set.gov.py/de/ws/';
    private $productionUrl = 'https://sifen.set.gov.py/de/ws/';
    private $certificateService;

    public function __construct(SifenCertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Configura URLs según el ambiente
     */
    public function setEnvironment($isProduction = false)
    {
        $this->baseUrl = $isProduction ? $this->productionUrl : $this->testUrl;
    }

    /**
     * 1. siRecepDE - Recepción individual de DE
     */
    public function siRecepDE(Company $company, $xmlSigned)
    {
        try {
            $this->setEnvironment($company->production);
            
            $envelope = $this->buildSoapEnvelope('siRecepDE', [
                'dId' => uniqid(),
                'xDE' => base64_encode($xmlSigned)
            ]);

            $response = $this->sendSoapRequest('sync/recepcion-de', $envelope, $company);
            
            return $this->parseSifenResponse($response);
        } catch (Exception $e) {
            Log::error('Error siRecepDE: ' . $e->getMessage());
            throw new Exception('Error en recepción individual DE: ' . $e->getMessage());
        }
    }

    /**
     * 2. siRecepLoteDE - Recepción de lote de DE (hasta 15)
     */
    public function siRecepLoteDE(Company $company, array $xmlDocuments)
    {
        try {
            if (count($xmlDocuments) > 15) {
                throw new Exception('El lote no puede contener más de 15 documentos');
            }

            $this->setEnvironment($company->production);
            
            $loteItems = [];
            foreach ($xmlDocuments as $index => $xml) {
                $loteItems[] = [
                    'dId' => uniqid() . '_' . $index,
                    'xDE' => base64_encode($xml)
                ];
            }

            $envelope = $this->buildSoapEnvelope('siRecepLoteDE', [
                'dId' => 'LOTE_' . uniqid(),
                'items' => $loteItems
            ]);

            $response = $this->sendSoapRequest('sync/recepcion-lote-de', $envelope, $company);
            
            return $this->parseSifenResponse($response);
        } catch (Exception $e) {
            Log::error('Error siRecepLoteDE: ' . $e->getMessage());
            throw new Exception('Error en recepción lote DE: ' . $e->getMessage());
        }
    }

    /**
     * 3. siResultLoteDE - Consulta resultado de lote
     */
    public function siResultLoteDE(Company $company, $loteId)
    {
        try {
            $this->setEnvironment($company->production);
            
            $envelope = $this->buildSoapEnvelope('siResultLoteDE', [
                'dProtConsLote' => $loteId
            ]);

            $response = $this->sendSoapRequest('consultas/resultado-lote', $envelope, $company);
            
            return $this->parseSifenResponse($response);
        } catch (Exception $e) {
            Log::error('Error siResultLoteDE: ' . $e->getMessage());
            throw new Exception('Error en consulta resultado lote: ' . $e->getMessage());
        }
    }

    /**
     * 4. siConsDE - Consulta de documentos electrónicos
     */
    public function siConsDE(Company $company, $cdc)
    {
        try {
            $this->setEnvironment($company->production);
            
            $envelope = $this->buildSoapEnvelope('siConsDE', [
                'dCDC' => $cdc
            ]);

            $response = $this->sendSoapRequest('consultas/consulta-de', $envelope, $company);
            
            return $this->parseSifenResponse($response);
        } catch (Exception $e) {
            Log::error('Error siConsDE: ' . $e->getMessage());
            throw new Exception('Error en consulta DE: ' . $e->getMessage());
        }
    }

    /**
     * 5. siConsRUC - Consulta y validación de RUC
     */
    public function siConsRUC(Company $company, $ruc)
    {
        try {
            $this->setEnvironment($company->production);
            
            $envelope = $this->buildSoapEnvelope('siConsRUC', [
                'dRUC' => $ruc
            ]);

            $response = $this->sendSoapRequest('consultas/consulta-ruc', $envelope, $company);
            
            return $this->parseSifenResponse($response);
        } catch (Exception $e) {
            Log::error('Error siConsRUC: ' . $e->getMessage());
            throw new Exception('Error en consulta RUC: ' . $e->getMessage());
        }
    }

    /**
     * 6. siRecepEvento - Recepción de eventos
     */
    public function siRecepEvento(Company $company, $xmlEventoSigned)
    {
        try {
            $this->setEnvironment($company->production);
            
            $envelope = $this->buildSoapEnvelope('siRecepEvento', [
                'dId' => uniqid(),
                'xEvento' => base64_encode($xmlEventoSigned)
            ]);

            $response = $this->sendSoapRequest('eventos/recepcion-evento', $envelope, $company);
            
            return $this->parseSifenResponse($response);
        } catch (Exception $e) {
            Log::error('Error siRecepEvento: ' . $e->getMessage());
            throw new Exception('Error en recepción evento: ' . $e->getMessage());
        }
    }

    /**
     * Construye el envelope SOAP para las peticiones
     */
    private function buildSoapEnvelope($operation, $data)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:rec="http://ekuatia.set.gov.py/sifen/xsd">';
        $xml .= '<soap:Header/>';
        $xml .= '<soap:Body>';
        $xml .= '<rec:' . $operation . '>';
        
        switch ($operation) {
            case 'siRecepDE':
                $xml .= '<rec:dId>' . $data['dId'] . '</rec:dId>';
                $xml .= '<rec:xDE>' . $data['xDE'] . '</rec:xDE>';
                break;
                
            case 'siRecepLoteDE':
                $xml .= '<rec:dId>' . $data['dId'] . '</rec:dId>';
                foreach ($data['items'] as $item) {
                    $xml .= '<rec:item>';
                    $xml .= '<rec:dId>' . $item['dId'] . '</rec:dId>';
                    $xml .= '<rec:xDE>' . $item['xDE'] . '</rec:xDE>';
                    $xml .= '</rec:item>';
                }
                break;
                
            case 'siResultLoteDE':
                $xml .= '<rec:dProtConsLote>' . $data['dProtConsLote'] . '</rec:dProtConsLote>';
                break;
                
            case 'siConsDE':
                $xml .= '<rec:dCDC>' . $data['dCDC'] . '</rec:dCDC>';
                break;
                
            case 'siConsRUC':
                $xml .= '<rec:dRUC>' . $data['dRUC'] . '</rec:dRUC>';
                break;
                
            case 'siRecepEvento':
                $xml .= '<rec:dId>' . $data['dId'] . '</rec:dId>';
                $xml .= '<rec:xEvento>' . $data['xEvento'] . '</rec:xEvento>';
                break;
        }
        
        $xml .= '</rec:' . $operation . '>';
        $xml .= '</soap:Body>';
        $xml .= '</soap:Envelope>';
        
        return $xml;
    }

    /**
     * Envía petición SOAP con certificado
     */
    private function sendSoapRequest($endpoint, $envelope, Company $company)
    {
        $certificateData = $this->certificateService->getCertificateData($company);
        
        $response = Http::withOptions([
            'verify' => false, // Para testing, en producción usar certificado raíz
            'cert' => $certificateData['cert_path'],
            'ssl_key' => $certificateData['key_path'],
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => '',
            ],
            'timeout' => 30,
        ])->send('POST', $this->baseUrl . $endpoint, [
            'body' => $envelope
        ]);

        if (!$response->successful()) {
            throw new Exception('Error HTTP: ' . $response->status() . ' - ' . $response->body());
        }

        return $response->body();
    }

    /**
     * Procesa la respuesta de SIFEN
     */
    private function parseSifenResponse($responseXml)
    {
        try {
            $xml = simplexml_load_string($responseXml);
            
            // Extraer información básica de la respuesta
            $response = [
                'success' => false,
                'protocol' => null,
                'status' => null,
                'message' => null,
                'errors' => [],
                'raw_response' => $responseXml
            ];
            
            // Buscar códigos de respuesta en la estructura XML
            if (isset($xml->xpath('//dCodRes')[0])) {
                $code = (string)$xml->xpath('//dCodRes')[0];
                $response['status'] = $code;
                $response['success'] = in_array($code, ['0260', '0261', '0262']); // Códigos de éxito
            }
            
            if (isset($xml->xpath('//dMsgRes')[0])) {
                $response['message'] = (string)$xml->xpath('//dMsgRes')[0];
            }
            
            if (isset($xml->xpath('//dProtDTE')[0])) {
                $response['protocol'] = (string)$xml->xpath('//dProtDTE')[0];
            }
            
            // Buscar errores
            $errors = $xml->xpath('//gCamErr');
            foreach ($errors as $error) {
                $response['errors'][] = [
                    'code' => (string)$error->dCodErr,
                    'message' => (string)$error->dMsgErr,
                    'field' => (string)$error->dCamErr ?? null
                ];
            }
            
            return $response;
        } catch (Exception $e) {
            throw new Exception('Error procesando respuesta SIFEN: ' . $e->getMessage());
        }
    }

    /**
     * Valida que el RUC existe y está activo
     */
    public function validateRUC(Company $company, $ruc)
    {
        $response = $this->siConsRUC($company, $ruc);
        
        return [
            'valid' => $response['success'],
            'active' => $response['success'] && isset($response['status']),
            'details' => $response
        ];
    }

    /**
     * Verifica el estado de un documento por CDC
     */
    public function checkDocumentStatus(Company $company, $cdc)
    {
        $response = $this->siConsDE($company, $cdc);
        
        return [
            'exists' => $response['success'],
            'status' => $response['status'] ?? 'unknown',
            'details' => $response
        ];
    }
}
