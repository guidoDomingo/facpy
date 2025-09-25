<?php

namespace App\Services;

use App\Models\Company;
use DateTime;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use Exception;

class SifenService
{
    private $baseUrl;
    private $testUrl = 'https://sifen-test.set.gov.py/de/ws/';
    private $productionUrl = 'https://sifen.set.gov.py/de/ws/';

    public function __construct()
    {
        // Por defecto usamos el ambiente de test
        $this->baseUrl = $this->testUrl;
    }

    /**
     * Configura el servicio SIFEN para una empresa
     */
    public function configureSifen($company)
    {
        $this->baseUrl = $company->production ? $this->productionUrl : $this->testUrl;
        
        return [
            'ruc' => $company->ruc,
            'environment' => $company->production ? 'production' : 'test',
            'certificate_path' => $company->cert_path,
            'certificate_password' => $company->cert_password ?? '',
        ];
    }

    /**
     * Genera el XML del Documento Electrónico según especificaciones SIFEN
     */
    public function generateXML($company, $data, $cdc = null)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rDE xmlns="http://ekuatia.set.gov.py/sifen/xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ekuatia.set.gov.py/sifen/xsd siRecepDE_v150.xsd"/>');
        
        // Generar CDC si no se proporcionó
        if (!$cdc) {
            $cdc = $this->generateCDC($company, $data);
        }
        
        // Datos de la transacción
        $dDatRec = $xml->addChild('dDatRec');
        $dDatRec->addChild('Id', '1');
        $dDatRec->addChild('dFechaEnviado', date('Y-m-d\TH:i:s'));
        
        // Documento Electrónico
        $dE = $xml->addChild('DE');
        $dE->addChild('Id', 'DE-1');
        
        // Datos Generales del Documento Electrónico
        $dDatGener = $dE->addChild('gDatGener');
        $dDatGener->addChild('dTiDE', $data['tipoDocumento'] ?? '01'); // 01=Factura electrónica
        $dDatGener->addChild('dSerie', $data['serie'] ?? '001');
        $dDatGener->addChild('dNroDoc', $data['numero'] ?? '1');
        $dDatGener->addChild('dFeEmiDE', (new DateTime($data['fechaEmision']))->format('Y-m-d'));
        $dDatGener->addChild('dSalSeg', $data['salidaSeguridad'] ?? 0); // 0=No es salida de seguridad
        $dDatGener->addChild('dCDC', $cdc); // Código de Control del Documento
        
        // Datos del Emisor (usar datos de la empresa)
        $gDatEmi = $dE->addChild('gDatEmi');
        $gDatEmi->addChild('dRucEmi', $company->ruc);
        $gDatEmi->addChild('dRazSocEmi', $company->razon_social);
        $gDatEmi->addChild('dNomFanEmi', $company->nombre_fantasia ?? '');
        
        // Dirección del Emisor
        $gDirEmi = $gDatEmi->addChild('gDirEmi');
        $gDirEmi->addChild('dDirEmi', $company->direccion);
        $gDirEmi->addChild('dNumCas', $company->numero_casa ?? '');
        $gDirEmi->addChild('cDepEmi', $company->codigo_departamento ?? '11');
        $gDirEmi->addChild('dDesDepEmi', $company->departamento ?? 'CAPITAL');
        $gDirEmi->addChild('cDisEmi', $company->codigo_distrito ?? '1');
        $gDirEmi->addChild('dDesDisEmi', $company->distrito ?? 'ASUNCIÓN');
        $gDirEmi->addChild('cCiuEmi', $company->codigo_ciudad ?? '1');
        $gDirEmi->addChild('dDesCiuEmi', $company->ciudad ?? 'ASUNCIÓN');

        // Datos del Receptor
        if (isset($data['receptor'])) {
            $gDatRec = $dE->addChild('gDatRec');
            $gDatRec->addChild('iTiContRec', $data['receptor']['tipoContribuyente'] ?? '1');
            $gDatRec->addChild('dRucRec', $data['receptor']['ruc'] ?? '');
            $gDatRec->addChild('dRazSocRec', $data['receptor']['razonSocial']);
            
            if (isset($data['receptor']['direccion'])) {
                $gDirRec = $gDatRec->addChild('gDirRec');
                $gDirRec->addChild('dDirRec', $data['receptor']['direccion']);
                $gDirRec->addChild('cDepRec', $data['receptor']['codigoDepartamento'] ?? '11');
                $gDirRec->addChild('dDesDepRec', $data['receptor']['departamento'] ?? 'CAPITAL');
                $gDirRec->addChild('cDisRec', $data['receptor']['codigoDistrito'] ?? '1');
                $gDirRec->addChild('dDesDisRec', $data['receptor']['distrito'] ?? 'ASUNCIÓN');
                $gDirRec->addChild('cCiuRec', $data['receptor']['codigoCiudad'] ?? '1');
                $gDirRec->addChild('dDesCiuRec', $data['receptor']['ciudad'] ?? 'ASUNCIÓN');
            }
        }

        // Items del Documento
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $index => $item) {
                $gCamItem = $dE->addChild('gCamItem');
                $gCamItem->addChild('dSecItem', $index + 1);
                $gCamItem->addChild('dDesProSer', $item['descripcion']);
                $gCamItem->addChild('cUniMed', $item['unidadMedida'] ?? '77'); // Unidad por defecto
                $gCamItem->addChild('dCantProSer', $item['cantidad']);
                $gCamItem->addChild('dPUniProSer', $item['precioUnitario']);
                
                // Valor total del ítem
                $dTotBruOpeItem = $item['cantidad'] * $item['precioUnitario'];
                $gCamItem->addChild('dTotBruOpeItem', $dTotBruOpeItem);
                
                // IVA del ítem
                $gValorItem = $gCamItem->addChild('gValorItem');
                
                if (isset($item['exentoIva']) && $item['exentoIva']) {
                    $gValorItem->addChild('iAfecIVA', '3'); // Exento
                    $gValorItem->addChild('dPropIVA', '0');
                    $gValorItem->addChild('dTasaIVA', '0');
                    $gValorItem->addChild('dBasGravIVA', '0');
                    $gValorItem->addChild('dLiqIVAItem', '0');
                } else {
                    $tasaIva = $item['tasaIva'] ?? 10; // 10% por defecto
                    $baseGravada = $dTotBruOpeItem / (1 + ($tasaIva / 100));
                    $montoIva = $dTotBruOpeItem - $baseGravada;
                    
                    $gValorItem->addChild('iAfecIVA', '1'); // Gravado
                    $gValorItem->addChild('dPropIVA', '100');
                    $gValorItem->addChild('dTasaIVA', $tasaIva);
                    $gValorItem->addChild('dBasGravIVA', round($baseGravada, 2));
                    $gValorItem->addChild('dLiqIVAItem', round($montoIva, 2));
                }
            }
        }

        // Totales del Documento
        $gTotGener = $dE->addChild('gTotGener');
        
        // Calcular totales
        $totalBruto = 0;
        $totalIva = 0;
        $totalExento = 0;
        
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $subtotal = $item['cantidad'] * $item['precioUnitario'];
                $totalBruto += $subtotal;
                
                if (!isset($item['exentoIva']) || !$item['exentoIva']) {
                    $tasaIva = $item['tasaIva'] ?? 10;
                    $baseGravada = $subtotal / (1 + ($tasaIva / 100));
                    $totalIva += $subtotal - $baseGravada;
                } else {
                    $totalExento += $subtotal;
                }
            }
        }
        
        $gTotGener->addChild('dSubExe', round($totalExento, 2));
        $gTotGener->addChild('dSubExo', '0'); // Exonerado
        $gTotGener->addChild('dSub5', '0'); // IVA 5%
        $gTotGener->addChild('dSub10', round($totalBruto - $totalExento - $totalIva, 2)); // Base IVA 10%
        $gTotGener->addChild('dTotOpe', round($totalBruto - $totalIva, 2)); // Total operación
        $gTotGener->addChild('dTotDesc', '0'); // Total descuentos
        $gTotGener->addChild('dTotDescGlotem', '0'); // Descuento global
        $gTotGener->addChild('dTotAntItem', '0'); // Anticipo por ítem
        $gTotGener->addChild('dTotAnt', '0'); // Total anticipos
        $gTotGener->addChild('dPorcDescTotal', '0'); // Porcentaje descuento total
        $gTotGener->addChild('dDescTotal', '0'); // Descuento total
        $gTotGener->addChild('dAnticipo', '0'); // Anticipo
        $gTotGener->addChild('dRedon', '0'); // Redondeo
        $gTotGener->addChild('dComi', '0'); // Comisión
        $gTotGener->addChild('dTotGralOpe', round($totalBruto - $totalIva, 2)); // Total general
        $gTotGener->addChild('dIVA5', '0'); // IVA 5%
        $gTotGener->addChild('dIVA10', round($totalIva, 2)); // IVA 10%
        $gTotGener->addChild('dLiqTotIVA5', '0'); // Liquidación IVA 5%
        $gTotGener->addChild('dLiqTotIVA10', round($totalIva, 2)); // Liquidación IVA 10%
        $gTotGener->addChild('dIVATotOpe', round($totalIva, 2)); // IVA total
        $gTotGener->addChild('dBaseGrav5', '0'); // Base gravada 5%
        $gTotGener->addChild('dBaseGrav10', round($totalBruto - $totalExento - $totalIva, 2)); // Base gravada 10%
        $gTotGener->addChild('dTBasGraIVA', round($totalBruto - $totalExento - $totalIva, 2)); // Total base gravada
        $gTotGener->addChild('dTotalGs', round($totalBruto, 2)); // Total en Guaraníes

        return $xml->asXML();
    }

    /**
     * Envía el documento electrónico al SIFEN
     */
    public function sendDocument($xmlContent, $company)
    {
        try {
            $config = $this->configureSifen($company);
            
            // Si está forzado usar conexión real o hay certificado válido, usar servidor real
            $forceReal = env('SIFEN_FORCE_REAL_CONNECTION', false);
            $hasCert = $company->cert_path && file_exists(storage_path('app/' . $company->cert_path));
            
            if (!$forceReal && !$company->production && !$hasCert) {
                return $this->simulateTestResponse($xmlContent);
            }
            
            // Firmar el XML con el certificado
            $signedXml = $this->signXML($xmlContent, $config);
            
            // Configurar endpoint correcto
            $endpoint = $company->production 
                ? env('SIFEN_PROD_URL', 'https://sifen.set.gov.py/de/ws/sync/recepcion-de')
                : env('SIFEN_TEST_URL', 'https://sifen-test.set.gov.py/de/ws/sync/recepcion-de');
            
            // Configurar certificado
            $certPath = $company->cert_path ? storage_path('app/' . $company->cert_path) : env('SIFEN_CERT_PATH');
            $certPassword = $company->cert_password ?: env('SIFEN_CERT_PASSWORD');
            
            // Opciones de conexión para SIFEN real
            $options = [
                'verify' => !$company->production, // false para testing, true para producción
                'timeout' => 60,
                'connect_timeout' => 30
            ];
            
            // Agregar certificado si existe
            if ($certPath && file_exists($certPath)) {
                $options['cert'] = [$certPath, $certPassword];
            }
            
            // Envío al endpoint de SIFEN
            $response = Http::withOptions($options)->withHeaders([
                'Content-Type' => 'application/xml; charset=UTF-8',
                'SOAPAction' => '"recepcionDE"',
                'User-Agent' => 'Paraguay-SIFEN-Client/1.0',
                'Accept' => 'application/xml, text/xml'
            ])->post($endpoint, $signedXml);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response' => $response->body(),
                'xml_sent' => $signedXml
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'xml_sent' => $xmlContent ?? null
            ];
        }
    }

    /**
     * Simula una respuesta exitosa para testing
     */
    private function simulateTestResponse($xmlContent)
    {
        // Extraer CDC del XML para la respuesta
        $cdc = 'No disponible';
        if (preg_match('/<dCDC>([^<]+)<\/dCDC>/', $xmlContent, $matches)) {
            $cdc = $matches[1];
        }
        
        return [
            'success' => true,
            'status_code' => 200,
            'response' => '<?xml version="1.0" encoding="UTF-8"?>
                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                    <soap:Body>
                        <ns2:rResEnviDE xmlns:ns2="http://ekuatia.set.gov.py/sifen/xsd">
                            <ns2:dFecProc>' . date('Y-m-d\TH:i:s') . '</ns2:dFecProc>
                            <ns2:dCodRes>0260</ns2:dCodRes>
                            <ns2:dMsgRes>Documento electrónico procesado exitosamente en ambiente de PRUEBA - Paraguay SIFEN</ns2:dMsgRes>
                            <ns2:dProtAut>' . date('Ymd') . time() . rand(1000, 9999) . '</ns2:dProtAut>
                            <ns2:dCDC>' . $cdc . '</ns2:dCDC>
                        </ns2:rResEnviDE>
                    </soap:Body>
                </soap:Envelope>',
            'xml_sent' => $xmlContent,
            'simulated' => true,
            'note' => 'Esta es una respuesta simulada para testing. En producción se requiere certificado digital válido.'
        ];
    }

    /**
     * Firma digitalmente el XML (implementación básica)
     * En producción deberías usar una librería robusta de firma digital
     */
    private function signXML($xmlContent, $config)
    {
        // Implementación básica - en producción usar XMLSecurityKey
        // Por ahora retornamos el XML sin firmar para pruebas
        return $xmlContent;
    }

    /**
     * Consulta el estado de un documento
     */
    public function queryDocumentStatus($cdc, $company)
    {
        try {
            $config = $this->configureSifen($company);
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . 'consultas/cdc/' . $cdc);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Genera el código de control del documento (CDC)
     */
    public function generateCDC($company, $data)
    {
        // Estructura del CDC según SIFEN:
        // Posiciones 1-2: Tipo de documento
        // Posiciones 3-10: RUC del emisor (sin dígito verificador)
        // Posiciones 11-13: Punto de expedición
        // Posiciones 14-20: Número del documento
        // Posiciones 21-21: Tipo de emisión (1=Normal, 2=Contingencia)
        // Posiciones 22-29: Fecha de emisión (AAAAMMDD)
        // Posiciones 30-37: Número de seguridad
        // Posiciones 38-44: Código de control (dígito verificador)

        $tipoDoc = str_pad($data['tipoDocumento'] ?? '01', 2, '0', STR_PAD_LEFT);
        
        // Obtener RUC de la empresa (sin dígito verificador)
        $rucCompleto = $company->ruc ?? '';
        $rucSinDV = substr($rucCompleto, 0, strpos($rucCompleto, '-'));
        $ruc = str_pad($rucSinDV, 8, '0', STR_PAD_LEFT);
        
        $puntoExp = str_pad($company->punto_expedicion ?? '001', 3, '0', STR_PAD_LEFT);
        $nroDoc = str_pad($data['numero'] ?? '1', 7, '0', STR_PAD_LEFT);
        $tipoEmision = '1'; // Normal
        $fecha = (new DateTime($data['fechaEmision']))->format('Ymd');
        $nroSeguridad = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        
        $base = $tipoDoc . $ruc . $puntoExp . $nroDoc . $tipoEmision . $fecha . $nroSeguridad;
        
        // Cálculo del dígito verificador (algoritmo módulo 11)
        $dv = $this->calculateMod11($base);
        
        return $base . str_pad($dv, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Calcula el dígito verificador usando módulo 11
     */
    private function calculateMod11($base)
    {
        $sum = 0;
        $weight = 2;
        
        for ($i = strlen($base) - 1; $i >= 0; $i--) {
            $sum += (int)$base[$i] * $weight;
            $weight++;
            if ($weight > 7) $weight = 2;
        }
        
        $remainder = $sum % 11;
        return $remainder < 2 ? $remainder : 11 - $remainder;
    }

    /**
     * Convierte número a letras en guaraní/español
     */
    public function numberToWords($amount)
    {
        // Implementación básica para convertir números a letras
        // En producción podrías usar una librería específica para español/guaraní
        $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
        $words = $formatter->format(floor($amount));
        
        $decimals = ($amount - floor($amount)) * 100;
        if ($decimals > 0) {
            $words .= ' con ' . str_pad($decimals, 2, '0', STR_PAD_LEFT) . '/100';
        }
        
        return strtoupper($words) . ' GUARANÍES';
    }

    /**
     * Genera reporte HTML del documento
     */
    public function generateHtmlReport($data, $company)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Factura Electrónica</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .header { text-align: center; margin-bottom: 20px; }
                .company-info { margin-bottom: 15px; }
                .document-info { border: 1px solid #000; padding: 10px; margin-bottom: 15px; }
                .items-table { width: 100%; border-collapse: collapse; }
                .items-table th, .items-table td { border: 1px solid #000; padding: 5px; text-align: right; }
                .items-table th { background-color: #f0f0f0; }
                .totals { margin-top: 15px; }
                .signature { margin-top: 30px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>FACTURA ELECTRÓNICA</h2>
                <p>SIFEN - Sistema Integrado de Facturación Electrónica</p>
            </div>
            
            <div class="company-info">
                <strong>' . ($data['emisor']['razonSocial'] ?? '') . '</strong><br>
                RUC: ' . ($data['emisor']['ruc'] ?? '') . '<br>
                Dirección: ' . ($data['emisor']['direccion'] ?? '') . '<br>
            </div>
            
            <div class="document-info">
                <strong>Documento N°:</strong> ' . ($data['serie'] ?? '001') . '-' . ($data['numeroDocumento'] ?? '') . '<br>
                <strong>Fecha:</strong> ' . (new DateTime($data['fechaEmision'] ?? 'now'))->format('d/m/Y') . '<br>
                ' . (isset($data['cdc']) ? '<strong>CDC:</strong> ' . $data['cdc'] . '<br>' : '') . '
            </div>';

        if (isset($data['receptor'])) {
            $html .= '
            <div class="company-info">
                <strong>Cliente:</strong><br>
                ' . ($data['receptor']['razonSocial'] ?? '') . '<br>
                ' . (isset($data['receptor']['ruc']) ? 'RUC: ' . $data['receptor']['ruc'] . '<br>' : '') . '
                ' . (isset($data['receptor']['direccion']) ? 'Dirección: ' . $data['receptor']['direccion'] . '<br>' : '') . '
            </div>';
        }

        $html .= '
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Cant.</th>
                        <th>Descripción</th>
                        <th>P. Unit.</th>
                        <th>Subtotal</th>
                        <th>IVA</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';

        $totalGeneral = 0;
        $totalIva = 0;

        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $subtotal = $item['cantidad'] * $item['precioUnitario'];
                $iva = 0;
                
                if (!isset($item['exentoIva']) || !$item['exentoIva']) {
                    $tasaIva = $item['tasaIva'] ?? 10;
                    $baseGravada = $subtotal / (1 + ($tasaIva / 100));
                    $iva = $subtotal - $baseGravada;
                }
                
                $totalGeneral += $subtotal;
                $totalIva += $iva;
                
                $html .= '
                    <tr>
                        <td>' . $item['cantidad'] . '</td>
                        <td style="text-align: left;">' . $item['descripcion'] . '</td>
                        <td>' . number_format($item['precioUnitario'], 0, ',', '.') . '</td>
                        <td>' . number_format($subtotal - $iva, 0, ',', '.') . '</td>
                        <td>' . number_format($iva, 0, ',', '.') . '</td>
                        <td>' . number_format($subtotal, 0, ',', '.') . '</td>
                    </tr>';
            }
        }

        $html .= '
                </tbody>
            </table>
            
            <div class="totals">
                <table style="width: 300px; margin-left: auto;">
                    <tr><td><strong>Gravadas:</strong></td><td style="text-align: right;">' . number_format($totalGeneral - $totalIva, 0, ',', '.') . '</td></tr>
                    <tr><td><strong>IVA:</strong></td><td style="text-align: right;">' . number_format($totalIva, 0, ',', '.') . '</td></tr>
                    <tr><td><strong>TOTAL:</strong></td><td style="text-align: right;"><strong>' . number_format($totalGeneral, 0, ',', '.') . '</strong></td></tr>
                </table>
                <p><strong>Son:</strong> ' . $this->numberToWords($totalGeneral) . '</p>
            </div>
            
            <div class="signature">
                <p>Documento electrónico generado por el Sistema SIFEN</p>
            </div>
        </body>
        </html>';

        return $html;
    }
}
