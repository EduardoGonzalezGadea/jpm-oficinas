<?php

namespace App\Livewire\Tesoreria\CertificadosResidencia;

use App\Models\Tesoreria\CertificadoResidencia;
use Livewire\Component;

class Show extends Component
{
    public $certificado;

    protected $listeners = ['showDetailModal'];

    public function showDetailModal($id)
    {
        $this->certificado = CertificadoResidencia::with(['receptor', 'entregador', 'devolucionUser', 'createdBy', 'updatedBy', 'deletedBy'])->find($id);
        $this->dispatch('show-modal', id: 'showModal');
    }

    public function render()
    {
        return view('livewire.tesoreria.certificados-residencia.show');
    }
}