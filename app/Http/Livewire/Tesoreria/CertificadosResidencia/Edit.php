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
    public $fecha_entregado;
    public $retira_nombre;
    public $retira_apellido;
    public $retira_tipo_documento;
    public $retira_nro_documento;
    public $numero_recibo;
    public $estado;

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
        $this->fecha_entregado = $certificado->fecha_entregado;
        $this->retira_nombre = $certificado->retira_nombre;
        $this->retira_apellido = $certificado->retira_apellido;
        $this->retira_tipo_documento = $certificado->retira_tipo_documento;
        $this->retira_nro_documento = $certificado->retira_nro_documento;
        $this->numero_recibo = $certificado->numero_recibo;
        $this->estado = $certificado->estado;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'editModal']);
    }

    public function render()
    {
        return view('livewire.tesoreria.certificados-residencia.edit');
    }

    public function update()
    {
        // Convertir fecha_entregado vacía a null
        if (empty($this->fecha_entregado)) {
            $this->fecha_entregado = null;
        }

        $this->validate([
            'fecha_recibido' => 'required|date',
            'titular_nombre' => 'required|string|max:255',
            'titular_apellido' => 'required|string|max:255',
            'titular_tipo_documento' => 'required|in:Cédula,Cédula Extranjera,Pasaporte,Otro',
            'titular_nro_documento' => 'required|string|max:255',
            'fecha_entregado' => 'nullable|date',
            'retira_nombre' => 'nullable|string|max:255',
            'retira_apellido' => 'nullable|string|max:255',
            'retira_tipo_documento' => 'nullable|in:Cédula,Cédula Extranjera,Pasaporte,Otro',
            'retira_nro_documento' => 'nullable|string|max:255',
            'numero_recibo' => 'nullable|string|max:255',
        ]);

        // Si no hay fecha de entrega, todos los datos de entrega deben ser nulos
        if (empty($this->fecha_entregado)) {
            $this->retira_nombre = null;
            $this->retira_apellido = null;
            $this->retira_tipo_documento = null;
            $this->retira_nro_documento = null;
            $this->numero_recibo = null;
        }

        $certificado = CertificadoResidencia::find($this->certificado_id);
        
        // Preparar datos para actualizar
        $updateData = [
            'fecha_recibido' => $this->fecha_recibido,
            'titular_nombre' => $this->titular_nombre,
            'titular_apellido' => $this->titular_apellido,
            'titular_tipo_documento' => $this->titular_tipo_documento,
            'titular_nro_documento' => $this->titular_nro_documento,
            'fecha_entregado' => $this->fecha_entregado,
            'retira_nombre' => $this->retira_nombre,
            'retira_apellido' => $this->retira_apellido,
            'retira_tipo_documento' => $this->retira_tipo_documento,
            'retira_nro_documento' => $this->retira_nro_documento,
            'numero_recibo' => $this->numero_recibo,
        ];
        
        // Si no hay fecha de entrega, cambiar estado a 'Recibido'
        if (empty($this->fecha_entregado)) {
            $updateData['estado'] = 'Recibido';
        }
        
        $certificado->update($updateData);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'editModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Certificado actualizado correctamente.']);
    }
}