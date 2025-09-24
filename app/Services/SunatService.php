<?php

namespace App\Services;

use App\Models\Company;
use Exception;

/**
 * Clase de compatibilidad - redirige todo al SifenService
 * Este proyecto ahora está adaptado SOLO para Paraguay
 */
class SunatService
{
    private $sifenService;

    public function __construct()
    {
        $this->sifenService = new SifenService();
    }

    /**
     * Método de compatibilidad - ahora usa SIFEN Paraguay
     */
    public function getSee($company)
    {
        throw new Exception('Este proyecto ahora está adaptado solo para Paraguay. Use SifenService directamente.');
    }

    /**
     * Método de compatibilidad - ahora usa SIFEN Paraguay
     */
    public function getInvoice($data)
    {
        throw new Exception('Este proyecto ahora está adaptado solo para Paraguay. Use SifenService directamente.');
    }

    /**
     * Método de compatibilidad - ahora usa SIFEN Paraguay
     */
    public function sunatResponse($result)
    {
        throw new Exception('Este proyecto ahora está adaptado solo para Paraguay. Use SifenService directamente.');
    }

    /**
     * Método de compatibilidad - ahora usa SIFEN Paraguay
     */
    public function getHtmlReport($invoice)
    {
        throw new Exception('Este proyecto ahora está adaptado solo para Paraguay. Use SifenService directamente.');
    }

    /**
     * Método de compatibilidad - ahora usa SIFEN Paraguay
     */
    public function generatePdfReport($invoice)
    {
        throw new Exception('Este proyecto ahora está adaptado solo para Paraguay. Use SifenService directamente.');
    }
}
