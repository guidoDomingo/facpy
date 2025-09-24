<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\SifenController;
use App\Http\Controllers\Api\TestController;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('register', [RegisterController::class, 'store']);

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('refresh', [AuthController::class, 'refresh']);
Route::post('me', [AuthController::class, 'me']);

Route::apiResource('companies', CompanyController::class)
    ->middleware('auth:api');

// Rutas de prueba (sin autenticación para testing)
Route::get('test/connection', [TestController::class, 'testConnection']);
Route::get('test/system', [TestController::class, 'systemInfo']);
Route::get('test/debug', [TestController::class, 'debug']);
Route::post('test/create-company', [TestController::class, 'createTestCompany']);
Route::post('test/create-user', [TestController::class, 'createUser']);
Route::post('test/simple-create-user', [TestController::class, 'simpleCreateUser']);
Route::post('test/basic', [TestController::class, 'basicTest']);
Route::get('test/sifen-test', [SifenController::class, 'testRoute']);
Route::post('test/xml-without-auth', [SifenController::class, 'generateXmlTest']);

// Rutas principales de facturación electrónica (Paraguay SIFEN)
Route::middleware('auth:api')->group(function () {
    Route::post('invoices/send', [SifenController::class, 'sendInvoice']);
    Route::post('invoices/xml', [SifenController::class, 'generateXml']);
    Route::post('invoices/report', [SifenController::class, 'generateReport']);
    Route::post('invoices/status', [SifenController::class, 'queryStatus']);
    Route::get('invoices/config', [SifenController::class, 'getConfig']);
});