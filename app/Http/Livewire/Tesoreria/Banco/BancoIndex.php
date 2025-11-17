<?php
// app/Http/Livewire/Tesoreria/Banco/BancoIndex.php
namespace App\Http\Livewire\Tesoreria\Banco;

use App\Models\Tesoreria\Banco;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class BancoIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $showCreate = false, $showEdit = false;
    public $bancoId;

    protected $listeners = ['delete', 'closeModal', 'bancoStore' => '$refresh', 'bancoUpdate' => '$refresh'];

    public function render()
    {
        $page = $this->page ?: 1;
        $cacheKey = 'bancos_search_' . $this->search . '_page_' . $page;

        $bancos = Cache::remember($cacheKey, now()->addDay(), function () {
            return Banco::where('nombre', 'like', "%{$this->search}%")
                ->orWhere('codigo', 'like', "%{$this->search}%")
                ->paginate(10);
        });

        return view('livewire.tesoreria.banco.banco-index', compact('bancos'));
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
        session()->flash('debug', 'Create method called');
    }

    public function edit($id)
    {
        $this->bancoId = $id;
        $this->showCreate = false;
        $this->showEdit = true;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modal']);
    }

    public function closeModal()
    {
        $this->showCreate = false;
        $this->showEdit = false;
        $this->emit('close-modal');
    }

    public function deleteConfirm($id)
    {
        $this->emit('swal:confirm', [
            'type' => 'warning',
            'title' => '¿Estás seguro?',
            'text' => 'Se eliminará el banco.',
            'id' => $id,
        ]);
    }

    public function delete($id)
    {
        Banco::find($id)->delete();
        Cache::flush();
        $this->emit('swal', [
            'title' => 'Eliminado!',
            'type' => 'success'
        ]);
    }
}
