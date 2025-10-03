<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SIFEN Paraguay Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el Sistema de Facturación Electrónica (SIFEN)
    | del Paraguay. Estas configuraciones son necesarias para la integración
    | con los servicios web de la SET.
    |
    */

    'environments' => [
        'test' => [
            'url' => env('SIFEN_TEST_URL', 'https://sifen-test.set.gov.py/de/ws/'),
            'name' => 'Ambiente de Pruebas',
            'description' => 'Utilizado para pruebas y homologación'
        ],
        'production' => [
            'url' => env('SIFEN_PROD_URL', 'https://sifen.set.gov.py/de/ws/'),
            'name' => 'Ambiente de Producción',
            'description' => 'Ambiente productivo oficial'
        ]
    ],

    'default_environment' => env('SIFEN_DEFAULT_ENV', 'test'),

    /*
    |--------------------------------------------------------------------------
    | Web Services Configuration
    |--------------------------------------------------------------------------
    */

    'web_services' => [
        'siRecepDE' => [
            'endpoint' => 'sync/siRecepDE.wsdl',
            'description' => 'Recepción de documentos electrónicos individuales',
            'timeout' => env('SIFEN_TIMEOUT', 30),
        ],
        'siRecepLoteDE' => [
            'endpoint' => 'sync/siRecepLoteDE.wsdl',
            'description' => 'Recepción de lotes de documentos electrónicos',
            'timeout' => env('SIFEN_TIMEOUT', 60),
        ],
        'siResultLoteDE' => [
            'endpoint' => 'sync/siResultLoteDE.wsdl',
            'description' => 'Consulta de resultados de lotes',
            'timeout' => env('SIFEN_TIMEOUT', 30),
        ],
        'siConsDE' => [
            'endpoint' => 'sync/siConsDE.wsdl',
            'description' => 'Consulta de documentos electrónicos',
            'timeout' => env('SIFEN_TIMEOUT', 30),
        ],
        'siConsRUC' => [
            'endpoint' => 'sync/siConsRUC.wsdl',
            'description' => 'Consulta de datos de RUC',
            'timeout' => env('SIFEN_TIMEOUT', 30),
        ],
        'siRecepEvento' => [
            'endpoint' => 'sync/siRecepEvento.wsdl',
            'description' => 'Recepción de eventos',
            'timeout' => env('SIFEN_TIMEOUT', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Configuration
    |--------------------------------------------------------------------------
    */

    'documents' => [
        'xml_version' => '150',
        'cdc_length' => 44,
        'max_batch_size' => env('SIFEN_BATCH_SIZE', 15),
        'max_retry_attempts' => env('SIFEN_MAX_RETRY', 3),
        
        'types' => [
            '01' => 'Factura Electrónica',
            '04' => 'Autofactura Electrónica',
            '05' => 'Nota de Crédito Electrónica',
            '06' => 'Nota de Débito Electrónica',
            '07' => 'Nota de Remisión Electrónica',
            '08' => 'Comprobante de Retención Electrónico',
        ],

        'status' => [
            'draft' => 'Borrador',
            'signed' => 'Firmado',
            'sent' => 'Enviado',
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            'cancelled' => 'Cancelado',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Configuration
    |--------------------------------------------------------------------------
    */

    'events' => [
        'types' => [
            '701' => 'Cancelación',
            '702' => 'Inutilización',
            '703' => 'Conformidad',
            '704' => 'Disconformidad',
            '705' => 'Desconocimiento',
            '706' => 'No recibido',
        ],

        'motives' => [
            'cancellation' => [
                'E401' => 'Error en datos del emisor',
                'E402' => 'Error en datos del receptor',
                'E403' => 'Error en datos de la operación',
                'E404' => 'Error en cálculos',
                'E405' => 'Documento duplicado',
                'E406' => 'Otros errores',
            ],
            'inutilization' => [
                'E501' => 'Error en la numeración',
                'E502' => 'Falla en el sistema',
                'E503' => 'Otros motivos técnicos',
            ]
        ],

        'time_limit_hours' => 48,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */

    'security' => [
        'certificate' => [
            'default_path' => 'storage/certificates/certificate.p12',
            'required_format' => 'PKCS#12',
            'min_key_length' => 2048,
        ],

        'xml_signature' => [
            'canonicalization' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
            'signature_method' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
            'digest_method' => 'http://www.w3.org/2001/04/xmlenc#sha256',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | QR Code Configuration
    |--------------------------------------------------------------------------
    */

    'qr_code' => [
        'size' => 300,
        'margin' => 10,
        'encoding' => 'UTF-8',
        'error_correction' => 'medium', // low, medium, quartile, high
        'format' => 'png',
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Configuration
    |--------------------------------------------------------------------------
    */

    'pdf' => [
        'format' => 'A4',
        'orientation' => 'P', // P = Portrait, L = Landscape
        'unit' => 'mm',
        'unicode' => true,
        'encoding' => 'UTF-8',
        'margins' => [
            'left' => 15,
            'top' => 27,
            'right' => 15,
            'bottom' => 25,
        ],
        'font' => [
            'family' => 'helvetica',
            'size' => 10,
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'ttl' => [
            'ruc_data' => 3600, // 1 hora
            'certificate_info' => 86400, // 24 horas
            'service_status' => 300, // 5 minutos
        ],
        'keys' => [
            'ruc_prefix' => 'sifen_ruc_',
            'cert_prefix' => 'sifen_cert_',
            'service_prefix' => 'sifen_service_',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('SIFEN_LOGGING_ENABLED', true),
        'level' => env('SIFEN_LOG_LEVEL', 'info'),
        'channels' => ['sifen'],
        'include_request_data' => env('SIFEN_LOG_REQUESTS', true),
        'include_response_data' => env('SIFEN_LOG_RESPONSES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paraguay Geographic Codes
    |--------------------------------------------------------------------------
    */

    'geographic_codes' => [
        'departments' => [
            '11' => 'Alto Paraguay',
            '12' => 'Alto Paraná',
            '13' => 'Amambay',
            '14' => 'Boquerón',
            '15' => 'Caaguazú',
            '16' => 'Caazapá',
            '17' => 'Canindeyú',
            '01' => 'Concepción',
            '02' => 'San Pedro',
            '03' => 'Cordillera',
            '04' => 'Guairá',
            '05' => 'Caaguazú',
            '06' => 'Caazapá',
            '07' => 'Itapúa',
            '08' => 'Misiones',
            '09' => 'Paraguarí',
            '10' => 'Central',
            '18' => 'Presidente Hayes',
            '19' => 'Asunción',
        ],
        'default_department' => '11',
        'default_district' => '1',
        'default_city' => '1',
    ]
];
