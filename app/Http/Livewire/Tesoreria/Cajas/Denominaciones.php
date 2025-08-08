<?php

namespace App\Http\Livewire\Tesoreria\Cajas;

use Livewire\Component;
use App\Models\Tesoreria\Cajas\Denominacion;
use Illuminate\Validation\Rule;

class Denominaciones extends Component
{
    public $valor;
    public $tipo = 'BILLETE';
    public $activo = true;
    public $orden;
    public $denominacionId;
    public $modo = 'crear';

    protected $rules = [
        'valor' => 'required|numeric|min:0.01|max:10000',
        'tipo' => 'required|in:BILLETE,MONEDA',
        'activo' => 'boolean',
        'orden' => 'required|integer|min:0'
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset(['valor', 'tipo', 'activo', 'orden', 'denominacionId']);
        $this->modo = 'crear';
        $this->orden = Denominacion::max('orden') + 1;
    }

    public function editar($id)
    {
        $denominacion = Denominacion::find($id);
        $this->denominacionId = $denominacion->idDenominacion;
        $this->valor = $denominacion->valor;
        $this->tipo = $denominacion->tipo;
        $this->activo = $denominacion->activo;
        $this->orden = $denominacion->orden;
        $this->modo = 'editar';
    }

    public function guardar()
    {
        $this->validate();

        if ($this->modo === 'crear') {
            Denominacion::create([
                'valor' => $this->valor,
                'tipo' => $this->tipo,
                'activo' => $this->activo,
                'orden' => $this->orden
            ]);

            session()->flash('message', 'Denominación creada correctamente.');
        } else {
            $denominacion = Denominacion::find($this->denominacionId);
            $denominacion->update([
                'valor' => $this->valor,
                'tipo' => $this->tipo,
                'activo' => $this->activo,
                'orden' => $this->orden
            ]);

            session()->flash('message', 'Denominación actualizada correctamente.');
        }

        $this->resetForm();
        $this->emit('denominacionesActualizadas');
    }

    public function toggleEstado($id)
    {
        $denominacion = Denominacion::find($id);
        $denominacion->activo = !$denominacion->activo;
        $denominacion->save();
    }

    public function render()
    {
        return view('livewire.tesoreria.cajas.denominaciones', [
            'billetes' => Denominacion::where('tipo', 'BILLETE')->orderBy('orden')->get(),
            'monedas' => Denominacion::where('tipo', 'MONEDA')->orderBy('orden')->get()
        ]);
    }
}
