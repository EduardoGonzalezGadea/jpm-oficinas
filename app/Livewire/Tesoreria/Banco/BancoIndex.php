<?php
// app/Http/Livewire/Tesoreria/Banco/BancoIndex.php
namespace App\Livewire\Tesoreria\Banco;

use App\Models\Tesoreria\Banco;
use Livewire\Component;
use Livewire\WithPagination;

class BancoIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    // Formulario crear / editar
    public ?int $bancoId = null;
    public string $nombre = '';
    public string $codigo = '';
    public string $observaciones = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // ── Crear ──────────────────────────────────────────────────────────────
    public function create(): void
    {
        $this->resetInput();
        $this->dispatch('show-modal', id: 'bancoModal');
    }

    // ── Editar ─────────────────────────────────────────────────────────────
    public function edit(int $id): void
    {
        $banco = Banco::findOrFail($id);
        $this->bancoId       = $banco->id;
        $this->nombre        = $banco->nombre;
        $this->codigo        = $banco->codigo;
        $this->observaciones = $banco->observaciones ?? '';
        $this->dispatch('show-modal', id: 'bancoModal');
    }

    // ── Guardar (crear o actualizar) ───────────────────────────────────────
    public function store(): void
    {
        $rules = [
            'nombre' => 'required|string|max:100',
            'codigo' => 'required|string|max:20|unique:tes_bancos,codigo',
        ];

        if ($this->bancoId) {
            $rules['codigo'] = "required|string|max:20|unique:tes_bancos,codigo,{$this->bancoId}";
        }

        $this->validate($rules);

        $data = [
            'nombre'        => $this->nombre,
            'codigo'        => $this->codigo,
            'observaciones' => $this->observaciones,
        ];

        if ($this->bancoId) {
            Banco::findOrFail($this->bancoId)->update($data);
            $msg = 'Banco actualizado con éxito.';
        } else {
            Banco::create($data);
            $msg = 'Banco creado con éxito.';
        }

        $this->resetInput();
        $this->dispatch('close-modal');
        $this->dispatch('alert', type: 'success', message: $msg, toast: true);
    }

    // ── Eliminar ───────────────────────────────────────────────────────────
    public function destroy(int $id): void
    {
        Banco::findOrFail($id)->delete();
        $this->dispatch('alert', type: 'success', message: 'Banco eliminado.', toast: true);
    }

    // ── Reset ──────────────────────────────────────────────────────────────
    public function resetInput(): void
    {
        $this->bancoId       = null;
        $this->nombre        = '';
        $this->codigo        = '';
        $this->observaciones = '';
        $this->resetErrorBag();
    }

    // ── Render ─────────────────────────────────────────────────────────────
    public function render()
    {
        $bancos = Banco::where('nombre', 'like', "%{$this->search}%")
            ->orWhere('codigo', 'like', "%{$this->search}%")
            ->orderBy('nombre')
            ->paginate(15);

        return view('livewire.tesoreria.banco.banco-index', compact('bancos'));
    }
}
