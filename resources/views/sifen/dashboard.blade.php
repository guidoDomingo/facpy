@extends('layouts.sifen')

@section('title', 'Dashboard SIFEN Paraguay')
@section('page-title', 'Dashboard Principal')

@section('content')
<div class="row">
    <!-- Estadísticas principales -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-number">{{ number_format($stats['total_documents']) }}</div>
                <div>Total Documentos</div>
                <small class="opacity-75">
                    <i class="fas fa-file-alt"></i> Todos los tipos
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-number">{{ number_format($stats['today_documents']) }}</div>
                <div>Documentos Hoy</div>
                <small class="opacity-75">
                    <i class="fas fa-calendar-day"></i> {{ date('d/m/Y') }}
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-number">{{ number_format($stats['pending_events']) }}</div>
                <div>Eventos Pendientes</div>
                <small class="opacity-75">
                    <i class="fas fa-clock"></i> Requieren atención
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-number">{{ number_format($stats['active_companies']) }}</div>
                <div>Empresas Activas</div>
                <small class="opacity-75">
                    <i class="fas fa-building"></i> Con certificado
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Documentos por estado -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2"></i>Documentos por Estado
            </div>
            <div class="card-body">
                <canvas id="statusChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Eventos por tipo -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-bar me-2"></i>Eventos por Tipo
            </div>
            <div class="card-body">
                <canvas id="eventsChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Documentos recientes -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-file-alt me-2"></i>Documentos Recientes</span>
                <a href="{{ route('sifen.documents') }}" class="btn btn-sm btn-outline-primary">
                    Ver todos <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>CDC</th>
                                <th>Tipo</th>
                                <th>Empresa</th>
                                <th>Receptor</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentDocuments as $document)
                            <tr>
                                <td>
                                    <code class="small">{{ substr($document->cdc, 0, 12) }}...</code>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $document->tipo_documento_name }}
                                    </span>
                                </td>
                                <td>{{ $document->company->razon_social ?? 'N/A' }}</td>
                                <td>{{ $document->receptor_razon_social ?? 'N/A' }}</td>
                                <td>{{ number_format($document->total_documento, 0) }} Gs.</td>
                                <td>
                                    <span class="status-badge bg-{{ $document->estado_css_class }}">
                                        {{ $document->estado_formatted }}
                                    </span>
                                </td>
                                <td>{{ $document->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('sifen.document.detail', $document->cdc) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    No hay documentos recientes
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Gráfico de documentos por estado
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_keys($documentsByStatus->toArray())) !!},
        datasets: [{
            data: {!! json_encode(array_values($documentsByStatus->toArray())) !!},
            backgroundColor: [
                '#28a745', // aprobado
                '#ffc107', // pendiente
                '#17a2b8', // enviado
                '#dc3545', // rechazado
                '#6c757d', // cancelado
                '#fd7e14'  // error
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfico de eventos por tipo
const eventsCtx = document.getElementById('eventsChart').getContext('2d');
const eventsChart = new Chart(eventsCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode(array_keys($eventsByType->toArray())) !!},
        datasets: [{
            label: 'Cantidad',
            data: {!! json_encode(array_values($eventsByType->toArray())) !!},
            backgroundColor: '#667eea'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
@endsection
