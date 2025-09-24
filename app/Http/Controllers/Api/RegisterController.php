<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'email|required|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email, 
            'password' => bcrypt($request->password),
            'email_verified_at' => now(),
        ]);

        // Crear empresa demo para el usuario (especialmente para admin@test.com)
        if ($request->email === 'admin@test.com') {
            $company = \App\Models\Company::create([
                'user_id' => $user->id,
                'ruc' => '80123456-7',
                'razon_social' => 'Empresa Demo Paraguay SRL',
                'nombre_comercial' => 'Demo Paraguay',
                'direccion' => 'Av. Mariscal López 1234',
                'telefono' => '+595 21 123456',
                'email' => 'contacto@demopy.com',
                'departamento' => 'CAPITAL',
                'distrito' => 'ASUNCIÓN',
                'ciudad' => 'ASUNCIÓN',
                'codigo_departamento' => '01',
                'punto_expedicion' => '001',
                'actividad_economica' => 'Servicios de consultoría',
                'estado' => 'activo',
            ]);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente para Paraguay SIFEN',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'company' => isset($company) ? [
                'id' => $company->id,
                'ruc' => $company->ruc,
                'razon_social' => $company->razon_social,
            ] : null,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }
}
