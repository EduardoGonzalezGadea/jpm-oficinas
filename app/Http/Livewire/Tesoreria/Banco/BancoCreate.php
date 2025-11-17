<?php
// app/Http/Livewire/Tesoreria/Banco/BancoCreate.php
namespace App\Http\Livewire\Tesoreria\Banco;

use App\Models\Tesoreria\Banco;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class BancoCreate extends Component
{
    public $nombre, $codigo, $observaciones;

    protected $rules = [
        'nombre' => 'required|string|max:100',
        'codigo' => 'required|string|max:20|unique:tes_bancos,codigo',
    ];

    public function save()
    {
        $this->validate();
        Banco::create([
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'observaciones' => $this->observaciones,
        ]);

        Cache::flush();
        $this->reset();
        $this->emit('bancoStore');
        $this->dispatchBrowserEvent('swal', ['title' => 'Banco creado!', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.tesoreria.banco.banco-create');
    }
}
