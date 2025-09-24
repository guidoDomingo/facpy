<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\SifenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
