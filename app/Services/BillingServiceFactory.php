<?php

namespace App\Services;

use App\Models\Company;
use App\Services\SunatService;
use App\Services\SifenService;
use Exception;

class BillingServiceFactory
{
    /**
     * Crea una instancia del servicio de facturación apropiado según el país
     */
    public static function create(Company $company)
    {
        $country = $company->pais ?? config('billing.default_country', 'PE');
        
        switch ($country) {
            case 'PE':
                return new SunatService();
            case 'PY':
                return new SifenService();
            default:
                throw new Exception("País no soportado: {$country}");
        }
    }

    /**
     * Obtiene la configuración del país
     */
    public static function getCountryConfig(string $country)
    {
        $config = config("billing.countries.{$country}");
        
        if (!$config) {
            throw new Exception("Configuración no encontrada para el país: {$country}");
        }
        
        return $config;
    }

    /**
     * Obtiene los tipos de documento disponibles para un país
     */
    public static function getDocumentTypes(string $country)
    {
        $config = self::getCountryConfig($country);
        return $config['document_types'] ?? [];
    }

    /**
     * Obtiene las tasas de impuestos para un país
     */
    public static function getTaxRates(string $country)
    {
        $config = self::getCountryConfig($country);
        return $config['tax_rates'] ?? [];
    }

    /**
     * Obtiene las unidades de medida para un país
     */
    public static function getUnits(string $country)
    {
        return config("billing.units.{$country}", []);
    }

    /**
     * Obtiene los endpoints para un país y servicio
     */
    public static function getEndpoints(string $country, bool $production = false)
    {
        $config = self::getCountryConfig($country);
        $environment = $production ? 'production' : 'test';
        
        return $config['endpoints'][$environment] ?? null;
    }

    /**
     * Valida si un país está soportado
     */
    public static function isCountrySupported(string $country): bool
    {
        return array_key_exists($country, config('billing.countries', []));
    }

    /**
     * Obtiene la lista de países soportados
     */
    public static function getSupportedCountries(): array
    {
        $countries = config('billing.countries', []);
        $result = [];
        
        foreach ($countries as $code => $config) {
            $result[$code] = $config['name'];
        }
        
        return $result;
    }

    /**
     * Convierte un documento del formato universal al formato específico del país
     */
    public static function adaptDocumentFormat(array $data, string $country)
    {
        switch ($country) {
            case 'PE':
                return self::adaptForPeru($data);
            case 'PY':
                return self::adaptForParaguay($data);
            default:
                return $data;
        }
    }

    /**
     * Adapta el documento para el formato de Perú (SUNAT)
     */
    private static function adaptForPeru(array $data)
    {
        // Mantener el formato actual para compatibilidad
        $adapted = $data;
        
        // Mapear campos universales a campos específicos de Perú
        if (isset($data['emisor'])) {
            $adapted['company'] = [
                'ruc' => $data['emisor']['ruc'],
                'razonSocial' => $data['emisor']['razonSocial'],
                'nombreComercial' => $data['emisor']['nombreFantasia'] ?? '',
                'address' => [
                    'direccion' => $data['emisor']['direccion'],
                    'departamento' => $data['emisor']['departamento'] ?? '',
                    'provincia' => $data['emisor']['distrito'] ?? '',
                    'distrito' => $data['emisor']['ciudad'] ?? '',
                    'ubigueo' => '150101', // Lima por defecto
                    'codLocal' => '0000'
                ]
            ];
        }

        if (isset($data['receptor'])) {
            $adapted['client'] = [
                'tipoDoc' => '6', // RUC
                'numDoc' => $data['receptor']['ruc'],
                'rznSocial' => $data['receptor']['razonSocial']
            ];
        }

        // Adaptar items
        if (isset($data['items'])) {
            $adapted['details'] = [];
            foreach ($data['items'] as $item) {
                $adapted['details'][] = [
                    'codProducto' => $item['codigo'] ?? 'PROD001',
                    'unidad' => 'NIU',
                    'cantidad' => $item['cantidad'],
                    'descripcion' => $item['descripcion'],
                    'mtoValorUnitario' => $item['precioUnitario'],
                    'mtoValorVenta' => $item['cantidad'] * $item['precioUnitario'],
                    'mtoBaseIgv' => $item['cantidad'] * $item['precioUnitario'],
                    'porcentajeIgv' => 18,
                    'igv' => ($item['cantidad'] * $item['precioUnitario']) * 0.18,
                    'tipAfeIgv' => 10, // Gravado
                    'totalImpuestos' => ($item['cantidad'] * $item['precioUnitario']) * 0.18,
                    'mtoPrecioUnitario' => $item['precioUnitario'] * 1.18
                ];
            }
        }

        return $adapted;
    }

    /**
     * Adapta el documento para el formato de Paraguay (SIFEN)
     */
    private static function adaptForParaguay(array $data)
    {
        // El formato ya está adaptado para Paraguay en SifenService
        return $data;
    }

    /**
     * Obtiene el servicio de numeración a letras apropiado
     */
    public static function getNumberToWordsService(string $country)
    {
        switch ($country) {
            case 'PE':
                return new \Luecano\NumeroALetras\NumeroALetras();
            case 'PY':
                // Para Paraguay, usar el método del SifenService
                return new SifenService();
            default:
                throw new Exception("Servicio de numeración no disponible para: {$country}");
        }
    }
}
