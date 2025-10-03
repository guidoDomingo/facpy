<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ElectronicDocument;
use App\Models\DocumentEvent;
use App\Services\SifenWebService;
use App\Services\SifenEventService;
use App\Services\SifenNCEService;
use App\Services\SifenKudeService;
use App\Services\SifenCertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class SifenAdvancedController extends Controller
{
    private $webService;
    private $eventService;
    private $nceService;
    private $kudeService;
    private $certificateService;

    public function __construct(
        SifenWebService $webService,
        SifenEventService $eventService,
        SifenNCEService $nceService,
        SifenKudeService $kudeService,
        SifenCertificateService $certificateService
    ) {
        $this->webService = $webService;
        $this->eventService = $eventService;
        $this->nceService = $nceService;
        $this->kudeService = $kudeService;
        $this->certificateService = $certificateService;
    }

    /**
     * Cancela un documento electrónico
     */
    public function cancelDocument(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cdc' => 'required|string|size:44',
                'reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $document = ElectronicDocument::findByCDC($request->cdc);
            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado'
                ], 404);
            }

            // Verificar permisos
            if ($document->company->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $result = $this->eventService->cancelDocument($document, $request->reason);

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Inutiliza un rango de numeración
     */
    public function inutilizeRange(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'serie' => 'required|string|max:10',
                'range_start' => 'required|string|max:20',
                'range_end' => 'required|string|max:20',
                'reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $company = Company::find($request->company_id);
            
            // Verificar permisos
            if ($company->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $result = $this->eventService->inutilizeRange(
                $company,
                $request->serie,
                $request->range_start,
                $request->range_end,
                $request->reason
            );

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crea una Nota de Crédito Electrónica
     */
    public function createNCE(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'original_cdc' => 'required|string|size:44',
                'motivo' => 'required|in:E401,E402',
                'serie' => 'string|max:10',
                'total' => 'required|numeric|min:0.01',
                'items' => 'required|array|min:1',
                'items.*.descripcion' => 'required|string|max:255',
                'items.*.cantidad' => 'required|numeric|min:0.01',
                'items.*.precio_unitario' => 'required|numeric|min:0',
                'observaciones' => 'string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $company = Company::find($request->company_id);
            
            // Verificar permisos
            if ($company->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $originalDocument = ElectronicDocument::findByCDC($request->original_cdc);
            if (!$originalDocument) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento original no encontrado'
                ], 404);
            }

            $nceData = $request->only([
                'motivo', 'serie', 'total', 'items', 'observaciones'
            ]);
            $nceData['documento_asociado'] = $request->original_cdc;

            $result = $this->nceService->createNCE($company, $nceData, $originalDocument);

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera KuDE para un documento
     */
    public function generateKuDE(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cdc' => 'required|string|size:44'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'CDC requerido',
                    'errors' => $validator->errors()
                ], 400);
            }

            $document = ElectronicDocument::findByCDC($request->cdc);
            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado'
                ], 404);
            }

            // Verificar permisos
            if ($document->company->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            if (!$document->xml_content) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento sin contenido XML'
                ], 400);
            }

            $result = $this->kudeService->generateKuDE(
                $document->company,
                $document,
                $document->xml_content
            );

            // Actualizar documento con rutas de archivos
            $document->update([
                'kude_path' => $result['kude_path'],
                'qr_path' => $result['qr_path']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KuDE generado exitosamente',
                'kude_url' => url('storage/kude/' . basename($result['kude_path'])),
                'qr_url' => url('storage/qr_codes/' . basename($result['qr_path']))
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consulta estado de documento en SIFEN
     */
    public function consultDocument(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cdc' => 'required|string|size:44',
                'company_id' => 'required|exists:companies,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $company = Company::find($request->company_id);
            
            // Verificar permisos
            if ($company->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $result = $this->webService->siConsDE($company, $request->cdc);

            return response()->json([
                'success' => true,
                'sifen_response' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valida RUC en SIFEN
     */
    public function validateRUC(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ruc' => 'required|string',
                'company_id' => 'required|exists:companies,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $company = Company::find($request->company_id);
            
            // Verificar permisos
            if ($company->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $result = $this->webService->validateRUC($company, $request->ruc);

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envía documento en lote
     */
    public function sendBatch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'document_ids' => 'required|array|min:1|max:15',
                'document_ids.*' => 'exists:electronic_documents,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $company = Company::find($request->company_id);
            
            // Verificar permisos
            if ($company->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $documents = ElectronicDocument::whereIn('id', $request->document_ids)
                ->where('company_id', $company->id)
                ->get();

            $xmlDocuments = $documents->pluck('xml_content')->toArray();

            $result = $this->webService->siRecepLoteDE($company, $xmlDocuments);

            // Actualizar estados de documentos
            foreach ($documents as $document) {
                $document->updateStatus(
                    $result['success'] ? ElectronicDocument::STATUS_SENT : ElectronicDocument::STATUS_ERROR,
                    $result
                );
            }

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consulta resultado de lote
     */
    public function consultBatch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'lote_id' => 'required|string',
                'company_id' => 'required|exists:companies,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $company = Company::find($request->company_id);
            
            // Verificar permisos
            if ($company->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $result = $this->webService->siResultLoteDE($company, $request->lote_id);

            return response()->json([
                'success' => true,
                'batch_result' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sube certificado P12 para empresa
     */
    public function uploadCertificate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'certificate' => 'required|file|mimes:p12,pfx',
                'password' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $company = Company::find($request->company_id);
            
            // Verificar permisos
            if ($company->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $certPath = $this->certificateService->storeCertificate(
                $company,
                $request->file('certificate'),
                $request->password ?? ''
            );

            return response()->json([
                'success' => true,
                'message' => 'Certificado subido exitosamente',
                'certificate_path' => $certPath
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene eventos de un documento
     */
    public function getDocumentEvents($cdc)
    {
        try {
            $document = ElectronicDocument::findByCDC($cdc);
            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado'
                ], 404);
            }

            // Verificar permisos
            if ($document->company->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $events = $document->events()->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'events' => $events
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesa lote de eventos pendientes
     */
    public function processEventBatch()
    {
        try {
            $result = $this->eventService->processEventBatch();

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas del sistema
     */
    public function getStats(Request $request)
    {
        try {
            $companyId = $request->query('company_id');
            
            if ($companyId) {
                $company = Company::find($companyId);
                if (!$company || $company->user_id !== auth()->id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No autorizado'
                    ], 403);
                }
            }

            $stats = [
                'documents' => ElectronicDocument::getStatusStats($companyId),
                'events' => DocumentEvent::getEventStats(),
                'today_documents' => ElectronicDocument::today()
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->count(),
                'pending_events' => DocumentEvent::pending()
                    ->when($companyId, function($q) use ($companyId) {
                        $q->whereHas('electronicDocument', fn($subQ) => $subQ->where('company_id', $companyId));
                    })
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
