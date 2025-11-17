<?php
// app/Http/Livewire/Tesoreria/CuentaBancaria/CuentaIndex.php
namespace App\Http\Livewire\Tesoreria\CuentaBancaria;

use App\Models\Tesoreria\CuentaBancaria;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class CuentaIndex extends Component
{
    use WithPagination;
    public $search = '';
    public $showCreate = false, $showEdit = false;
    public $cuentaId;

    protected $listeners = ['delete', 'closeModal', 'cuentaStore' => '$refresh', 'cuentaUpdate' => '$refresh'];

    public function render()
    {
        $page = $this->page ?: 1;
        $cacheKey = 'cuentas_bancarias_search_' . $this->search . '_page_' . $page;

        $cuentas = Cache::remember($cacheKey, now()->addDay(), function () {
            return CuentaBancaria::with('banco')
                ->whereHas('banco', fn($q) => $q->where('nombre', 'like', "%{$this->search}%"))
                ->orWhere('numero_cuenta', 'like', "%{$this->search}%")
                ->paginate(10);
        });

        return view('livewire.tesoreria.cuenta-bancaria.cuenta-index', compact('cuentas'));
    }

    public function updatingSearch()
    {
        $this->resetPage();
        Cache::flush();
    }

    public function create()
    {
        $this->showCreate = true;
        $this->showEdit = false;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modal']);
    }

    public function edit($id)
    {
        $this->cuentaId = $id;
        $this->showCreate = false;
        $this->showEdit = true;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modal']);
    }

    public function closeModal()
    {
        $this->showCreate = false;
        $this->showEdit = false;
        $this->dispatchBrowserEvent('close-modal');
    }

    public function deleteConfirm($id)
    {
        $this->dispatchBrowserEvent('swal:confirm', [
            'type' => 'warning',
            'title' => '¿Estás seguro?',
            'text' => 'Se eliminará la cuenta bancaria.',
            'id' => $id,
        ]);
    }

    public function delete($id)
    {
        CuentaBancaria::find($id)->delete();
        Cache::flush();
        $this->dispatchBrowserEvent('swal', [
            'title' => 'Eliminado!',
            'type' => 'success'
        ]);
    }
}
