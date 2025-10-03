<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ElectronicDocument;
use App\Models\DocumentEvent;
use App\Models\Company;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SifenDashboardController extends Controller
{
    /**
     * Dashboard principal
     */
    public function index()
    {
        $stats = [
            'total_documents' => ElectronicDocument::count(),
            'today_documents' => ElectronicDocument::whereDate('created_at', Carbon::today())->count(),
            'pending_events' => DocumentEvent::where('status', DocumentEvent::STATUS_PENDING)->count(),
            'active_companies' => Company::whereNotNull('cert_path')->count()
        ];

        $recentDocuments = ElectronicDocument::with('company')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $documentsByStatus = ElectronicDocument::selectRaw('estado, COUNT(*) as count')
            ->groupBy('estado')
            ->get()
            ->pluck('count', 'estado');

        $eventsByType = DocumentEvent::selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->get()
            ->pluck('count', 'event_type');

        return view('sifen.dashboard', compact(
            'stats',
            'recentDocuments',
            'documentsByStatus',
            'eventsByType'
        ));
    }

    /**
     * Lista de documentos
     */
    public function documents(Request $request)
    {
        $query = ElectronicDocument::with('company');

        // Filtros
        if ($request->filled('status')) {
            $query->where('estado', $request->status);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('fecha_emision', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('fecha_emision', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('cdc', 'like', "%{$search}%")
                  ->orWhere('numero_documento', 'like', "%{$search}%")
                  ->orWhere('receptor_razon_social', 'like', "%{$search}%");
            });
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(20);
        $companies = Company::all();

        return view('sifen.documents', compact('documents', 'companies'));
    }

    /**
     * Lista de eventos
     */
    public function events(Request $request)
    {
        $query = DocumentEvent::with('electronicDocument.company');

        // Filtros
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $events = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('sifen.events', compact('events'));
    }

    /**
     * Estadísticas detalladas
     */
    public function stats()
    {
        // Documentos por día (últimos 30 días)
        $dailyStats = ElectronicDocument::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Documentos por tipo
        $documentsByType = ElectronicDocument::selectRaw('tipo_documento, COUNT(*) as count')
            ->groupBy('tipo_documento')
            ->get()
            ->map(function ($item) {
                $types = [
                    '01' => 'Factura Electrónica',
                    '04' => 'Autofactura Electrónica',
                    '05' => 'Nota de Crédito Electrónica',
                    '06' => 'Nota de Débito Electrónica',
                    '07' => 'Nota de Remisión Electrónica'
                ];
                return [
                    'type' => $types[$item->tipo_documento] ?? 'Desconocido',
                    'count' => $item->count
                ];
            });

        // Eventos por estado
        $eventsByStatus = DocumentEvent::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Empresas más activas
        $topCompanies = Company::withCount('electronicDocuments')
            ->orderBy('electronic_documents_count', 'desc')
            ->take(10)
            ->get();

        return view('sifen.stats', compact(
            'dailyStats',
            'documentsByType',
            'eventsByStatus',
            'topCompanies'
        ));
    }

    /**
     * Detalle de un documento
     */
    public function documentDetail($cdc)
    {
        $document = ElectronicDocument::with(['company', 'events'])
            ->where('cdc', $cdc)
            ->firstOrFail();

        // NCEs asociadas
        $associatedNCEs = ElectronicDocument::where('tipo_documento', ElectronicDocument::TYPE_NOTA_CREDITO)
            ->where('documento_asociado', $cdc)
            ->get();

        return view('sifen.document-detail', compact('document', 'associatedNCEs'));
    }
}
