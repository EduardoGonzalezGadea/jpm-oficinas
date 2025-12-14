<?php
// app/Http/Livewire/Tesoreria/Cheque/ChequeLibreta.php
namespace App\Http\Livewire\Tesoreria\Cheque;

use App\Models\Tesoreria\Cheque;
use App\Models\Tesoreria\CuentaBancaria;
use Livewire\Component;

class ChequeLibreta extends Component
{
    public $cuenta_bancaria_id, $inicio, $cantidad_libretas, $numero_final, $serie;
    public $cuentas;

    protected $listeners = [
        'refreshLibreta' => '$refresh'
    ];

    public function mount()
    {
        $this->cuentas = CuentaBancaria::where('activa', true)->orderBy('id')->get();
        if ($this->cuentas->isNotEmpty()) {
            $this->cuenta_bancaria_id = $this->cuentas->first()->id;
        }
    }

    protected $rules = [
        'cuenta_bancaria_id' => 'required|exists:tes_cuentas_bancarias,id',
        'inicio' => 'required|numeric|min:1',
        'cantidad_libretas' => 'required|numeric|min:1',
        'serie' => 'nullable|string|max:11',
    ];

    public function getNumeroFinalProperty()
    {
        if ($this->inicio && $this->cantidad_libretas) {
            return $this->inicio + ($this->cantidad_libretas * 25) - 1;
        }
        return null;
    }

    public function updatedInicio()
    {
        $this->calcularNumeroFinal();
    }

    public function updatedCantidadLibretas()
    {
        $this->calcularNumeroFinal();
    }

    private function calcularNumeroFinal()
    {
        if ($this->inicio && $this->cantidad_libretas) {
            $this->numero_final = $this->inicio + ($this->cantidad_libretas * 25) - 1;
        } else {
            $this->numero_final = null;
        }
    }

    public function saveDirect()
    {
        $this->validate();

        if (($this->inicio - 1) % 25 !== 0) {
            $this->addError('inicio', 'El número inicial debe ser 1, 26, 51, 76, etc. (múltiplo de 25 + 1).');
            return;
        }

        $numero_final = $this->numero_final;
        $existingCheques = Cheque::where('cuenta_bancaria_id', $this->cuenta_bancaria_id)
            ->whereBetween('numero_cheque', [
                str_pad($this->inicio, 8, '0', STR_PAD_LEFT),
                str_pad($numero_final, 8, '0', STR_PAD_LEFT)
            ])
            ->exists();

        if ($existingCheques) {
            $this->addError('inicio', 'Ya existen cheques registrados en este rango para esta cuenta bancaria.');
            return;
        }

        for ($i = $this->inicio; $i <= $numero_final; $i++) {
            Cheque::create([
                'cuenta_bancaria_id' => $this->cuenta_bancaria_id,
                'numero_cheque' => str_pad($i, 8, '0', STR_PAD_LEFT),
                'estado' => 'disponible',
                'serie' => $this->serie,
            ]);
        }

        $this->reset(['inicio', 'cantidad_libretas', 'numero_final', 'serie']);
        $this->dispatchBrowserEvent('swal', ['title' => 'Libreta registrada!', 'type' => 'success']);
        // Close modal after success
        $this->emit('close-modal', 'modalIngresoCheque');
    }

    public function save()
    {
        $this->validate();

        if (($this->inicio - 1) % 25 !== 0) {
            $this->addError('inicio', 'El número inicial debe ser 1, 26, 51, 76, etc. (múltiplo de 25 + 1).');
            return;
        }

        $numero_final = $this->numero_final;
        $existingCheques = Cheque::where('cuenta_bancaria_id', $this->cuenta_bancaria_id)
            ->whereBetween('numero_cheque', [
                str_pad($this->inicio, 8, '0', STR_PAD_LEFT),
                str_pad($numero_final, 8, '0', STR_PAD_LEFT)
            ])
            ->exists();

        if ($existingCheques) {
            $this->addError('inicio', 'Ya existen cheques registrados en este rango para esta cuenta bancaria.');
            return;
        }

        for ($i = $this->inicio; $i <= $numero_final; $i++) {
            Cheque::create([
                'cuenta_bancaria_id' => $this->cuenta_bancaria_id,
                'numero_cheque' => str_pad($i, 8, '0', STR_PAD_LEFT),
                'estado' => 'disponible',
                'serie' => $this->serie,
            ]);
        }

        $this->reset(['inicio', 'cantidad_libretas', 'numero_final', 'serie']);
        $this->dispatchBrowserEvent('swal', ['title' => 'Libreta registrada!', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.tesoreria.cheque.cheque-libreta');
    }
}
