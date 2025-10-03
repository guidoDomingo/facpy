<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ElectronicDocument;
use Exception;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use TCPDF;

/**
 * Servicio para generación de KuDE y códigos QR
 * Implementa representación gráfica según capítulo 13.8 SIFEN
 */
class SifenKudeService
{
    private $qrCodeService;
    
    public function __construct()
    {
        // Verificar que las librerías necesarias estén disponibles
        if (!class_exists('Endroid\QrCode\QrCode')) {
            throw new Exception('Librería endroid/qr-code no está instalada. Ejecute: composer require endroid/qr-code');
        }
        
        if (!class_exists('TCPDF')) {
            throw new Exception('Librería TCPDF no está instalada. Ejecute: composer require tecnickcom/tcpdf');
        }
    }

    /**
     * Genera la representación gráfica KuDE completa
     */
    public function generateKuDE(Company $company, ElectronicDocument $document, $xmlContent)
    {
        try {
            // Parsear datos del XML
            $xmlData = $this->parseXMLData($xmlContent);
            
            // Generar código QR
            $qrCodePath = $this->generateQRCode($document->cdc, $xmlData);
            
            // Generar PDF KuDE
            $kudePath = $this->generateKuDEPDF($company, $document, $xmlData, $qrCodePath);
            
            return [
                'kude_path' => $kudePath,
                'qr_path' => $qrCodePath,
                'success' => true
            ];
        } catch (Exception $e) {
            throw new Exception('Error generando KuDE: ' . $e->getMessage());
        }
    }

    /**
     * Genera código QR según especificación capítulo 13.8
     */
    public function generateQRCode($cdc, $xmlData)
    {
        try {
            // Construir contenido QR según especificación SIFEN
            $qrContent = $this->buildQRContent($cdc, $xmlData);
            
            // Generar QR
            $qrCode = new QrCode($qrContent);
            $qrCode->setSize(300);
            $qrCode->setMargin(10);
            
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            
            // Guardar archivo
            $fileName = 'qr_' . $cdc . '.png';
            $path = storage_path('app/public/qr_codes/' . $fileName);
            
            // Crear directorio si no existe
            $directory = dirname($path);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            file_put_contents($path, $result->getString());
            
            return $path;
        } catch (Exception $e) {
            throw new Exception('Error generando código QR: ' . $e->getMessage());
        }
    }

    /**
     * Construye el contenido del código QR según especificación
     */
    private function buildQRContent($cdc, $xmlData)
    {
        // Formato QR según capítulo 13.8:
        // nVersion|Id|dDVId|dFecFirma|dSisFact|dRUCEmi|dTiDE|dNumDE|dFeEmiDE|dTotGralOpe|dTotIVA|cItems|dVerFor
        
        $content = [
            '150', // nVersion
            $cdc, // Id (CDC completo)
            substr($cdc, 42, 1), // dDVId (dígito verificador)
            $xmlData['fecha_firma'] ?? date('Y-m-d\TH:i:s'), // dFecFirma
            '1', // dSisFact (1=Facturación)
            $xmlData['emisor']['ruc'] ?? '', // dRUCEmi
            $xmlData['tipo_documento'] ?? '1', // dTiDE
            $xmlData['numero_documento'] ?? '', // dNumDE
            $xmlData['fecha_emision'] ?? '', // dFeEmiDE
            number_format($xmlData['total_general'] ?? 0, 0, '', ''), // dTotGralOpe
            number_format($xmlData['total_iva'] ?? 0, 0, '', ''), // dTotIVA
            count($xmlData['items'] ?? []), // cItems
            '1' // dVerFor (versión formato)
        ];
        
        return implode('|', $content);
    }

    /**
     * Genera PDF KuDE con formato oficial
     */
    public function generateKuDEPDF(Company $company, ElectronicDocument $document, $xmlData, $qrCodePath)
    {
        try {
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Configuración del documento
            $pdf->SetCreator('Sistema Facturación Paraguay SIFEN');
            $pdf->SetAuthor($company->business_name);
            $pdf->SetTitle('Factura Electrónica - ' . $document->cdc);
            $pdf->SetSubject('KuDE - Documento Electrónico');
            
            // Configurar página
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 25);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            
            // Agregar página
            $pdf->AddPage();
            
            // Header del documento
            $this->addKuDEHeader($pdf, $company, $xmlData);
            
            // Datos del emisor
            $this->addEmisorData($pdf, $xmlData);
            
            // Datos del receptor
            $this->addReceptorData($pdf, $xmlData);
            
            // Items del documento
            $this->addItemsTable($pdf, $xmlData);
            
            // Totales
            $this->addTotalesSection($pdf, $xmlData);
            
            // Código QR y CDC
            $this->addQRAndCDC($pdf, $qrCodePath, $document->cdc);
            
            // Footer legal
            $this->addLegalFooter($pdf, $xmlData);
            
            // Guardar PDF
            $fileName = 'kude_' . $document->cdc . '.pdf';
            $path = storage_path('app/public/kude/' . $fileName);
            
            // Crear directorio si no existe
            $directory = dirname($path);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $pdf->Output($path, 'F');
            
            return $path;
        } catch (Exception $e) {
            throw new Exception('Error generando PDF KuDE: ' . $e->getMessage());
        }
    }

    /**
     * Agrega header del KuDE
     */
    private function addKuDEHeader($pdf, $company, $xmlData)
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'FACTURA ELECTRÓNICA', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'República del Paraguay', 0, 1, 'C');
        $pdf->Cell(0, 8, 'Sistema Integrado de Facturación Electrónica', 0, 1, 'C');
        
        $pdf->Ln(5);
    }

    /**
     * Agrega datos del emisor
     */
    private function addEmisorData($pdf, $xmlData)
    {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'DATOS DEL EMISOR', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Razón Social:', 0, 0, 'L');
        $pdf->Cell(0, 6, $xmlData['emisor']['razon_social'] ?? '', 0, 1, 'L');
        
        $pdf->Cell(40, 6, 'RUC:', 0, 0, 'L');
        $pdf->Cell(0, 6, $xmlData['emisor']['ruc'] ?? '', 0, 1, 'L');
        
        $pdf->Cell(40, 6, 'Dirección:', 0, 0, 'L');
        $pdf->Cell(0, 6, $xmlData['emisor']['direccion'] ?? '', 0, 1, 'L');
        
        $pdf->Cell(40, 6, 'Timbrado:', 0, 0, 'L');
        $pdf->Cell(0, 6, $xmlData['timbrado'] ?? '', 0, 1, 'L');
        
        $pdf->Ln(3);
    }

    /**
     * Agrega datos del receptor
     */
    private function addReceptorData($pdf, $xmlData)
    {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'DATOS DEL RECEPTOR', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Razón Social:', 0, 0, 'L');
        $pdf->Cell(0, 6, $xmlData['receptor']['razon_social'] ?? '', 0, 1, 'L');
        
        $pdf->Cell(40, 6, 'RUC:', 0, 0, 'L');
        $pdf->Cell(0, 6, $xmlData['receptor']['ruc'] ?? '', 0, 1, 'L');
        
        $pdf->Ln(3);
    }

    /**
     * Agrega tabla de items
     */
    private function addItemsTable($pdf, $xmlData)
    {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'DETALLE DE PRODUCTOS/SERVICIOS', 0, 1, 'L');
        
        // Header de tabla
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(10, 8, 'Item', 1, 0, 'C');
        $pdf->Cell(80, 8, 'Descripción', 1, 0, 'C');
        $pdf->Cell(20, 8, 'Cantidad', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Precio Unit.', 1, 0, 'C');
        $pdf->Cell(20, 8, 'IVA', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Total', 1, 1, 'C');
        
        // Items
        $pdf->SetFont('helvetica', '', 9);
        $items = $xmlData['items'] ?? [];
        
        foreach ($items as $index => $item) {
            $pdf->Cell(10, 6, $index + 1, 1, 0, 'C');
            $pdf->Cell(80, 6, substr($item['descripcion'] ?? '', 0, 40), 1, 0, 'L');
            $pdf->Cell(20, 6, number_format($item['cantidad'] ?? 0, 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($item['precio_unitario'] ?? 0, 0), 1, 0, 'R');
            $pdf->Cell(20, 6, ($item['tasa_iva'] ?? 0) . '%', 1, 0, 'C');
            $pdf->Cell(25, 6, number_format($item['total'] ?? 0, 0), 1, 1, 'R');
        }
        
        $pdf->Ln(3);
    }

    /**
     * Agrega sección de totales
     */
    private function addTotalesSection($pdf, $xmlData)
    {
        $pdf->SetFont('helvetica', 'B', 12);
        
        $pdf->Cell(130, 6, '', 0, 0); // Espaciado
        $pdf->Cell(30, 6, 'Subtotal:', 0, 0, 'R');
        $pdf->Cell(30, 6, number_format($xmlData['subtotal'] ?? 0, 0), 0, 1, 'R');
        
        $pdf->Cell(130, 6, '', 0, 0);
        $pdf->Cell(30, 6, 'IVA:', 0, 0, 'R');
        $pdf->Cell(30, 6, number_format($xmlData['total_iva'] ?? 0, 0), 0, 1, 'R');
        
        $pdf->Cell(130, 6, '', 0, 0);
        $pdf->Cell(30, 6, 'TOTAL:', 1, 0, 'R');
        $pdf->Cell(30, 6, number_format($xmlData['total_general'] ?? 0, 0), 1, 1, 'R');
        
        $pdf->Ln(5);
    }

    /**
     * Agrega código QR y CDC
     */
    private function addQRAndCDC($pdf, $qrCodePath, $cdc)
    {
        // Código QR
        $pdf->Image($qrCodePath, 15, $pdf->GetY(), 30, 30, 'PNG');
        
        // CDC
        $pdf->SetXY(50, $pdf->GetY());
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'CDC (Código de Control):', 0, 1, 'L');
        
        $pdf->SetX(50);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 6, $cdc, 0, 1, 'L');
        
        $pdf->Ln(35);
    }

    /**
     * Agrega footer legal
     */
    private function addLegalFooter($pdf, $xmlData)
    {
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 5, 'Documento electrónico generado según Ley 6380/2019', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Fecha y hora de generación: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Cell(0, 5, 'Este documento tiene validez legal según normativa vigente', 0, 1, 'C');
    }

    /**
     * Parsea datos del XML para KuDE
     */
    private function parseXMLData($xmlContent)
    {
        try {
            $xml = simplexml_load_string($xmlContent);
            
            $data = [
                'emisor' => [
                    'ruc' => (string)$xml->xpath('//dRUCEm')[0] ?? '',
                    'razon_social' => (string)$xml->xpath('//dNomEm')[0] ?? '',
                    'direccion' => (string)$xml->xpath('//dDirEm')[0] ?? ''
                ],
                'receptor' => [
                    'ruc' => (string)$xml->xpath('//dRUCRec')[0] ?? '',
                    'razon_social' => (string)$xml->xpath('//dNomRec')[0] ?? ''
                ],
                'timbrado' => (string)$xml->xpath('//dNumTim')[0] ?? '',
                'numero_documento' => (string)$xml->xpath('//dNumDoc')[0] ?? '',
                'fecha_emision' => (string)$xml->xpath('//dFeEmiDE')[0] ?? '',
                'tipo_documento' => (string)$xml->xpath('//iTiDE')[0] ?? '',
                'items' => [],
                'subtotal' => 0,
                'total_iva' => 0,
                'total_general' => 0
            ];
            
            // Parsear items
            $itemNodes = $xml->xpath('//gCamItem');
            foreach ($itemNodes as $item) {
                $itemData = [
                    'descripcion' => (string)$item->dDesProSer,
                    'cantidad' => (float)$item->dCantProSer,
                    'precio_unitario' => (float)$item->dPUniProSer,
                    'total' => (float)$item->dTotBruOpeItem,
                    'tasa_iva' => (float)$item->gValorItem->dTasaIVA ?? 0
                ];
                $data['items'][] = $itemData;
            }
            
            // Parsear totales
            $data['subtotal'] = (float)$xml->xpath('//dSubExe')[0] ?? 0;
            $data['total_iva'] = (float)$xml->xpath('//dLiqTotIVA5')[0] + (float)$xml->xpath('//dLiqTotIVA10')[0] ?? 0;
            $data['total_general'] = (float)$xml->xpath('//dTotGralOpe')[0] ?? 0;
            
            return $data;
        } catch (Exception $e) {
            throw new Exception('Error parseando XML: ' . $e->getMessage());
        }
    }

    /**
     * Valida que el QR coincida con el XML
     */
    public function validateQRConsistency($cdc, $xmlData, $qrContent)
    {
        $expectedQR = $this->buildQRContent($cdc, $xmlData);
        return $qrContent === $expectedQR;
    }
}
