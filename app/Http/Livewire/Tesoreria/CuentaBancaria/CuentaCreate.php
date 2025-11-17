<?php
// app/Http/Livewire/Tesoreria/CuentaBancaria/CuentaCreate.php
namespace App\Http\Livewire\Tesoreria\CuentaBancaria;

use App\Models\Tesoreria\Banco;
use App\Models\Tesoreria\CuentaBancaria;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class CuentaCreate extends Component
{
    public $banco_id, $numero_cuenta, $tipo, $activa = true, $observaciones;

    protected $rules = [
        'banco_id' => 'required|exists:tes_bancos,id',
        'numero_cuenta' => 'required|string|max:50|unique:tes_cuentas_bancarias,numero_cuenta',
        'tipo' => 'required|string|max:20',
        'activa' => 'boolean',
    ];

    public function mount()
    {
        $this->tipo = 'Corriente';
    }

    public function save()
    {
        $this->validate();
        CuentaBancaria::create([
            'banco_id' => $this->banco_id,
            'numero_cuenta' => $this->numero_cuenta,
            'tipo' => $this->tipo,
            'activa' => $this->activa,
            'observaciones' => $this->observaciones,
        ]);

        Cache::flush();
        $this->reset();
        $this->emit('cuentaStore');
        $this->dispatchBrowserEvent('swal', ['title' => 'Cuenta bancaria creada!', 'type' => 'success']);
    }

    public function render()
    {
        $bancos = Cache::remember('bancos_all', now()->addDay(), function () {
            return Banco::orderBy('nombre')->get();
        });
        return view('livewire.tesoreria.cuenta-bancaria.cuenta-create', compact('bancos'));
    }
}
