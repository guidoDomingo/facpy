<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\BillingServiceFactory;
use Illuminate\Http\Request;
use Exception;

class UnifiedBillingController extends Controller
{
    /**
     * Envía un documento electrónico (Perú o Paraguay según la empresa)
     */
    public function sendDocument(Request $request)
    {
        $request->validate([
            'company_ruc' => 'required|string',
            'document_type' => 'required|string',
            'series' => 'required|string',
            'number' => 'required|string',
            'issue_date' => 'required|date',
            'currency' => 'sometimes|string',
            'customer' => 'sometimes|array',
            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            // Buscar la empresa
            $company = Company::where('user_id', auth()->id())
                        ->where('ruc', $request->company_ruc)
                        ->firstOrFail();

            $country = $company->pais ?? 'PE';

            // Crear el servicio apropiado
            $billingService = BillingServiceFactory::create($company);

            // Preparar los datos en formato universal
            $documentData = $this->prepareDocumentData($request->all(), $company);

            // Adaptar al formato específico del país
            $adaptedData = BillingServiceFactory::adaptDocumentFormat($documentData, $country);

            // Procesar según el país
            if ($country === 'PE') {
                return $this->processPeru($adaptedData, $company, $billingService);
            } elseif ($country === 'PY') {
                return $this->processParaguay($adaptedData, $company, $billingService);
            }

            return response()->json([
                'success' => false,
                'message' => 'País no soportado'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al procesar el documento'
            ], 500);
        }
    }

    /**
     * Genera solo el XML del documento
     */
    public function generateXml(Request $request)
    {
        $request->validate([
            'company_ruc' => 'required|string',
            'document_type' => 'required|string',
            'series' => 'required|string',
            'number' => 'required|string',
            'issue_date' => 'required|date',
            'items' => 'required|array',
        ]);

        try {
            $company = Company::where('user_id', auth()->id())
                        ->where('ruc', $request->company_ruc)
                        ->firstOrFail();

            $country = $company->pais ?? 'PE';
            $billingService = BillingServiceFactory::create($company);

            $documentData = $this->prepareDocumentData($request->all(), $company);
            $adaptedData = BillingServiceFactory::adaptDocumentFormat($documentData, $country);

            if ($country === 'PE') {
                $see = $billingService->getSee($company);
                $invoice = $billingService->getInvoice($adaptedData);
                $xml = $see->getXmlSigned($invoice);
                
                return response()->json([
                    'success' => true,
                    'xml' => $xml,
                    'country' => 'Peru',
                    'service' => 'SUNAT'
                ]);

            } elseif ($country === 'PY') {
                $adaptedData['cdc'] = $billingService->generateCDC($adaptedData);
                $xml = $billingService->generateXML($adaptedData);
                
                return response()->json([
                    'success' => true,
                    'xml' => $xml,
                    'cdc' => $adaptedData['cdc'],
                    'country' => 'Paraguay',
                    'service' => 'SIFEN'
                ]);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera el reporte (HTML/PDF) del documento
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'company_ruc' => 'required|string',
            'document_type' => 'required|string',
            'series' => 'required|string',
            'number' => 'required|string',
            'issue_date' => 'required|date',
            'items' => 'required|array',
            'format' => 'sometimes|in:html,pdf'
        ]);

        try {
            $company = Company::where('user_id', auth()->id())
                        ->where('ruc', $request->company_ruc)
                        ->firstOrFail();

            $country = $company->pais ?? 'PE';
            $format = $request->input('format', 'html');

            $documentData = $this->prepareDocumentData($request->all(), $company);
            $adaptedData = BillingServiceFactory::adaptDocumentFormat($documentData, $country);

            if ($country === 'PE') {
                $billingService = BillingServiceFactory::create($company);
                $see = $billingService->getSee($company);
                $invoice = $billingService->getInvoice($adaptedData);
                $html = $billingService->getHtmlReport($invoice);
                
                if ($format === 'html') {
                    return response($html)->header('Content-Type', 'text/html');
                }

            } elseif ($country === 'PY') {
                $billingService = BillingServiceFactory::create($company);
                $html = $billingService->generateHtmlReport($adaptedData, $company);
                
                if ($format === 'html') {
                    return response($html)->header('Content-Type', 'text/html');
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Reporte generado',
                'format' => $format
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene información de configuración de la empresa y país
     */
    public function getInfo(Request $request)
    {
        $request->validate([
            'company_ruc' => 'required|string'
        ]);

        try {
            $company = Company::where('user_id', auth()->id())
                        ->where('ruc', $request->company_ruc)
                        ->firstOrFail();

            $country = $company->pais ?? 'PE';
            $config = BillingServiceFactory::getCountryConfig($country);

            return response()->json([
                'success' => true,
                'company' => [
                    'ruc' => $company->ruc,
                    'razon_social' => $company->razon_social,
                    'country' => $country,
                    'environment' => $company->production ? 'production' : 'test'
                ],
                'country_config' => [
                    'name' => $config['name'],
                    'service' => $config['service'],
                    'currency' => $config['currency'],
                    'tax_name' => $config['tax_name'],
                    'tax_rates' => $config['tax_rates'],
                    'document_types' => $config['document_types']
                ],
                'units' => BillingServiceFactory::getUnits($country)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepara los datos del documento en formato universal
     */
    private function prepareDocumentData(array $requestData, Company $company)
    {
        $country = $company->pais ?? 'PE';
        
        $data = [
            'tipoDocumento' => $requestData['document_type'],
            'serie' => $requestData['series'],
            'numeroDocumento' => $requestData['number'],
            'fechaEmision' => $requestData['issue_date'],
            'emisor' => [
                'ruc' => $company->ruc,
                'razonSocial' => $company->razon_social,
                'nombreFantasia' => $company->nombre_fantasia ?? '',
                'direccion' => $company->direccion,
                'codigoDepartamento' => $company->codigo_departamento ?? '',
                'departamento' => $company->departamento ?? '',
                'codigoDistrito' => $company->codigo_distrito ?? '',
                'distrito' => $company->distrito ?? '',
                'codigoCiudad' => $company->codigo_ciudad ?? '',
                'ciudad' => $company->ciudad ?? '',
                'numeroCasa' => $company->numero_casa ?? '',
            ],
            'items' => []
        ];

        // Agregar punto de expedición para Paraguay
        if ($country === 'PY') {
            $data['puntoExpedicion'] = $company->punto_expedicion ?? '001';
        }

        // Agregar receptor si existe
        if (isset($requestData['customer'])) {
            $data['receptor'] = $requestData['customer'];
        }

        // Procesar items
        foreach ($requestData['items'] as $item) {
            $data['items'][] = [
                'descripcion' => $item['description'],
                'cantidad' => $item['quantity'],
                'precioUnitario' => $item['unit_price'],
                'codigo' => $item['code'] ?? 'ITEM001',
                'unidadMedida' => $item['unit'] ?? ($country === 'PY' ? '77' : 'NIU'),
                'exentoIva' => $item['tax_exempt'] ?? false,
                'tasaIva' => $item['tax_rate'] ?? ($country === 'PY' ? 10 : 18)
            ];
        }

        return $data;
    }

    /**
     * Procesa el documento para Perú (SUNAT)
     */
    private function processPeru(array $data, Company $company, $billingService)
    {
        $see = $billingService->getSee($company);
        $invoice = $billingService->getInvoice($data);
        $result = $see->send($invoice);

        $response = [
            'success' => $result->isSuccess(),
            'country' => 'Peru',
            'service' => 'SUNAT',
            'xml' => $see->getFactory()->getLastXml(),
        ];

        if ($result->isSuccess()) {
            $response['cdr'] = base64_encode($result->getCdrZip());
            $response['sunat_response'] = [
                'code' => (int)$result->getCdrResponse()->getCode(),
                'description' => $result->getCdrResponse()->getDescription(),
                'notes' => $result->getCdrResponse()->getNotes()
            ];
        } else {
            $response['error'] = [
                'code' => $result->getError()->getCode(),
                'message' => $result->getError()->getMessage()
            ];
        }

        return response()->json($response);
    }

    /**
     * Procesa el documento para Paraguay (SIFEN)
     */
    private function processParaguay(array $data, Company $company, $billingService)
    {
        $data['cdc'] = $billingService->generateCDC($data);
        $xml = $billingService->generateXML($data);
        $result = $billingService->sendDocument($xml, $company);

        return response()->json([
            'success' => $result['success'],
            'country' => 'Paraguay',
            'service' => 'SIFEN',
            'cdc' => $data['cdc'],
            'xml' => $xml,
            'sifen_response' => $result
        ]);
    }
}
