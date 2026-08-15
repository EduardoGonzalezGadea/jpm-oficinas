<?php
// app/Http/Livewire/Tesoreria/CuentaBancaria/CuentaIndex.php
namespace App\Livewire\Tesoreria\CuentaBancaria;

use App\Models\Tesoreria\Banco;
use App\Models\Tesoreria\CuentaBancaria;
use Livewire\Component;
use Livewire\WithPagination;

class CuentaIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    // Formulario
    public ?int $cuentaId     = null;
    public ?int $banco_id     = null;
    public string $numero_cuenta = '';
    public string $tipo          = 'Corriente';
    public bool   $activa        = true;
    public string $observaciones = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // ── Crear ──────────────────────────────────────────────────────────────
    public function create(): void
    {
        $this->resetInput();
        $this->dispatch('show-modal', id: 'cuentaModal');
    }

    // ── Editar ─────────────────────────────────────────────────────────────
    public function edit(int $id): void
    {
        $cuenta = CuentaBancaria::findOrFail($id);
        $this->cuentaId       = $cuenta->id;
        $this->banco_id       = $cuenta->banco_id;
        $this->numero_cuenta  = $cuenta->numero_cuenta;
        $this->tipo           = $cuenta->tipo;
        $this->activa         = (bool) $cuenta->activa;
        $this->observaciones  = $cuenta->observaciones ?? '';
        $this->dispatch('show-modal', id: 'cuentaModal');
    }

    // ── Guardar ────────────────────────────────────────────────────────────
    public function store(): void
    {
        $uniqueRule = $this->cuentaId
            ? "required|string|max:50|unique:tes_cuentas_bancarias,numero_cuenta,{$this->cuentaId}"
            : 'required|string|max:50|unique:tes_cuentas_bancarias,numero_cuenta';

        $this->validate([
            'banco_id'     => 'required|exists:tes_bancos,id',
            'numero_cuenta'=> $uniqueRule,
            'tipo'         => 'required|string|max:20',
            'activa'       => 'boolean',
        ]);

        $data = [
            'banco_id'      => $this->banco_id,
            'numero_cuenta' => $this->numero_cuenta,
            'tipo'          => $this->tipo,
            'activa'        => $this->activa,
            'observaciones' => $this->observaciones,
        ];

        if ($this->cuentaId) {
            CuentaBancaria::findOrFail($this->cuentaId)->update($data);
            $msg = 'Cuenta bancaria actualizada con éxito.';
        } else {
            CuentaBancaria::create($data);
            $msg = 'Cuenta bancaria creada con éxito.';
        }

        $this->resetInput();
        $this->dispatch('close-modal');
        $this->dispatch('alert', type: 'success', message: $msg, toast: true);
    }

    // ── Eliminar ───────────────────────────────────────────────────────────
    public function destroy(int $id): void
    {
        CuentaBancaria::findOrFail($id)->delete();
        $this->dispatch('alert', type: 'success', message: 'Cuenta bancaria eliminada.', toast: true);
    }

    // ── Reset ──────────────────────────────────────────────────────────────
    public function resetInput(): void
    {
        $this->cuentaId      = null;
        $this->banco_id      = null;
        $this->numero_cuenta = '';
        $this->tipo          = 'Corriente';
        $this->activa        = true;
        $this->observaciones = '';
        $this->resetErrorBag();
    }

    // ── Render ─────────────────────────────────────────────────────────────
    public function render()
    {
        $cuentas = CuentaBancaria::with('banco')
            ->where(function ($q) {
                $q->whereHas('banco', fn ($b) => $b->where('nombre', 'like', "%{$this->search}%"))
                  ->orWhere('numero_cuenta', 'like', "%{$this->search}%");
            })
            ->orderBy('numero_cuenta')
            ->paginate(15);

        $bancos = Banco::orderBy('nombre')->get();

        return view('livewire.tesoreria.cuenta-bancaria.cuenta-index', compact('cuentas', 'bancos'));
    }
}
