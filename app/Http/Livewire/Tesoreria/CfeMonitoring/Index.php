<?php

namespace App\Http\Livewire\Tesoreria\CfeMonitoring;

use App\Models\TesCfePendiente;
use Livewire\Component;

class Index extends Component
{
    public string $periodo = '24h';
    public array $stats = [];
    public array $errores = [];
    public array $tendencia = [];

    public function mount(): void
    {
        $this->cargarStats();
    }

    public function cambiarPeriodo(string $periodo): void
    {
        $this->periodo = $periodo;
        $this->cargarStats();
    }

    public function cargarStats(): void
    {
        $horas = match ($this->periodo) {
            '7d' => 168,
            '30d' => 720,
            default => 24,
        };
        $since = now()->subHours($horas);

        $query = TesCfePendiente::where('created_at', '>=', $since);
        $total = (clone $query)->count();

        $tipos = (clone $query)
            ->selectRaw('tipo_cfe, COUNT(*) as total')
            ->groupBy('tipo_cfe')
            ->pluck('total', 'tipo_cfe')
            ->toArray();

        $porEstado = (clone $query)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $this->stats = [
            'total' => $total,
            'confirmados' => $porEstado['confirmado'] ?? 0,
            'rechazados' => $porEstado['rechazado'] ?? 0,
            'pendientes' => ($porEstado['pendiente'] ?? 0) + ($porEstado['en_revision'] ?? 0),
            'expirados' => $porEstado['expirado'] ?? 0,
            'tasa_exito' => $total > 0 ? round((($porEstado['confirmado'] ?? 0) / $total) * 100, 1) : 0,
            'por_tipo' => $tipos,
            'por_estado' => $porEstado,
        ];

        $this->tendencia = [];
        for ($i = 6; $i >= 0; $i--) {
            $dia = now()->subDays($i)->startOfDay();
            $count = TesCfePendiente::where('created_at', '>=', $dia)
                ->where('created_at', '<', (clone $dia)->addDay())
                ->count();
            $this->tendencia[$dia->format('d/m')] = $count;
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.cfe-monitoring.index')
            ->extends('layouts.app')
            ->section('content');
    }
}
