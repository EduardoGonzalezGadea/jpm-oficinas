<?php

namespace App\Http\Livewire\Tesoreria\CertificadosResidencia;

use App\Models\Tesoreria\CertificadoResidencia;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    public $fecha_recibido;
    public $titular_nombre;
    public $titular_apellido;
    public $titular_tipo_documento = 'Cédula';
    public $titular_nro_documento;

    protected $listeners = ['showCreateModal'];

    public function showCreateModal()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'createModal']);
    }

    public function mount()
    {
        $this->fecha_recibido = date('Y-m-d');
    }

    public function render()
    {
        return view('livewire.tesoreria.certificados-residencia.create');
    }

    public function save()
    {
        $this->validate([
            'fecha_recibido' => 'required|date',
            'titular_nombre' => 'required|string|max:255',
            'titular_apellido' => 'required|string|max:255',
            'titular_tipo_documento' => 'required|in:Cédula,Cédula Extranjera,Pasaporte,Otro',
            'titular_nro_documento' => 'required|string|max:255',
        ]);

        CertificadoResidencia::create([
            'fecha_recibido' => $this->fecha_recibido,
            'receptor_id' => Auth::id(),
            'titular_nombre' => $this->titular_nombre,
            'titular_apellido' => $this->titular_apellido,
            'titular_tipo_documento' => $this->titular_tipo_documento,
            'titular_nro_documento' => $this->titular_nro_documento,
            'estado' => 'Recibido',
        ]);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'createModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Certificado registrado correctamente.']);
    }

    private function resetInput()
    {
        $this->fecha_recibido = date('Y-m-d');
        $this->titular_nombre = '';
        $this->titular_apellido = '';
        $this->titular_tipo_documento = 'Cédula';
        $this->titular_nro_documento = '';
    }
}