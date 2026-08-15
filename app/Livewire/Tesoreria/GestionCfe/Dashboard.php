<?php

namespace App\Livewire\Tesoreria\GestionCfe;

use App\Services\Tesoreria\DashboardService;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Componente Livewire para Dashboard de KPIs de Recaudaciones
 * 
 * Características:
 * - Filtros de fecha: Hoy, Esta Semana, Este Mes, Este Año, Rango personalizado
 * - 8 KPIs principales con gráficos interactivos
 * - Panel de alertas
 * - Diseño responsive
 */
class Dashboard extends Component
{
    public $filtroSeleccionado = 'mes'; // hoy, semana, mes, ano, personalizado
    public $fechaInicio;
    public $fechaFin;
    public $fechaInicioCustom;
    public $fechaFinCustom;

    protected DashboardService $dashboardService;

    public function boot(DashboardService $dashboardService): void
    {
        $this->dashboardService = $dashboardService;
    }

    public function mount(): void
    {
        // Default: Este Mes
        $this->aplicarFiltroMes();
    }

    /**
     * Aplica el filtro "Hoy"
     */
    public function filtrarHoy(): void
    {
        $this->filtroSeleccionado = 'hoy';
        $hoy = Carbon::today();
        $this->fechaInicio = $hoy->format('Y-m-d');
        $this->fechaFin = $hoy->format('Y-m-d');
    }

    /**
     * Aplica el filtro "Esta Semana"
     */
    public function filtrarSemana(): void
    {
        $this->filtroSeleccionado = 'semana';
        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();
        $this->fechaInicio = $inicioSemana->format('Y-m-d');
        $this->fechaFin = $finSemana->format('Y-m-d');
    }

    /**
     * Aplica el filtro "Este Mes"
     */
    public function filtrarMes(): void
    {
        $this->aplicarFiltroMes();
    }

    /**
     * Método privado para aplicar filtro de mes
     */
    private function aplicarFiltroMes(): void
    {
        $this->filtroSeleccionado = 'mes';
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $this->fechaInicio = $inicioMes->format('Y-m-d');
        $this->fechaFin = $finMes->format('Y-m-d');
    }

    /**
     * Aplica el filtro "Mes Anterior"
     */
    public function filtrarMesAnterior(): void
    {
        $this->filtroSeleccionado = 'mes_anterior';
        $inicio = Carbon::now()->subMonth()->startOfMonth();
        $fin = Carbon::now()->subMonth()->endOfMonth();
        $this->fechaInicio = $inicio->format('Y-m-d');
        $this->fechaFin = $fin->format('Y-m-d');
    }

    /**
     * Aplica el filtro "Últimos 30 Días"
     */
    public function filtrar30Dias(): void
    {
        $this->filtroSeleccionado = '30dias';
        $inicio = Carbon::now()->subDays(29)->startOfDay();
        $fin = Carbon::now()->endOfDay();
        $this->fechaInicio = $inicio->format('Y-m-d');
        $this->fechaFin = $fin->format('Y-m-d');
    }

    /**
     * Aplica el filtro "Este Año"
     */
    public function filtrarAno(): void
    {
        $this->filtroSeleccionado = 'ano';
        $inicioAno = Carbon::now()->startOfYear();
        $finAno = Carbon::now()->endOfYear();
        $this->fechaInicio = $inicioAno->format('Y-m-d');
        $this->fechaFin = $finAno->format('Y-m-d');
    }

    /**
     * Aplica el filtro personalizado
     */
    public function aplicarFiltroPersonalizado(): void
    {
        $this->validate([
            'fechaInicioCustom' => 'required|date',
            'fechaFinCustom' => 'required|date|after_or_equal:fechaInicioCustom',
        ], [
            'fechaInicioCustom.required' => 'La fecha de inicio es requerida.',
            'fechaInicioCustom.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fechaFinCustom.required' => 'La fecha de fin es requerida.',
            'fechaFinCustom.date' => 'La fecha de fin debe ser una fecha válida.',
            'fechaFinCustom.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ]);

        $this->filtroSeleccionado = 'personalizado';
        $this->fechaInicio = $this->fechaInicioCustom;
        $this->fechaFin = $this->fechaFinCustom;

        $this->dispatch('swal:toast-success', [
            'text' => 'Filtro personalizado aplicado correctamente.',
        ]);
    }

    /**
     * Limpia los filtros y vuelve al default (Este Mes)
     */
    public function limpiarFiltros(): void
    {
        $this->fechaInicioCustom = null;
        $this->fechaFinCustom = null;
        $this->aplicarFiltroMes();
    }

    /**
     * Renderiza el componente
     */
    public function render()
    {
        try {
            $carbonInicio = Carbon::parse($this->fechaInicio);
            $carbonFin = Carbon::parse($this->fechaFin);

            // Obtener todos los KPIs del servicio
            $kpis = $this->dashboardService->getAllKPIs($carbonInicio, $carbonFin);

            // Formatear fechas para mostrar
            $periodoTexto = $this->getPeriodoTexto($carbonInicio, $carbonFin);

            return view('livewire.tesoreria.gestion-cfe.dashboard', [
                'kpis' => $kpis,
                'periodoTexto' => $periodoTexto,
                'carbonInicio' => $carbonInicio,
                'carbonFin' => $carbonFin,
            ])
            ->extends('layouts.app')
            ->section('content');

        } catch (\Exception $e) {
            \Log::error('Error en Dashboard de Recaudaciones', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('swal:toast-error', [
                'text' => 'Error al cargar el dashboard. Intente nuevamente.',
            ]);

            // Valores por defecto en caso de error
            $kpis = [
                'total_recaudado' => ['total_general' => 0, 'desglose' => [], 'promedio_diario' => 0, 'dias_periodo' => 1],
                'planillas_pendientes' => ['count' => 0, 'planillas' => []],
                'items_sin_asignar' => ['count' => 0, 'monto_total' => 0, 'antiguos' => 0, 'alerta' => false, 'items_recientes' => []],
                'recaudacion_por_tipo_siif' => [],
                'recaudacion_por_dependencia' => [],
                'comparativa' => ['actual' => 0, 'anterior' => 0, 'diferencia' => 0, 'porcentaje' => 0, 'porcentaje_display' => '0%', 'tendencia' => 'up'],
                'alertas' => [],
            ];

            return view('livewire.tesoreria.gestion-cfe.dashboard', [
                'kpis' => $kpis,
                'periodoTexto' => 'Error al cargar período',
                'carbonInicio' => Carbon::today(),
                'carbonFin' => Carbon::today(),
            ])
            ->extends('layouts.app')
            ->section('content');
        }
    }

    /**
     * Genera texto descriptivo del período seleccionado
     * 
     * @param Carbon $fechaInicio
     * @param Carbon $fechaFin
     * @return string
     */
    private function getPeriodoTexto(Carbon $fechaInicio, Carbon $fechaFin): string
    {
        switch ($this->filtroSeleccionado) {
            case 'hoy':
                return 'Hoy - ' . $fechaInicio->format('d/m/Y');
            
            case 'semana':
                return 'Esta Semana - ' . $fechaInicio->format('d/m/Y') . ' al ' . $fechaFin->format('d/m/Y');
            
            case 'mes':
                return 'Este Mes - ' . $fechaInicio->locale('es')->isoFormat('MMMM Y');

            case 'mes_anterior':
                return 'Mes Anterior - ' . $fechaInicio->locale('es')->isoFormat('MMMM Y');

            case '30dias':
                return 'Últimos 30 Días - ' . $fechaInicio->format('d/m/Y') . ' al ' . $fechaFin->format('d/m/Y');

            case 'ano':
                return 'Este Año - ' . $fechaInicio->format('Y');
            
            case 'personalizado':
                return 'Período Personalizado - ' . $fechaInicio->format('d/m/Y') . ' al ' . $fechaFin->format('d/m/Y');
            
            default:
                return $fechaInicio->format('d/m/Y') . ' al ' . $fechaFin->format('d/m/Y');
        }
    }

    /**
     * Exporta datos del dashboard a JSON para gráficos
     * 
     * @return string
     */
    public function getDashboardDataJson(): string
    {
        try {
            $carbonInicio = Carbon::parse($this->fechaInicio);
            $carbonFin = Carbon::parse($this->fechaFin);
            $kpis = $this->dashboardService->getAllKPIs($carbonInicio, $carbonFin);
            
            return json_encode($kpis, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return json_encode(['error' => true]);
        }
    }
}
