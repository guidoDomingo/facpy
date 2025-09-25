<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\SifenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class TestController extends Controller
{
    protected $sifenService;

    public function __construct(SifenService $sifenService)
    {
        $this->sifenService = $sifenService;
    }

    /**
     * Probar conexión con SIFEN
     */
    public function testConnection(): JsonResponse
    {
        try {
            $company = Company::first();
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay empresas registradas. Ejecute: php artisan db:seed --class=ParaguaySeeder'
                ], 400);
            }

            // Datos de prueba para factura
            $invoiceData = [
                'tipoDocumento' => '01', // Factura electrónica
                'serie' => '001',
                'numero' => '0000001',
                'fechaEmision' => now()->format('Y-m-d'),
                'receptor' => [
                    'ruc' => '80024242-1',
                    'razonSocial' => 'Cliente de Prueba SA',
                    'direccion' => 'Av. Test 123'
                ],
                'items' => [
                    [
                        'descripcion' => 'Producto de Prueba',
                        'cantidad' => 1,
                        'precioUnitario' => 100000,
                        'tipoIva' => '10',
                        'ivaMonto' => 9091
                    ]
                ],
                'totales' => [
                    'subTotal' => 90909,
                    'totalIva10' => 9091,
                    'totalIva5' => 0,
                    'totalExento' => 0,
                    'totalGeneral' => 100000
                ]
            ];

            // Generar CDC de prueba
            $cdc = $this->sifenService->generateCDC($company, $invoiceData);

            // Generar XML de prueba
            $xml = $this->sifenService->generateXML($company, $invoiceData, $cdc);

            return response()->json([
                'success' => true,
                'message' => 'Integración Paraguay SIFEN funcionando correctamente',
                'data' => [
                    'company' => [
                        'razon_social' => $company->razon_social,
                        'ruc' => $company->ruc,
                        'departamento' => $company->departamento,
                        'ciudad' => $company->ciudad
                    ],
                    'cdc' => $cdc,
                    'xml_length' => strlen($xml),
                    'sifen_endpoints' => [
                        'test' => config('billing.paraguay.endpoints.test'),
                        'production' => config('billing.paraguay.endpoints.production')
                    ],
                    'invoice_sample' => $invoiceData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la integración: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Crear empresa de prueba para el usuario autenticado
     */
    public function createTestCompany(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        $userId = $request->input('user_id');

        try {
            // Verificar si ya existe una empresa para este usuario
            $existingCompany = Company::where('user_id', $userId)->first();
            
            if ($existingCompany) {
                return response()->json([
                    'success' => true,
                    'message' => 'El usuario ya tiene una empresa asociada',
                    'company' => $existingCompany
                ]);
            }

            // Crear empresa de prueba con todos los campos requeridos
            $company = Company::create([
                'user_id' => $userId,
                'ruc' => '80123456-7',
                'razon_social' => 'Empresa Demo Paraguay SRL',
                'nombre_fantasia' => 'Demo Paraguay',
                'direccion' => 'Av. Mariscal López 1234',
                'departamento' => 'CAPITAL',
                'distrito' => 'ASUNCIÓN',
                'ciudad' => 'ASUNCIÓN',
                'codigo_departamento' => '01',
                'codigo_distrito' => '01',
                'numero_casa' => '1234',
                'punto_expedicion' => '001',
                'cert_path' => 'certs/demo_cert.pem',
                'cert_password' => 'demo_password_123',
                'logo_path' => null,
                'production' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Empresa de prueba creada exitosamente para Paraguay SIFEN',
                'company' => $company
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear empresa de prueba: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug - Ver usuarios y empresas
     */
    public function debug(): JsonResponse
    {
        try {
            $companies = Company::all();
            $users = User::all();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'companies_count' => $companies->count(),
                    'companies' => $companies,
                    'users_count' => $users->count(),
                    'users' => $users->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'created_at' => $user->created_at
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear usuario de prueba
     */
    public function createUser(): JsonResponse
    {
        try {
            // Verificar si el usuario ya existe
            $existingUser = User::where('email', 'test@example.com')->first();
            
            if ($existingUser) {
                return response()->json([
                    'success' => true,
                    'message' => 'Usuario ya existe',
                    'data' => [
                        'user_id' => $existingUser->id,
                        'name' => $existingUser->name,
                        'email' => $existingUser->email
                    ]
                ]);
            }

            // Crear nuevo usuario
            $userData = [
                'name' => 'Usuario Test Paraguay',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ];

            $user = User::create($userData);
            
            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Mostrar información del sistema
     */
    public function systemInfo(): JsonResponse
    {
        return response()->json([
            'system' => 'Facturación Electrónica Paraguay',
            'country' => 'Paraguay',
            'currency' => 'PYG',
            'tax_authority' => 'SET (Subsecretaría de Estado de Tributación)',
            'system_name' => 'SIFEN (Sistema Integrado de Facturación Electrónica Nacional)',
            'document_format' => 'e-Kuatia',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database' => [
                'connection' => config('database.default'),
                'database' => config('database.connections.mysql.database')
            ],
            'companies_count' => Company::count(),
            'environment' => config('app.env'),
            'sifen_environment' => config('billing.sifen.environment', 'test')
        ]);
    }

    /**
     * Método de prueba simple para crear usuario
     */
    public function simpleCreateUser(Request $request): JsonResponse
    {
        try {
            // Validación básica
            if (empty($request->name) || empty($request->email) || empty($request->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faltan campos requeridos: name, email, password'
                ], 400);
            }

            // Verificar si el email ya existe
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'El email ya está registrado'
                ], 409);
            }

            // Crear usuario directamente
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at
                ]
            ], 201);

        } catch (Exception $e) {
            Log::error('Error en simpleCreateUser: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Endpoint de prueba básico sin base de datos
     */
    public function basicTest(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Endpoint funcionando correctamente',
            'timestamp' => now()->toISOString(),
            'received_data' => $request->all(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ]);
    }
}
