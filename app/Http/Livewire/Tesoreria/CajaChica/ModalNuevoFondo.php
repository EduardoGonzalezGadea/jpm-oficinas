<?php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use App\Models\Tesoreria\CajaChica;

class ModalNuevoFondo extends Component
{
    public $show = false;
    public $mes;
    public $anio;
    public $monto;

    protected $rules = [
        'mes' => 'required|string|max:20',
        'anio' => 'required|integer',
        'monto' => 'required|numeric|min:0',
    ];

    public function mount()
    {
        $this->mes = now()->locale('es_ES')->isoFormat('MMMM');
        $this->anio = now()->year;
        $this->monto = 0;
    }

    public function guardar()
    {
        $this->validate();

        $existe = CajaChica::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->exists();

        if ($existe) {
            session()->flash('error', 'Ya existe un Fondo Permanente para este mes y año.');
        } else {
            CajaChica::create([
                'mes' => $this->mes,
                'anio' => $this->anio,
                'montoCajaChica' => $this->monto,
            ]);
            $this->dispatch('cerrarModalNuevoFondo');
            $this->dispatch('fondoCreado'); // Evento para refrescar datos en Index
            session()->flash('message', 'Fondo Permanente creado correctamente.');
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modal-nuevo-fondo');
    }
}
