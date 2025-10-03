<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ElectronicDocument;
use App\Models\DocumentEvent;
use Exception;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use Carbon\Carbon;

/**
 * Servicio para manejo de eventos SIFEN
 * Implementa cancelación, inutilización y eventos del receptor
 */
class SifenEventService
{
    private $webService;
    private $certificateService;
    private $sifenService;

    public function __construct(
        SifenWebService $webService,
        SifenCertificateService $certificateService,
        SifenService $sifenService
    ) {
        $this->webService = $webService;
        $this->certificateService = $certificateService;
        $this->sifenService = $sifenService;
    }

    /**
     * Cancela un documento electrónico (ventana 48h)
     */
    public function cancelDocument(ElectronicDocument $document, $reason)
    {
        try {
            // Verificar ventana de 48h
            if (!$document->canBeCancelled()) {
                throw new Exception('El documento no puede ser cancelado. Fuera de la ventana de 48 horas o estado incorrecto.');
            }

            // Generar XML de cancelación
            $xmlCancelacion = $this->generateCancellationXML($document, $reason);
            
            // Firmar XML
            $xmlSigned = $this->certificateService->signXML($document->company, $xmlCancelacion);
            
            // Crear evento
            $event = DocumentEvent::createCancellation($document, $reason, $xmlSigned);
            
            // Enviar a SIFEN
            $response = $this->webService->siRecepEvento($document->company, $xmlSigned);
            
            if ($response['success']) {
                $event->updateStatus(DocumentEvent::STATUS_APPROVED, $response);
                $document->updateStatus(ElectronicDocument::STATUS_CANCELLED, 'Cancelado por evento SIFEN');
                
                return [
                    'success' => true,
                    'message' => 'Documento cancelado exitosamente',
                    'event_id' => $event->id,
                    'protocol' => $response['protocol']
                ];
            } else {
                $event->updateStatus(DocumentEvent::STATUS_REJECTED, $response);
                
                return [
                    'success' => false,
                    'message' => 'Error en cancelación: ' . $response['message'],
                    'errors' => $response['errors']
                ];
            }
        } catch (Exception $e) {
            Log::error('Error cancelando documento: ' . $e->getMessage());
            throw new Exception('Error en cancelación: ' . $e->getMessage());
        }
    }

    /**
     * Inutiliza un rango de numeración
     */
    public function inutilizeRange(Company $company, $serie, $rangeStart, $rangeEnd, $reason)
    {
        try {
            // Validar rango
            if ((int)$rangeStart > (int)$rangeEnd) {
                throw new Exception('Rango inválido: inicio mayor que fin');
            }

            // Verificar que no existan documentos en el rango
            $existingDocs = ElectronicDocument::where('company_id', $company->id)
                ->where('serie', $serie)
                ->whereBetween('numero_documento', [$rangeStart, $rangeEnd])
                ->exists();

            if ($existingDocs) {
                throw new Exception('Existen documentos emitidos en el rango especificado');
            }

            // Generar XML de inutilización
            $xmlInutilizacion = $this->generateInutilizationXML($company, $serie, $rangeStart, $rangeEnd, $reason);
            
            // Firmar XML
            $xmlSigned = $this->certificateService->signXML($company, $xmlInutilizacion);
            
            // Crear evento
            $event = DocumentEvent::createInutilization($company->id, $serie, $rangeStart, $rangeEnd, $reason, $xmlSigned);
            
            // Enviar a SIFEN
            $response = $this->webService->siRecepEvento($company, $xmlSigned);
            
            if ($response['success']) {
                $event->updateStatus(DocumentEvent::STATUS_APPROVED, $response);
                
                return [
                    'success' => true,
                    'message' => 'Rango inutilizado exitosamente',
                    'event_id' => $event->id,
                    'protocol' => $response['protocol']
                ];
            } else {
                $event->updateStatus(DocumentEvent::STATUS_REJECTED, $response);
                
                return [
                    'success' => false,
                    'message' => 'Error en inutilización: ' . $response['message'],
                    'errors' => $response['errors']
                ];
            }
        } catch (Exception $e) {
            Log::error('Error inutilizando rango: ' . $e->getMessage());
            throw new Exception('Error en inutilización: ' . $e->getMessage());
        }
    }

    /**
     * Procesa evento automático por NCE (devolución/ajuste)
     */
    public function processNCEEvent(ElectronicDocument $nceDocument, ElectronicDocument $originalDocument)
    {
        try {
            $eventType = $this->determineNCEEventType($nceDocument);
            
            // Generar XML del evento
            $xmlEvent = $this->generateNCEEventXML($nceDocument, $originalDocument, $eventType);
            
            // Firmar XML
            $xmlSigned = $this->certificateService->signXML($nceDocument->company, $xmlEvent);
            
            // Crear evento
            $event = DocumentEvent::create([
                'electronic_document_id' => $originalDocument->id,
                'event_type' => $eventType,
                'event_code' => $eventType === DocumentEvent::TYPE_DEVOLUCION ? 
                    DocumentEvent::CODE_DEVOLUCION : DocumentEvent::CODE_AJUSTE,
                'description' => "Evento automático por NCE: {$nceDocument->cdc}",
                'xml_content' => $xmlSigned,
                'status' => DocumentEvent::STATUS_PENDING
            ]);
            
            // Enviar a SIFEN
            $response = $this->webService->siRecepEvento($nceDocument->company, $xmlSigned);
            
            if ($response['success']) {
                $event->updateStatus(DocumentEvent::STATUS_APPROVED, $response);
                
                return [
                    'success' => true,
                    'message' => 'Evento NCE procesado exitosamente',
                    'event_id' => $event->id
                ];
            } else {
                $event->updateStatus(DocumentEvent::STATUS_REJECTED, $response);
                
                return [
                    'success' => false,
                    'message' => 'Error procesando evento NCE: ' . $response['message']
                ];
            }
        } catch (Exception $e) {
            Log::error('Error procesando evento NCE: ' . $e->getMessage());
            throw new Exception('Error en evento NCE: ' . $e->getMessage());
        }
    }

    /**
     * Procesa notificación de receptor
     */
    public function processReceptorNotification($cdc, $receptorRuc, $notificationType, $details = null)
    {
        try {
            $document = ElectronicDocument::findByCDC($cdc);
            if (!$document) {
                throw new Exception('Documento no encontrado');
            }

            // Generar XML de notificación
            $xmlNotification = $this->generateReceptorNotificationXML($document, $receptorRuc, $notificationType, $details);
            
            // Firmar XML
            $xmlSigned = $this->certificateService->signXML($document->company, $xmlNotification);
            
            // Crear evento
            $event = DocumentEvent::create([
                'electronic_document_id' => $document->id,
                'event_type' => $notificationType,
                'event_code' => $this->getEventCode($notificationType),
                'description' => "Notificación receptor: {$receptorRuc}",
                'xml_content' => $xmlSigned,
                'status' => DocumentEvent::STATUS_PENDING
            ]);
            
            // Enviar a SIFEN
            $response = $this->webService->siRecepEvento($document->company, $xmlSigned);
            
            $event->updateStatus(
                $response['success'] ? DocumentEvent::STATUS_APPROVED : DocumentEvent::STATUS_REJECTED,
                $response
            );
            
            return $response;
        } catch (Exception $e) {
            Log::error('Error procesando notificación receptor: ' . $e->getMessage());
            throw new Exception('Error en notificación receptor: ' . $e->getMessage());
        }
    }

    /**
     * Genera XML de cancelación
     */
    private function generateCancellationXML(ElectronicDocument $document, $reason)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rEvento xmlns="http://ekuatia.set.gov.py/sifen/xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>');
        
        // Datos del evento
        $xml->addChild('dFecFirma', Carbon::now()->format('Y-m-d\TH:i:s'));
        $xml->addChild('dVerFor', '150');
        
        // Identificación del evento
        $gGroupTiEvt = $xml->addChild('gGroupTiEvt');
        $gGroupTiEvt->addChild('rTEv', DocumentEvent::CODE_CANCELACION);
        $gGroupTiEvt->addChild('dDesTEv', 'Cancelación');
        
        // Datos específicos de cancelación
        $gGroupEv = $xml->addChild('gGroupEv');
        $gGroupEv->addChild('dCDCPE', $document->cdc);
        $gGroupEv->addChild('dDescEv', $reason);
        
        return $xml->asXML();
    }

    /**
     * Genera XML de inutilización
     */
    private function generateInutilizationXML(Company $company, $serie, $rangeStart, $rangeEnd, $reason)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rEvento xmlns="http://ekuatia.set.gov.py/sifen/xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>');
        
        // Datos del evento
        $xml->addChild('dFecFirma', Carbon::now()->format('Y-m-d\TH:i:s'));
        $xml->addChild('dVerFor', '150');
        
        // Identificación del evento
        $gGroupTiEvt = $xml->addChild('gGroupTiEvt');
        $gGroupTiEvt->addChild('rTEv', DocumentEvent::CODE_INUTILIZACION);
        $gGroupTiEvt->addChild('dDesTEv', 'Inutilización');
        
        // Datos específicos de inutilización
        $gGroupEv = $xml->addChild('gGroupEv');
        $gGroupEv->addChild('dSerie', $serie);
        $gGroupEv->addChild('dNumIni', $rangeStart);
        $gGroupEv->addChild('dNumFin', $rangeEnd);
        $gGroupEv->addChild('dDescEv', $reason);
        
        return $xml->asXML();
    }

    /**
     * Genera XML de evento NCE
     */
    private function generateNCEEventXML(ElectronicDocument $nceDocument, ElectronicDocument $originalDocument, $eventType)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rEvento xmlns="http://ekuatia.set.gov.py/sifen/xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>');
        
        // Datos del evento
        $xml->addChild('dFecFirma', Carbon::now()->format('Y-m-d\TH:i:s'));
        $xml->addChild('dVerFor', '150');
        
        // Identificación del evento
        $gGroupTiEvt = $xml->addChild('gGroupTiEvt');
        $gGroupTiEvt->addChild('rTEv', $this->getEventCode($eventType));
        $gGroupTiEvt->addChild('dDesTEv', $eventType === DocumentEvent::TYPE_DEVOLUCION ? 'Devolución' : 'Ajuste');
        
        // Datos específicos del evento
        $gGroupEv = $xml->addChild('gGroupEv');
        $gGroupEv->addChild('dCDCPE', $originalDocument->cdc);
        $gGroupEv->addChild('dCDCNCE', $nceDocument->cdc);
        $gGroupEv->addChild('dDescEv', "Evento automático por NCE");
        
        return $xml->asXML();
    }

    /**
     * Genera XML de notificación de receptor
     */
    private function generateReceptorNotificationXML(ElectronicDocument $document, $receptorRuc, $notificationType, $details)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rEvento xmlns="http://ekuatia.set.gov.py/sifen/xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>');
        
        // Datos del evento
        $xml->addChild('dFecFirma', Carbon::now()->format('Y-m-d\TH:i:s'));
        $xml->addChild('dVerFor', '150');
        
        // Identificación del evento
        $gGroupTiEvt = $xml->addChild('gGroupTiEvt');
        $gGroupTiEvt->addChild('rTEv', $this->getEventCode($notificationType));
        $gGroupTiEvt->addChild('dDesTEv', $this->getEventTypeName($notificationType));
        
        // Datos específicos del evento
        $gGroupEv = $xml->addChild('gGroupEv');
        $gGroupEv->addChild('dCDCPE', $document->cdc);
        $gGroupEv->addChild('dRUCRec', $receptorRuc);
        if ($details) {
            $gGroupEv->addChild('dDescEv', $details);
        }
        
        return $xml->asXML();
    }

    /**
     * Determina el tipo de evento para NCE
     */
    private function determineNCEEventType(ElectronicDocument $nceDocument)
    {
        // Lógica para determinar si es devolución o ajuste
        // Por defecto retornamos devolución, pero esto debería basarse en los datos del NCE
        return DocumentEvent::TYPE_DEVOLUCION;
    }

    /**
     * Obtiene el código del evento
     */
    private function getEventCode($eventType)
    {
        $codes = [
            DocumentEvent::TYPE_CANCELACION => DocumentEvent::CODE_CANCELACION,
            DocumentEvent::TYPE_INUTILIZACION => DocumentEvent::CODE_INUTILIZACION,
            DocumentEvent::TYPE_DEVOLUCION => DocumentEvent::CODE_DEVOLUCION,
            DocumentEvent::TYPE_AJUSTE => DocumentEvent::CODE_AJUSTE,
            DocumentEvent::TYPE_NOTIFICACION_RECEPTOR => DocumentEvent::CODE_NOTIFICACION,
            DocumentEvent::TYPE_CONFORMIDAD => DocumentEvent::CODE_CONFORMIDAD,
            DocumentEvent::TYPE_DISCONFORMIDAD => DocumentEvent::CODE_DISCONFORMIDAD
        ];

        return $codes[$eventType] ?? '690';
    }

    /**
     * Obtiene el nombre del tipo de evento
     */
    private function getEventTypeName($eventType)
    {
        $names = [
            DocumentEvent::TYPE_CANCELACION => 'Cancelación',
            DocumentEvent::TYPE_INUTILIZACION => 'Inutilización',
            DocumentEvent::TYPE_DEVOLUCION => 'Devolución',
            DocumentEvent::TYPE_AJUSTE => 'Ajuste',
            DocumentEvent::TYPE_NOTIFICACION_RECEPTOR => 'Notificación Receptor',
            DocumentEvent::TYPE_CONFORMIDAD => 'Conformidad',
            DocumentEvent::TYPE_DISCONFORMIDAD => 'Disconformidad'
        ];

        return $names[$eventType] ?? 'Evento';
    }

    /**
     * Procesa eventos en lote (hasta 15)
     */
    public function processEventBatch()
    {
        $pendingEvents = DocumentEvent::pendingToSend()->take(15)->get();
        
        if ($pendingEvents->isEmpty()) {
            return ['success' => true, 'message' => 'No hay eventos pendientes'];
        }

        $results = [];
        $xmlDocuments = [];
        
        foreach ($pendingEvents as $event) {
            $xmlDocuments[] = $event->xml_content;
        }
        
        // Obtener la empresa del primer evento
        $company = $pendingEvents->first()->electronicDocument->company ?? 
                   Company::first(); // Fallback para eventos sin documento asociado
        
        try {
            $response = $this->webService->siRecepLoteDE($company, $xmlDocuments);
            
            if ($response['success']) {
                foreach ($pendingEvents as $event) {
                    $event->updateStatus(DocumentEvent::STATUS_SENT, $response);
                }
                
                $results['success'] = true;
                $results['message'] = 'Lote de eventos enviado exitosamente';
                $results['protocol'] = $response['protocol'];
            } else {
                foreach ($pendingEvents as $event) {
                    $event->updateStatus(DocumentEvent::STATUS_ERROR, $response);
                }
                
                $results['success'] = false;
                $results['message'] = 'Error enviando lote: ' . $response['message'];
            }
        } catch (Exception $e) {
            foreach ($pendingEvents as $event) {
                $event->updateStatus(DocumentEvent::STATUS_ERROR, null, ['error' => $e->getMessage()]);
            }
            
            $results['success'] = false;
            $results['message'] = 'Error procesando lote: ' . $e->getMessage();
        }
        
        return $results;
    }
}
