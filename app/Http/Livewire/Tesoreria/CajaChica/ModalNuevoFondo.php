<?php
// app/Http/Livewire/Tesoreria/CajaChica/ModalNuevoFondo.php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use App\Models\Tesoreria\CajaChica;

class ModalNuevoFondo extends Component
{
    public $mes;
    public $anio;
    public $monto;
    public $mostrarModal = false;

    protected $rules = [
        'mes' => 'required|string|max:20',
        'anio' => 'required|integer',
        'monto' => 'required|numeric|min:0',
    ];

    protected $messages = [
        'mes.required' => 'El mes es obligatorio.',
        'anio.required' => 'El año es obligatorio.',
        'anio.integer' => 'El año debe ser un número entero.',
        'monto.required' => 'El monto es obligatorio.',
        'monto.numeric' => 'El monto debe ser un número.',
        'monto.min' => 'El monto debe ser mayor o igual a cero.',
    ];

    protected $listeners = [
        'mostrarModalNuevoFondo' => 'abrirModal',
        'cerrarModalNuevoFondo' => 'cerrarModal',
    ];

    protected function formatearMonto($valor)
    {
        if (is_null($valor)) return '';
        return number_format($valor, 2, ',', '.');
    }

    protected function parsearMonto($valor)
    {
        if (empty($valor)) return null;
        // Eliminar puntos de miles y reemplazar coma decimal por punto
        return (float) str_replace(['.', ','], ['', '.'], $valor);
    }

    public function mount()
    {
        // Los valores se establecerán en abrirModal()
    }

    public function updatedMonto($value)
    {
        $this->monto = $this->formatearMonto($this->parsearMonto($value));
    }

    public function abrirModal()
    {
        // Establecer valores por defecto
        $this->mes = now()->locale('es')->translatedFormat('F');
        $this->anio = now()->year;

        // Buscar el último fondo de caja chica
        $ultimoFondo = CajaChica::orderBy('anio', 'desc')
            ->orderBy('idCajaChica', 'desc')
            ->first();

        // Si existe un fondo anterior, usar su monto, si no, dejar vacío
        $this->monto = $ultimoFondo ? $this->formatearMonto($ultimoFondo->montoCajaChica) : null;

        $this->mostrarModal = true;
        $this->dispatchBrowserEvent('actualizar-modal-fondo', ['mostrar' => true]);
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->dispatchBrowserEvent('cerrar-y-refrescar-fondo');
    }

    public function guardar()
    {
        $this->validate([
            'mes' => 'required|string|max:20',
            'anio' => 'required|integer',
            'monto' => [
                'required',
                'regex:/^\d{1,3}(\.\d{3})*(\,\d{2})?$/' // Formato: 1.234,56
            ]
        ]);

        $existe = CajaChica::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->exists();

        if ($existe) {
            // Restaurar alerta normal con sesión
            session()->flash('error', 'Ya existe un Fondo Permanente para este mes y año.');
            return;
        } else {
            CajaChica::create([
                'mes' => $this->mes,
                'anio' => $this->anio,
                'montoCajaChica' => $this->parsearMonto($this->monto),
            ]);

            session()->flash('message', 'Fondo Permanente creado correctamente.');
            $this->cerrarModal();
            // Notificar al componente principal para recargar datos
            $this->emitTo('tesoreria.caja-chica.index', 'fondoCreado');
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modal-nuevo-fondo');
    }
}
