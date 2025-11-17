<?php
// app/Http/Livewire/Tesoreria/Banco/BancoEdit.php
namespace App\Http\Livewire\Tesoreria\Banco;

use App\Models\Tesoreria\Banco;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class BancoEdit extends Component
{
    public $bancoId, $nombre, $codigo, $observaciones;

    protected $rules = [
        'nombre' => 'required|string|max:100',
        'codigo' => 'required|string|max:20',
    ];

    public function mount($bancoId)
    {
        $banco = Banco::findOrFail($bancoId);
        $this->bancoId = $banco->id;
        $this->nombre = $banco->nombre;
        $this->codigo = $banco->codigo;
        $this->observaciones = $banco->observaciones;
    }

    public function save()
    {
        $this->validate([
            'codigo' => "required|unique:tes_bancos,codigo,{$this->bancoId}"
        ]);
        $banco = Banco::find($this->bancoId);
        $banco->update([
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'observaciones' => $this->observaciones,
        ]);

        Cache::flush();
        $this->emit('bancoUpdate');
        $this->dispatchBrowserEvent('swal', ['title' => 'Banco actualizado!', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.tesoreria.banco.banco-edit');
    }
}
