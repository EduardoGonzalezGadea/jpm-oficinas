<?php
// app/Http/Livewire/Tesoreria/Banco/BancoIndex.php
namespace App\Http\Livewire\Tesoreria\Banco;

use App\Models\Tesoreria\Banco;
use Livewire\Component;
use Livewire\WithPagination;

class BancoIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $showCreate = false, $showEdit = false;
    public $bancoId;

    protected $listeners = ['delete', 'closeModal'];

    public function render()
    {
        $bancos = Banco::where('nombre', 'like', "%{$this->search}%")
            ->orWhere('codigo', 'like', "%{$this->search}%")
            ->paginate(10);

        return view('livewire.tesoreria.banco.banco-index', compact('bancos'));
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
        $this->emit('swal', [
            'title' => 'Eliminado!',
            'type' => 'success'
        ]);
    }
}
