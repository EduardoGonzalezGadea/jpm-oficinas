<?php

namespace App\Http\Livewire\Sistema\Auditoria;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filtros de búsqueda
    public $search = '';
    public $logName = '';
    public $event = '';
    public $causerId = '';
    public $subjectType = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 25;

    // Para los selectores
    public $logNames = [];
    public $events = [];
    public $subjectTypes = [];
    public $users = [];

    // Modal de detalle
    public $showDetailModal = false;
    public $selectedActivity = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'logName' => ['except' => ''],
        'event' => ['except' => ''],
        'causerId' => ['except' => ''],
        'subjectType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount()
    {
        // Verificar permisos
        $user = auth()->user();
        if (!$user->hasAnyRole(['administrador', 'gerente_tesoreria', 'supervisor_tesoreria'])) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        $this->loadFilterOptions();

        // Establecer fecha por defecto (últimos 30 días)
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function loadFilterOptions()
    {
        // Obtener log_names únicos
        $this->logNames = Activity::select('log_name')
            ->distinct()
            ->whereNotNull('log_name')
            ->orderBy('log_name')
            ->pluck('log_name')
            ->toArray();

        // Obtener eventos únicos
        $this->events = Activity::select('event')
            ->distinct()
            ->whereNotNull('event')
            ->orderBy('event')
            ->pluck('event')
            ->toArray();

        // Obtener tipos de sujeto únicos
        $this->subjectTypes = Activity::select('subject_type')
            ->distinct()
            ->whereNotNull('subject_type')
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->map(function ($type) {
                return [
                    'value' => $type,
                    'label' => class_basename($type)
                ];
            })
            ->toArray();

        // Obtener usuarios que han causado actividades
        $this->users = Activity::select('causer_id')
            ->distinct()
            ->whereNotNull('causer_id')
            ->with('causer')
            ->get()
            ->filter(function ($activity) {
                return $activity->causer !== null;
            })
            ->map(function ($activity) {
                return [
                    'id' => $activity->causer_id,
                    'name' => $activity->causer->nombre_completo ?? 'Usuario #' . $activity->causer_id
                ];
            })
            ->unique('id')
            ->values()
            ->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingLogName()
    {
        $this->resetPage();
    }

    public function updatingEvent()
    {
        $this->resetPage();
    }

    public function updatingCauserId()
    {
        $this->resetPage();
    }

    public function updatingSubjectType()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->logName = '';
        $this->event = '';
        $this->causerId = '';
        $this->subjectType = '';
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->resetPage();
    }

    public function showDetail($activityId)
    {
        $this->selectedActivity = Activity::with('causer', 'subject')->find($activityId);
        $this->showDetailModal = true;
        $this->dispatchBrowserEvent('show-detail-modal');
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedActivity = null;
    }

    public function getEventBadgeClass($event)
    {
        return match ($event) {
            'created' => 'badge-success',
            'updated' => 'badge-warning',
            'deleted' => 'badge-danger',
            'restored' => 'badge-info',
            'login' => 'badge-primary',
            'logout' => 'badge-secondary',
            default => 'badge-light',
        };
    }

    public function getEventLabel($event)
    {
        return match ($event) {
            'created' => 'Creado',
            'updated' => 'Actualizado',
            'deleted' => 'Eliminado',
            'restored' => 'Restaurado',
            'login' => 'Inicio de Sesión',
            'logout' => 'Cierre de Sesión',
            default => ucfirst($event ?? 'N/A'),
        };
    }

    public function getSubjectLabel($subjectType)
    {
        if (!$subjectType) return 'N/A';

        $labels = [
            'App\\Models\\User' => 'Usuario',
            'App\\Models\\Tesoreria\\Cheque' => 'Cheque',
            'App\\Models\\Tesoreria\\CertificadoResidencia' => 'Certificado de Residencia',
            'App\\Models\\Tesoreria\\CajaChica' => 'Caja Chica',
            'App\\Models\\Tesoreria\\Pendiente' => 'Pendiente',
            'App\\Models\\Tesoreria\\Pago' => 'Pago',
            'App\\Models\\Tesoreria\\Movimiento' => 'Movimiento',
            'App\\Models\\Tesoreria\\Arrendamiento' => 'Arrendamiento',
            'App\\Models\\Tesoreria\\TesPorteArmas' => 'Porte de Armas',
            'App\\Models\\Tesoreria\\TesTenenciaArmas' => 'Tenencia de Armas',
            'App\\Models\\Tesoreria\\Prenda' => 'Prenda',
            'App\\Models\\Tesoreria\\DepositoVehiculo' => 'Depósito de Vehículo',
            'App\\Models\\Tesoreria\\Eventual' => 'Eventual',
            'App\\Models\\Tesoreria\\Valor' => 'Valor',
        ];

        return $labels[$subjectType] ?? class_basename($subjectType);
    }

    public function render()
    {
        $query = Activity::query()
            ->with(['causer', 'subject']);

        // Filtro por búsqueda general
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                    ->orWhere('properties', 'like', '%' . $this->search . '%');
            });
        }

        // Filtro por log_name
        if (!empty($this->logName)) {
            $query->where('log_name', $this->logName);
        }

        // Filtro por evento
        if (!empty($this->event)) {
            $query->where('event', $this->event);
        }

        // Filtro por usuario que causó el cambio
        if (!empty($this->causerId)) {
            $query->where('causer_id', $this->causerId);
        }

        // Filtro por tipo de sujeto
        if (!empty($this->subjectType)) {
            $query->where('subject_type', $this->subjectType);
        }

        // Filtro por rango de fechas
        if (!empty($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        // Ordenar por fecha descendente
        $activities = $query->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        $totalRegistros = $query->count();

        return view('livewire.sistema.auditoria.index', [
            'activities' => $activities,
            'totalRegistros' => $totalRegistros,
        ])->extends('layouts.app');
    }
}
