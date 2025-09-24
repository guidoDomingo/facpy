<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Facturación Electrónica Paraguay (SIFEN)
    |--------------------------------------------------------------------------
    |
    | Esta configuración maneja el sistema de facturación electrónica
    | de Paraguay (SIFEN e-Kuatia)
    |
    */

    'country' => 'PY', // Solo Paraguay

    'paraguay' => [
        'name' => 'Paraguay',
        'service' => 'sifen',
        'currency' => 'PYG',
        'currency_name' => 'GUARANÍES',
        'tax_name' => 'IVA',
        'tax_rates' => [
            'standard' => 10,
            'reduced' => 5,
            'exempt' => 0
        ],
        'document_types' => [
            '1' => 'Factura electrónica',
            '4' => 'Autofactura electrónica',
            '5' => 'Nota de crédito electrónica',
            '6' => 'Nota de débito electrónica',
            '7' => 'Nota de remisión electrónica'
        ],
        'endpoints' => [
            'test' => env('SIFEN_TEST_URL', 'https://sifen-test.set.gov.py/de/ws/'),
            'production' => env('SIFEN_PROD_URL', 'https://sifen.set.gov.py/de/ws/')
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración específica SIFEN Paraguay
    |--------------------------------------------------------------------------
    */

    'sifen' => [
        'endpoints' => [
            'test' => [
                'sync' => 'https://sifen-test.set.gov.py/de/ws/sync/recepcion-de',
                'async' => 'https://sifen-test.set.gov.py/de/ws/async/recepcion-de-lote',
                'query' => 'https://sifen-test.set.gov.py/de/ws/consultas',
                'events' => 'https://sifen-test.set.gov.py/de/ws/eventos'
            ],
            'production' => [
                'sync' => 'https://sifen.set.gov.py/de/ws/sync/recepcion-de',
                'async' => 'https://sifen.set.gov.py/de/ws/async/recepcion-de-lote',
                'query' => 'https://sifen.set.gov.py/de/ws/consultas',
                'events' => 'https://sifen.set.gov.py/de/ws/eventos'
            ]
        ],
        'namespaces' => [
            'sifen' => 'http://ekuatia.set.gov.py/sifen/xsd',
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance'
        ],
        'schema_location' => 'http://ekuatia.set.gov.py/sifen/xsd siRecepDE_v150.xsd'
    ],

    /*
    |--------------------------------------------------------------------------
    | Códigos de ubicación geográfica Paraguay
    |--------------------------------------------------------------------------
    */

    'departments' => [
        '11' => 'CAPITAL',
        '12' => 'SAN PEDRO',
        '13' => 'CORDILLERA',
        '14' => 'GUAIRÁ',
        '15' => 'CAAGUAZÚ',
        '16' => 'CAAZAPÁ',
        '17' => 'ITAPÚA',
        '18' => 'MISIONES',
        '19' => 'PARAGUARÍ',
        '20' => 'ALTO PARANÁ',
        '21' => 'CENTRAL',
        '22' => 'ÑEEMBUCÚ',
        '23' => 'AMAMBAY',
        '24' => 'CANINDEYÚ',
        '25' => 'PRESIDENTE HAYES',
        '26' => 'BOQUERÓN',
        '27' => 'ALTO PARAGUAY'
    ],

    /*
    |--------------------------------------------------------------------------
    | Unidades de medida Paraguay
    |--------------------------------------------------------------------------
    */

    'units' => [
        '77' => 'Unidad',
        '04' => 'Gramo',
        '05' => 'Kilogramo',
        '14' => 'Litro',
        '15' => 'Metro',
        '16' => 'Metro cuadrado',
        '17' => 'Metro cúbico',
        '26' => 'Tonelada',
        '57' => 'Caja',
        '58' => 'Paquete'
    ]
];
