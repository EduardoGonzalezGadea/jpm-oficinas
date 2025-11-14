<?php

namespace App\Http\Livewire\Tesoreria\CertificadosResidencia;

use App\Models\Tesoreria\CertificadoResidencia;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Update extends Component
{
    public $certificado_id;
    public $retira_nombre;
    public $retira_apellido;
    public $retira_tipo_documento = 'Cédula';
    public $retira_nro_documento;
    public $retira_telefono;
    public $retiraEsTitular = false;
    public $certificado;

    protected $listeners = ['showDeliverModal', 'showReturnModal'];

    public function showDeliverModal($id)
    {
        $this->resetInput();
        $this->certificado = CertificadoResidencia::find($id);
        $this->certificado_id = $id;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'deliverModal']);
    }

    public function updatedRetiraEsTitular($value)
    {
        if ($value) {
            $this->retira_nombre = $this->certificado->titular_nombre;
            $this->retira_apellido = $this->certificado->titular_apellido;
            $this->retira_tipo_documento = $this->certificado->titular_tipo_documento;
            $this->retira_nro_documento = $this->certificado->titular_nro_documento;
        } else {
            $this->retira_nombre = '';
            $this->retira_apellido = '';
            $this->retira_tipo_documento = 'Cédula';
            $this->retira_nro_documento = '';
        }
    }

    public function showReturnModal($id)
    {
        $this->certificado_id = $id;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'returnModal']);
    }

    public function render()
    {
        return view('livewire.tesoreria.certificados-residencia.update');
    }

    public function deliver()
    {
        $this->validate([
            'retira_nombre' => 'required|string|max:255',
            'retira_apellido' => 'required|string|max:255',
            'retira_tipo_documento' => 'required|in:Cédula,Cédula Extranjera,Pasaporte,Otro',
            'retira_nro_documento' => 'required|string|max:255',
            'retira_telefono' => 'nullable|string|max:255',
        ]);

        $certificado = CertificadoResidencia::find($this->certificado_id);
        $certificado->update([
            'fecha_entregado' => date('Y-m-d'),
            'entregador_id' => Auth::id(),
            'retira_nombre' => $this->retira_nombre,
            'retira_apellido' => $this->retira_apellido,
            'retira_tipo_documento' => $this->retira_tipo_documento,
            'retira_nro_documento' => $this->retira_nro_documento,
            'retira_telefono' => $this->retira_telefono,
            'estado' => 'Entregado',
        ]);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'deliverModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Certificado entregado correctamente.']);
    }

    public function return()
    {
        $certificado = CertificadoResidencia::find($this->certificado_id);
        $certificado->update([
            'fecha_devuelto' => date('Y-m-d'),
            'devolucion_user_id' => Auth::id(),
            'estado' => 'Devuelto',
        ]);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'returnModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Certificado devuelto correctamente.']);
    }

    private function resetInput()
    {
        $this->certificado_id = null;
        $this->certificado = null;
        $this->retira_nombre = '';
        $this->retira_apellido = '';
        $this->retira_tipo_documento = 'Cédula';
        $this->retira_nro_documento = '';
        $this->retira_telefono = '';
        $this->retiraEsTitular = false;
    }
}