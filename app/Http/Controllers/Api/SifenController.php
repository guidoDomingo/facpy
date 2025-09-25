<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\SifenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SifenController extends Controller
{
    protected $sifenService;

    public function __construct(SifenService $sifenService)
    {
        $this->sifenService = $sifenService;
    }

    /**
     * Método de prueba simple para verificar que el controlador funciona
     */
    public function testRoute()
    {
        return response()->json([
            'success' => true,
            'message' => 'SifenController está funcionando correctamente',
            'controller' => 'SifenController',
            'service' => class_exists('App\Services\SifenService') ? 'SifenService disponible' : 'SifenService NO disponible'
        ]);
    }

    /**
     * Genera XML para pruebas SIN autenticación
     */
    public function generateXmlTest(Request $request)
    {
        try {
            // Usar empresa por defecto para testing
            $company = Company::where('ruc', '80123456-7')->first();
            
            if (!$company) {
                // Crear empresa automáticamente si no existe
                try {
                    $company = Company::create([
                        'user_id' => 1,
                        'ruc' => '80123456-7',
                        'razon_social' => 'Empresa Demo Paraguay SRL',
                        'direccion' => 'Av. Mariscal López 1234',
                        'departamento' => 'CAPITAL',
                        'distrito' => 'ASUNCIÓN',
                        'ciudad' => 'ASUNCIÓN',
                        'codigo_departamento' => '01',
                        'punto_expedicion' => '001',
                        'estado' => 'activo',
                        'sol_user' => 'DEMO_USER_PY',
                        'sol_pass' => 'demo_password_123',
                        'cert_path' => 'certs/demo_cert.pem',
                        'production' => false,
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo crear empresa automáticamente: ' . $e->getMessage(),
                        'suggestion' => 'Usar /api/test/create-company con user_id correcto'
                    ], 400);
                }
            }

            // Datos de prueba por defecto
            $data = [
                'tipoDocumento' => '01',
                'serie' => '001',
                'numero' => '000000999',
                'fechaEmision' => date('Y-m-d'),
                'receptor' => [
                    'ruc' => '80012345-1',
                    'razonSocial' => 'Cliente Test Paraguay S.A.',
                    'direccion' => 'Av. Test 123, Asunción'
                ],
                'items' => [
                    [
                        'descripcion' => 'Producto Test Paraguay',
                        'cantidad' => 1,
                        'precioUnitario' => 100000,
                        'tipoIva' => '10'
                    ]
                ]
            ];

            // Generar CDC
            $cdc = $this->sifenService->generateCDC($company, $data);

            // Generar XML
            $xmlContent = $this->sifenService->generateXML($company, $data, $cdc);

            return response()->json([
                'success' => true,
                'message' => 'XML generado exitosamente SIN autenticación - Paraguay SIFEN',
                'cdc' => $cdc,
                'xml_preview' => substr($xmlContent, 0, 500) . '...',
                'xml_length' => strlen($xmlContent),
                'company' => [
                    'id' => $company->id,
                    'ruc' => $company->ruc,
                    'razon_social' => $company->razon_social
                ],
                'note' => 'Esta es una ruta de PRUEBA sin autenticación'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al generar XML de prueba',
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Simula completamente el envío de una factura al SIFEN (sin autenticación)
     */
    public function sendInvoiceSimulation(Request $request)
    {
        try {
            // Usar empresa por defecto para testing
            $company = Company::where('ruc', '80123456-7')->first();
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró empresa de prueba. Ejecutar: POST /api/test/create-company con user_id válido'
                ], 400);
            }

            // Datos de prueba por defecto o del request
            $data = $request->all() ?: [
                'tipoDocumento' => '01',
                'serie' => '001',
                'numero' => str_pad(rand(1, 999999), 9, '0', STR_PAD_LEFT),
                'fechaEmision' => date('Y-m-d'),
                'receptor' => [
                    'ruc' => '80012345-1',
                    'razonSocial' => 'Cliente Paraguay S.A.',
                    'direccion' => 'Av. Mariscal López 1234, Asunción'
                ],
                'items' => [
                    [
                        'descripcion' => 'Producto Paraguay Premium',
                        'cantidad' => 3,
                        'precioUnitario' => 100000,
                        'tipoIva' => '10'
                    ]
                ]
            ];

            // Generar CDC
            $cdc = $this->sifenService->generateCDC($company, $data);

            // Generar XML
            $xmlContent = $this->sifenService->generateXML($company, $data, $cdc);

            // Simular envío (siempre exitoso en modo de prueba)
            $result = $this->sifenService->sendDocument($xmlContent, $company);

            return response()->json([
                'success' => true,
                'cdc' => $cdc,
                'xml' => $xmlContent,
                'sifen_response' => $result,
                'message' => 'Documento enviado exitosamente (SIMULADO para testing)',
                'note' => 'Esta es una simulación completa. Para usar en produción necesitas certificados digitales válidos.',
                'company_used' => [
                    'id' => $company->id,
                    'ruc' => $company->ruc,
                    'razon_social' => $company->razon_social
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error en simulación de envío',
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Prueba conexión REAL con SIFEN Testing (requiere certificado)
     */
    public function testRealSifenConnection(Request $request)
    {
        try {
            // Usar empresa por defecto para testing
            $company = Company::where('ruc', '80123456-7')->first();
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró empresa de prueba'
                ], 400);
            }

            // Verificar configuración de certificado
            $certPath = $company->cert_path ? storage_path('app/' . $company->cert_path) : env('SIFEN_CERT_PATH');
            
            if (!$certPath || !file_exists($certPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificado no encontrado. Ubicación esperada: ' . ($certPath ?: 'No configurado'),
                    'instructions' => [
                        '1. Obtén un certificado digital de prueba de SET Paraguay',
                        '2. Guárdalo en storage/certificates/sifen_test.p12',
                        '3. Configura SIFEN_CERT_PATH y SIFEN_CERT_PASSWORD en .env',
                        '4. O configura cert_path y cert_password en la empresa'
                    ]
                ], 400);
            }

            // Datos mínimos para prueba
            $data = [
                'tipoDocumento' => '01',
                'serie' => '001',
                'numero' => 'TEST' . time(),
                'fechaEmision' => date('Y-m-d'),
                'receptor' => [
                    'ruc' => '80012345-1',
                    'razonSocial' => 'Cliente Prueba SIFEN',
                    'direccion' => 'Av. Test SIFEN, Asunción'
                ],
                'items' => [
                    [
                        'descripcion' => 'Producto Prueba SIFEN Real',
                        'cantidad' => 1,
                        'precioUnitario' => 10000,
                        'tipoIva' => '10'
                    ]
                ]
            ];

            // Generar CDC y XML
            $cdc = $this->sifenService->generateCDC($company, $data);
            $xmlContent = $this->sifenService->generateXML($company, $data, $cdc);

            // Enviar al SIFEN REAL (forzar conexión real)
            $originalForce = env('SIFEN_FORCE_REAL_CONNECTION');
            config(['sifen.force_real' => true]);
            
            $result = $this->sifenService->sendDocument($xmlContent, $company);

            return response()->json([
                'success' => $result['success'],
                'cdc' => $cdc,
                'sifen_real_response' => $result,
                'message' => $result['success'] 
                    ? 'Conexión REAL con SIFEN Testing exitosa' 
                    : 'Error en conexión REAL con SIFEN',
                'endpoint_used' => env('SIFEN_TEST_URL'),
                'certificate_used' => basename($certPath),
                'environment' => 'SIFEN TESTING REAL - NO SIMULADO'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al conectar con SIFEN real',
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Prueba conexión con SIFEN usando certificado raíz Paraguay
     */
    public function testWithParaguayRootCert()
    {
        try {
            $company = Company::where('ruc', '80123456-7')->first();
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró empresa de prueba'
                ], 400);
            }

            // Verificar certificado raíz
            $rootCertPath = storage_path('certificates/ac_raiz_py_sha256.crt');
            
            if (!file_exists($rootCertPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificado raíz de Paraguay no encontrado'
                ], 400);
            }

            // Datos mínimos para prueba
            $data = [
                'tipoDocumento' => '01',
                'serie' => '001',
                'numero' => 'ROOT' . time(),
                'fechaEmision' => date('Y-m-d'),
                'receptor' => [
                    'ruc' => '80012345-1',
                    'razonSocial' => 'Cliente Prueba SIFEN Root',
                    'direccion' => 'Av. Test SIFEN Root, Asunción'
                ],
                'items' => [
                    [
                        'descripcion' => 'Producto Prueba con Cert Root Paraguay',
                        'cantidad' => 1,
                        'precioUnitario' => 15000,
                        'tipoIva' => '10'
                    ]
                ]
            ];

            // Generar CDC y XML
            $cdc = $this->sifenService->generateCDC($company, $data);
            $xmlContent = $this->sifenService->generateXML($company, $data, $cdc);

            // Intentar conexión con certificado raíz para validación SSL
            $endpoint = env('SIFEN_TEST_URL', 'https://sifen-test.set.gov.py/de/ws/sync/recepcion-de');
            
            try {
                $response = Http::withOptions([
                    'verify' => $rootCertPath, // Usar certificado raíz para validación SSL
                    'timeout' => 30,
                    'connect_timeout' => 10
                ])->withHeaders([
                    'Content-Type' => 'application/xml; charset=UTF-8',
                    'SOAPAction' => '"recepcionDE"',
                    'User-Agent' => 'Paraguay-SIFEN-Test/1.0'
                ])->post($endpoint, $xmlContent);

                return response()->json([
                    'success' => true,
                    'test_type' => 'Conexión con Certificado Raíz Paraguay',
                    'cdc' => $cdc,
                    'endpoint' => $endpoint,
                    'response_status' => $response->status(),
                    'response_headers' => $response->headers(),
                    'response_body' => $response->body(),
                    'certificate_used' => 'ac_raiz_py_sha256.crt (Certificado Raíz Paraguay)',
                    'xml_sent_length' => strlen($xmlContent),
                    'message' => 'Prueba de conexión con certificado raíz Paraguay completada'
                ]);

            } catch (\Exception $httpException) {
                // Si falla con certificado raíz, intentar sin validación SSL
                $responseNoSSL = Http::withOptions([
                    'verify' => false,
                    'timeout' => 30
                ])->withHeaders([
                    'Content-Type' => 'application/xml; charset=UTF-8',
                    'SOAPAction' => '"recepcionDE"',
                    'User-Agent' => 'Paraguay-SIFEN-Test/1.0'
                ])->post($endpoint, $xmlContent);

                return response()->json([
                    'success' => true,
                    'test_type' => 'Conexión sin validación SSL (Fallback)',
                    'cdc' => $cdc,
                    'endpoint' => $endpoint,
                    'ssl_error' => $httpException->getMessage(),
                    'response_status' => $responseNoSSL->status(),
                    'response_body' => $responseNoSSL->body(),
                    'certificate_note' => 'Certificado raíz presente pero conexión SSL falló',
                    'message' => 'Conexión exitosa sin validación SSL - Respuesta real de SIFEN'
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error en prueba con certificado raíz Paraguay',
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Envía una factura electrónica al SIFEN
     */
    public function sendInvoice(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'tipoDocumento' => 'required|string',
            'serie' => 'required|string',
            'numero' => 'required|string',
            'fechaEmision' => 'required|date',
            'receptor' => 'required|array',
            'receptor.ruc' => 'required|string',
            'receptor.razonSocial' => 'required|string',
            'receptor.direccion' => 'required|string',
            'items' => 'required|array',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric|min:1',
            'items.*.precioUnitario' => 'required|numeric|min:0',
            'items.*.tipoIva' => 'required|string|in:0,5,10',
        ]);

        $data = $request->all();

        // Buscar la empresa del usuario autenticado
        $company = Company::where('user_id', auth()->id())
                    ->where('id', $data['company_id'])
                    ->firstOrFail();

        try {
            // Generar CDC (Código de Control del Documento)
            $cdc = $this->sifenService->generateCDC($company, $data);

            // Generar XML del documento electrónico
            $xmlContent = $this->sifenService->generateXML($company, $data, $cdc);

            // Enviar al SIFEN
            $result = $this->sifenService->sendDocument($xmlContent, $company);

            return response()->json([
                'success' => $result['success'],
                'cdc' => $cdc,
                'xml' => $xmlContent,
                'sifen_response' => $result,
                'message' => $result['success'] ? 'Documento enviado exitosamente' : 'Error al enviar documento'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Genera solo el XML sin enviar al SIFEN
     */
    public function generateXml(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'tipoDocumento' => 'required|string',
            'serie' => 'required|string',
            'numero' => 'required|string',
            'fechaEmision' => 'required|date',
            'receptor' => 'required|array',
            'receptor.ruc' => 'required|string',
            'receptor.razonSocial' => 'required|string',
            'receptor.direccion' => 'required|string',
            'items' => 'required|array',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric|min:1',
            'items.*.precioUnitario' => 'required|numeric|min:0',
            'items.*.tipoIva' => 'required|string|in:0,5,10',
        ]);

        $data = $request->all();

        // Buscar la empresa del usuario autenticado
        $company = Company::where('user_id', auth()->id())
                    ->where('id', $data['company_id'])
                    ->firstOrFail();

        try {
            // Generar CDC
            $cdc = $this->sifenService->generateCDC($company, $data);

            // Generar XML
            $xmlContent = $this->sifenService->generateXML($company, $data, $cdc);

            return response()->json([
                'success' => true,
                'cdc' => $cdc,
                'xml' => $xmlContent,
                'message' => 'XML generado exitosamente para Paraguay SIFEN',
                'company' => [
                    'ruc' => $company->ruc,
                    'razon_social' => $company->razon_social
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al generar XML Paraguay'
            ], 500);
        }
    }

    /**
     * Genera el reporte HTML/PDF del documento
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'tipoDocumento' => 'required|string',
            'serie' => 'required|string',
            'numero' => 'required|string',
            'fechaEmision' => 'required|date',
            'receptor' => 'required|array',
            'receptor.ruc' => 'required|string',
            'receptor.razonSocial' => 'required|string',
            'items' => 'required|array',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric|min:1',
            'items.*.precioUnitario' => 'required|numeric|min:0',
            'format' => 'sometimes|in:html,pdf'
        ]);

        $data = $request->all();
        $format = $request->input('format', 'html');

        // Buscar la empresa del usuario autenticado
        $company = Company::where('user_id', Auth::id())
                    ->where('id', $data['company_id'])
                    ->firstOrFail();

        try {
            // Generar CDC si no existe
            if (!isset($data['cdc'])) {
                $cdc = $this->sifenService->generateCDC($company, $data);
                $data['cdc'] = $cdc;
            }

            // Generar reporte HTML
            $htmlContent = $this->sifenService->generateHtmlReport($company, $data);

            if ($format === 'html') {
                return response($htmlContent)
                    ->header('Content-Type', 'text/html');
            } else {
                // Para PDF necesitarías una librería como dompdf o wkhtmltopdf
                // Por ahora retornamos el HTML
                return response()->json([
                    'success' => true,
                    'html' => $htmlContent,
                    'message' => 'Reporte generado exitosamente para Paraguay SIFEN (formato PDF en desarrollo)',
                    'company' => [
                        'ruc' => $company->ruc,
                        'razon_social' => $company->razon_social
                    ]
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al generar reporte Paraguay'
            ], 500);
        }
    }

    /**
     * Consulta el estado de un documento en SIFEN
     */
    public function queryStatus(Request $request)
    {
        $request->validate([
            'cdc' => 'required|string|size:44',
            'company_id' => 'required|integer|exists:companies,id'
        ]);

        $cdc = $request->input('cdc');
        $companyId = $request->input('company_id');

        // Buscar la empresa del usuario autenticado
        $company = Company::where('user_id', Auth::id())
                    ->where('id', $companyId)
                    ->firstOrFail();

        try {
            $result = $this->sifenService->queryDocumentStatus($cdc, $company);

            return response()->json([
                'success' => $result['success'],
                'cdc' => $cdc,
                'status' => $result['response'] ?? null,
                'message' => $result['success'] ? 'Consulta SIFEN Paraguay exitosa' : 'Error en la consulta SIFEN',
                'company' => [
                    'ruc' => $company->ruc,
                    'razon_social' => $company->razon_social
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al consultar estado en SIFEN Paraguay'
            ], 500);
        }
    }

    /**
     * Obtiene las configuraciones necesarias para la integración SIFEN Paraguay
     */
    public function getConfig(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id'
        ]);

        $companyId = $request->input('company_id');

        // Buscar la empresa del usuario autenticado
        $company = Company::where('user_id', Auth::id())
                    ->where('id', $companyId)
                    ->firstOrFail();

        try {
            $config = $this->sifenService->configureSifen($company);

            return response()->json([
                'success' => true,
                'config' => [
                    'ruc' => $company->ruc,
                    'razon_social' => $company->razon_social,
                    'environment' => $config['environment'] ?? 'test',
                    'has_certificate' => !empty($config['certificate_path'] ?? null),
                    'sifen_endpoints' => $config['endpoints'] ?? null
                ],
                'message' => 'Configuración SIFEN Paraguay obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al obtener configuración SIFEN Paraguay'
            ], 500);
        }
    }
}
