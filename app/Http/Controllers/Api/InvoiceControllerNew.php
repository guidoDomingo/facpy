<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Controlador de compatibilidad - redirige al SifenController
 * Este proyecto ahora está adaptado SOLO para Paraguay
 */
class InvoiceController extends Controller
{
    private $sifenController;

    public function __construct()
    {
        $this->sifenController = new SifenController(new \App\Services\SifenService());
    }

    /**
     * Redirección de compatibilidad - ahora usa SIFEN Paraguay
     */
    public function send(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Este proyecto ahora está adaptado solo para Paraguay.',
            'suggestion' => 'Use los endpoints: POST /api/invoices/send, /api/invoices/xml, /api/invoices/report',
            'documentation' => 'Consulte README-PARAGUAY.md para más información'
        ], 410); // Gone
    }

    /**
     * Redirección de compatibilidad - ahora usa SIFEN Paraguay
     */
    public function xml(Request $request)
    {
        return $this->sifenController->generateXml($request);
    }

    /**
     * Redirección de compatibilidad - ahora usa SIFEN Paraguay
     */
    public function pdf(Request $request)
    {
        return $this->sifenController->generateReport($request);
    }
}
