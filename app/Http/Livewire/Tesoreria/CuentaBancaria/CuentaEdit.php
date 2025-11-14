<?php
// app/Http/Livewire/Tesoreria/CuentaBancaria/CuentaEdit.php
namespace App\Http\Livewire\Tesoreria\CuentaBancaria;

use App\Models\Tesoreria\Banco;
use App\Models\Tesoreria\CuentaBancaria;
use Livewire\Component;

class CuentaEdit extends Component
{
    public $cuentaId, $banco_id, $numero_cuenta, $tipo, $activa, $observaciones;

    protected $rules = [
        'banco_id' => 'required|exists:tes_bancos,id',
        'numero_cuenta' => 'required|string|max:50',
        'tipo' => 'required|string|max:20',
        'activa' => 'boolean',
    ];

    public function mount($cuentaId)
    {
        $cuenta = CuentaBancaria::findOrFail($cuentaId);
        $this->cuentaId = $cuenta->id;
        $this->banco_id = $cuenta->banco_id;
        $this->numero_cuenta = $cuenta->numero_cuenta;
        $this->tipo = $cuenta->tipo;
        $this->activa = $cuenta->activa;
        $this->observaciones = $cuenta->observaciones;
    }

    public function save()
    {
        $this->validate([
            'numero_cuenta' => "required|unique:tes_cuentas_bancarias,numero_cuenta,{$this->cuentaId}"
        ]);
        $cuenta = CuentaBancaria::find($this->cuentaId);
        $cuenta->update([
            'banco_id' => $this->banco_id,
            'numero_cuenta' => $this->numero_cuenta,
            'tipo' => $this->tipo,
            'activa' => $this->activa,
            'observaciones' => $this->observaciones,
        ]);

        $this->emit('cuentaUpdate');
        $this->dispatchBrowserEvent('swal', ['title' => 'Cuenta bancaria actualizada!', 'type' => 'success']);
    }

    public function render()
    {
        $bancos = Banco::orderBy('nombre')->get();
        return view('livewire.tesoreria.cuenta-bancaria.cuenta-edit', compact('bancos'));
    }
}
