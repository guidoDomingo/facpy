<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ElectronicDocument;
use Exception;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

/**
 * Servicio para Nota de Crédito Electrónica (NCE)
 * Implementa funcionalidades específicas del tipo C002=5
 */
class SifenNCEService
{
    private $sifenService;
    private $eventService;
    private $certificateService;

    public function __construct(
        SifenService $sifenService,
        SifenEventService $eventService,
        SifenCertificateService $certificateService
    ) {
        $this->sifenService = $sifenService;
        $this->eventService = $eventService;
        $this->certificateService = $certificateService;
    }

    /**
     * Crea una Nota de Crédito Electrónica
     */
    public function createNCE(Company $company, array $nceData, ElectronicDocument $originalDocument)
    {
        try {
            // Validar que el documento original esté aprobado
            if ($originalDocument->estado !== ElectronicDocument::STATUS_APPROVED) {
                throw new Exception('El documento original debe estar aprobado para generar NCE');
            }

            // Validar datos específicos de NCE
            $this->validateNCEData($nceData, $originalDocument);

            // Preparar datos para XML
            $xmlData = $this->prepareNCEXMLData($nceData, $originalDocument);

            // Generar CDC para NCE
            $cdc = $this->sifenService->generateCDC($company, $xmlData);

            // Generar XML de NCE
            $xmlContent = $this->generateNCEXML($company, $xmlData, $cdc);

            // Crear registro en base de datos
            $nceDocument = ElectronicDocument::create([
                'company_id' => $company->id,
                'cdc' => $cdc,
                'tipo_documento' => ElectronicDocument::TYPE_NOTA_CREDITO,
                'serie' => $xmlData['serie'],
                'numero_documento' => $xmlData['numero'],
                'fecha_emision' => $xmlData['fecha_emision'],
                'receptor_ruc' => $originalDocument->receptor_ruc,
                'receptor_razon_social' => $originalDocument->receptor_razon_social,
                'total_documento' => $xmlData['total'],
                'estado' => ElectronicDocument::STATUS_GENERATED,
                'xml_content' => $xmlContent
            ]);

            // Firmar XML
            $xmlSigned = $this->certificateService->signXML($company, $xmlContent);
            $nceDocument->update([
                'xml_content' => $xmlSigned,
                'estado' => ElectronicDocument::STATUS_SIGNED
            ]);

            // Procesar evento automático de devolución/ajuste
            $this->processAutomaticEvent($nceDocument, $originalDocument, $nceData);

            return [
                'success' => true,
                'message' => 'NCE generada exitosamente',
                'nce_document' => $nceDocument,
                'cdc' => $cdc
            ];
        } catch (Exception $e) {
            Log::error('Error creando NCE: ' . $e->getMessage());
            throw new Exception('Error creando NCE: ' . $e->getMessage());
        }
    }

    /**
     * Valida datos específicos de NCE
     */
    private function validateNCEData(array $nceData, ElectronicDocument $originalDocument)
    {
        // Validar motivo E401/E402
        if (!isset($nceData['motivo']) || !in_array($nceData['motivo'], ['E401', 'E402'])) {
            throw new Exception('Motivo de NCE requerido (E401: Devolución, E402: Ajuste)');
        }

        // Validar asociación obligatoria
        if (!isset($nceData['documento_asociado']) || $nceData['documento_asociado'] !== $originalDocument->cdc) {
            throw new Exception('NCE debe estar asociada al documento original');
        }

        // Validar montos
        if (!isset($nceData['total']) || $nceData['total'] <= 0) {
            throw new Exception('Total de NCE debe ser mayor a cero');
        }

        if ($nceData['total'] > $originalDocument->total_documento) {
            throw new Exception('Total de NCE no puede ser mayor al documento original');
        }

        // Validar items
        if (!isset($nceData['items']) || empty($nceData['items'])) {
            throw new Exception('NCE debe incluir items');
        }

        // Validar que no sea retracto (ajuste total)
        if ($nceData['motivo'] === 'E402' && $nceData['total'] == $originalDocument->total_documento) {
            throw new Exception('No se permite ajuste total (retracto). Use cancelación en su lugar.');
        }
    }

    /**
     * Prepara datos para XML de NCE
     */
    private function prepareNCEXMLData(array $nceData, ElectronicDocument $originalDocument)
    {
        return [
            'tipo_documento' => '5', // C002 = 5 para NCE
            'serie' => $nceData['serie'] ?? '001',
            'numero' => $nceData['numero'] ?? ElectronicDocument::getNextNumber(
                $originalDocument->company, 
                ElectronicDocument::TYPE_NOTA_CREDITO, 
                $nceData['serie'] ?? '001'
            ),
            'fecha_emision' => $nceData['fecha_emision'] ?? now()->toDateString(),
            'documento_asociado' => $originalDocument->cdc,
            'motivo_nce' => $nceData['motivo'],
            'descripcion_motivo' => $this->getMotivoDescription($nceData['motivo']),
            'items' => $nceData['items'],
            'total' => $nceData['total'],
            'observaciones' => $nceData['observaciones'] ?? '',
            // Datos del receptor (mismo que documento original)
            'receptor' => [
                'ruc' => $originalDocument->receptor_ruc,
                'razon_social' => $originalDocument->receptor_razon_social
            ]
        ];
    }

    /**
     * Genera XML específico para NCE
     */
    private function generateNCEXML(Company $company, array $data, $cdc)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rDE xmlns="http://ekuatia.set.gov.py/sifen/xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ekuatia.set.gov.py/sifen/xsd siRecepDE_v150.xsd"/>');

        // Datos Evento
        $dE = $xml->addChild('DE');
        $dE->addAttribute('Id', $cdc);

        // Encabezado
        $gEncDE = $dE->addChild('gEncDE');
        $gEncDE->addChild('dFecEm', $data['fecha_emision']);
        $gEncDE->addChild('dVerFor', '150');

        // Identificación del Documento
        $gTimbre = $gEncDE->addChild('gTimbre');
        $gTimbre->addChild('iTiDE', '5'); // Tipo NCE
        $gTimbre->addChild('dDesTiDE', 'Nota de crédito electrónica');
        $gTimbre->addChild('dNumTim', $company->timbrado);
        $gTimbre->addChild('dSerieNum', $data['serie'] . '-' . $data['numero']);

        // Datos Emisor
        $gDatGralOpe = $gEncDE->addChild('gDatGralOpe');
        $gEmis = $gDatGralOpe->addChild('gEmis');
        $gEmis->addChild('dRUCEm', $company->ruc);
        $gEmis->addChild('dNomEm', $company->business_name);

        // Datos Receptor
        $gDatRec = $gDatGralOpe->addChild('gDatRec');
        $gDatRec->addChild('iNatRec', '1'); // Contribuyente
        $gDatRec->addChild('dRUCRec', $data['receptor']['ruc']);
        $gDatRec->addChild('dNomRec', $data['receptor']['razon_social']);

        // Grupo E400 - Específico para NCE
        $gE400 = $gDatGralOpe->addChild('gE400');
        $gE400->addChild('dCDCAsoc', $data['documento_asociado']);
        $gE400->addChild('dMotivoNCE', $data['motivo_nce']);
        $gE400->addChild('dDescMotivoNCE', $data['descripcion_motivo']);

        // Items de la NCE
        foreach ($data['items'] as $index => $item) {
            $gCamItem = $dE->addChild('gCamItem');
            $gCamItem->addChild('dSecItem', $index + 1);
            $gCamItem->addChild('dDesProSer', $item['descripcion']);
            $gCamItem->addChild('cUniMed', $item['unidad_medida'] ?? '77');
            $gCamItem->addChild('dCantProSer', $item['cantidad']);
            $gCamItem->addChild('dPUniProSer', $item['precio_unitario']);

            $totalItem = $item['cantidad'] * $item['precio_unitario'];
            $gCamItem->addChild('dTotBruOpeItem', $totalItem);

            // Valor del ítem con IVA
            $gValorItem = $gCamItem->addChild('gValorItem');
            if (isset($item['exento_iva']) && $item['exento_iva']) {
                $gValorItem->addChild('iAfecIVA', '3'); // Exento
                $gValorItem->addChild('dTasaIVA', '0');
                $gValorItem->addChild('dBasGravIVA', '0');
                $gValorItem->addChild('dLiqIVAItem', '0');
            } else {
                $tasaIva = $item['tasa_iva'] ?? 10;
                $baseGravada = $totalItem / (1 + ($tasaIva / 100));
                $montoIva = $totalItem - $baseGravada;

                $gValorItem->addChild('iAfecIVA', '1'); // Gravado
                $gValorItem->addChild('dTasaIVA', $tasaIva);
                $gValorItem->addChild('dBasGravIVA', round($baseGravada, 2));
                $gValorItem->addChild('dLiqIVAItem', round($montoIva, 2));
            }
        }

        // Totales
        $gTotGener = $dE->addChild('gTotGener');
        $gTotGener->addChild('dSubExe', $this->calculateExemptTotal($data['items']));
        $gTotGener->addChild('dSubExo', '0');
        $gTotGener->addChild('dSub5', $this->calculateTaxableTotal($data['items'], 5));
        $gTotGener->addChild('dSub10', $this->calculateTaxableTotal($data['items'], 10));
        $gTotGener->addChild('dLiqTotIVA5', $this->calculateTaxTotal($data['items'], 5));
        $gTotGener->addChild('dLiqTotIVA10', $this->calculateTaxTotal($data['items'], 10));
        $gTotGener->addChild('dTotGralOpe', $data['total']);

        return $xml->asXML();
    }

    /**
     * Procesa evento automático de devolución/ajuste
     */
    private function processAutomaticEvent(ElectronicDocument $nceDocument, ElectronicDocument $originalDocument, array $nceData)
    {
        try {
            // Determinar tipo de evento según motivo
            $eventType = $nceData['motivo'] === 'E401' ? 'devolucion' : 'ajuste';
            
            $this->eventService->processNCEEvent($nceDocument, $originalDocument);
            
            Log::info("Evento automático de {$eventType} procesado para NCE: {$nceDocument->cdc}");
        } catch (Exception $e) {
            Log::error("Error procesando evento automático para NCE {$nceDocument->cdc}: " . $e->getMessage());
            // No lanzar excepción para no fallar la creación de NCE
        }
    }

    /**
     * Obtiene descripción del motivo NCE
     */
    private function getMotivoDescription($motivo)
    {
        $motivos = [
            'E401' => 'Devolución de mercaderías',
            'E402' => 'Ajuste en precio, cantidad o descuento'
        ];

        return $motivos[$motivo] ?? 'Motivo no especificado';
    }

    /**
     * Valida reglas específicas de NCE
     */
    public function validateNCERules(ElectronicDocument $nceDocument, ElectronicDocument $originalDocument)
    {
        $rules = [];

        // Regla: NCE no puede ser mayor al documento original
        if ($nceDocument->total_documento > $originalDocument->total_documento) {
            $rules[] = [
                'rule' => 'total_amount',
                'valid' => false,
                'message' => 'Total de NCE excede el documento original'
            ];
        }

        // Regla: Verificar crédito fiscal IVA
        $creditoIVA = $this->calculateIVACredit($nceDocument);
        if ($creditoIVA > 0) {
            $rules[] = [
                'rule' => 'iva_credit',
                'valid' => true,
                'message' => "Genera crédito fiscal IVA: {$creditoIVA}",
                'credit_amount' => $creditoIVA
            ];
        }

        // Regla: Verificar plazo para NCE
        $daysSinceOriginal = $originalDocument->fecha_emision->diffInDays(now());
        if ($daysSinceOriginal > 365) {
            $rules[] = [
                'rule' => 'time_limit',
                'valid' => false,
                'message' => 'NCE fuera del plazo permitido (1 año)'
            ];
        }

        return $rules;
    }

    /**
     * Calcula crédito fiscal IVA de la NCE
     */
    private function calculateIVACredit(ElectronicDocument $nceDocument)
    {
        if (!$nceDocument->xml_content) {
            return 0;
        }

        try {
            $xml = simplexml_load_string($nceDocument->xml_content);
            $iva5 = (float)($xml->xpath('//dLiqTotIVA5')[0] ?? 0);
            $iva10 = (float)($xml->xpath('//dLiqTotIVA10')[0] ?? 0);
            
            return $iva5 + $iva10;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Calcula total exento
     */
    private function calculateExemptTotal(array $items)
    {
        $total = 0;
        foreach ($items as $item) {
            if (isset($item['exento_iva']) && $item['exento_iva']) {
                $total += $item['cantidad'] * $item['precio_unitario'];
            }
        }
        return $total;
    }

    /**
     * Calcula total gravado por tasa
     */
    private function calculateTaxableTotal(array $items, $tasa)
    {
        $total = 0;
        foreach ($items as $item) {
            if (!isset($item['exento_iva']) || !$item['exento_iva']) {
                if (($item['tasa_iva'] ?? 10) == $tasa) {
                    $totalItem = $item['cantidad'] * $item['precio_unitario'];
                    $total += $totalItem / (1 + ($tasa / 100));
                }
            }
        }
        return round($total, 2);
    }

    /**
     * Calcula total de IVA por tasa
     */
    private function calculateTaxTotal(array $items, $tasa)
    {
        $total = 0;
        foreach ($items as $item) {
            if (!isset($item['exento_iva']) || !$item['exento_iva']) {
                if (($item['tasa_iva'] ?? 10) == $tasa) {
                    $totalItem = $item['cantidad'] * $item['precio_unitario'];
                    $baseGravada = $totalItem / (1 + ($tasa / 100));
                    $total += $totalItem - $baseGravada;
                }
            }
        }
        return round($total, 2);
    }

    /**
     * Obtiene NCEs asociadas a un documento
     */
    public function getAssociatedNCEs(ElectronicDocument $document)
    {
        // Buscar en el XML content de otros documentos NCE
        return ElectronicDocument::where('tipo_documento', ElectronicDocument::TYPE_NOTA_CREDITO)
            ->where('company_id', $document->company_id)
            ->whereRaw("xml_content LIKE ?", ["%{$document->cdc}%"])
            ->get();
    }

    /**
     * Verifica si un documento tiene NCEs asociadas
     */
    public function hasAssociatedNCEs(ElectronicDocument $document)
    {
        return $this->getAssociatedNCEs($document)->isNotEmpty();
    }
}
