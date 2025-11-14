<?php

namespace App\Http\Livewire\Tesoreria\CertificadosResidencia;

use App\Models\Tesoreria\CertificadoResidencia;
use Livewire\Component;

class Edit extends Component
{
    public $certificado_id;
    public $fecha_recibido;
    public $titular_nombre;
    public $titular_apellido;
    public $titular_tipo_documento;
    public $titular_nro_documento;

    protected $listeners = ['showEditModal'];

    public function showEditModal($id)
    {
        $certificado = CertificadoResidencia::find($id);
        $this->certificado_id = $certificado->id;
        $this->fecha_recibido = $certificado->fecha_recibido;
        $this->titular_nombre = $certificado->titular_nombre;
        $this->titular_apellido = $certificado->titular_apellido;
        $this->titular_tipo_documento = $certificado->titular_tipo_documento;
        $this->titular_nro_documento = $certificado->titular_nro_documento;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'editModal']);
    }

    public function render()
    {
        return view('livewire.tesoreria.certificados-residencia.edit');
    }

    public function update()
    {
        $this->validate([
            'fecha_recibido' => 'required|date',
            'titular_nombre' => 'required|string|max:255',
            'titular_apellido' => 'required|string|max:255',
            'titular_tipo_documento' => 'required|in:Cédula,Cédula Extranjera,Pasaporte,Otro',
            'titular_nro_documento' => 'required|string|max:255',
        ]);

        $certificado = CertificadoResidencia::find($this->certificado_id);
        $certificado->update([
            'fecha_recibido' => $this->fecha_recibido,
            'titular_nombre' => $this->titular_nombre,
            'titular_apellido' => $this->titular_apellido,
            'titular_tipo_documento' => $this->titular_tipo_documento,
            'titular_nro_documento' => $this->titular_nro_documento,
        ]);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'editModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Certificado actualizado correctamente.']);
    }
}