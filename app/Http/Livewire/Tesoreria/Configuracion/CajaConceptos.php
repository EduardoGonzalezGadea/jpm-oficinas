<?php

namespace App\Http\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\CajaConcepto as Model;
use App\Models\Tesoreria\SiifDistribucionTipo;
use Livewire\Component;
use Livewire\WithPagination;

class CajaConceptos extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $caja_concepto_id;
    public $caja_concepto;
    public $requiere_confirmacion    = false;
    public $requiere_distribucion    = false;
    public $permite_planilla         = false;
    public $requiere_organismo       = false;
    public ?int $siif_distribucion_tipo_id = null;
    public $selectedConcepto = null;

    public function render()
    {
        $conceptos = Model::with('siifDistribucionTipo')
            ->search($this->search)
            ->ordenado()
            ->paginate(10);

        return view('livewire.tesoreria.configuracion.caja-conceptos', [
            'conceptos'  => $conceptos,
            'siifTipos'  => SiifDistribucionTipo::orderBy('tipo')->get(),
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'cajaConceptoModal']);
    }

    public function store()
    {
        $this->validate([
            'caja_concepto'              => 'required|string|max:255|unique:tes_caja_conceptos,caja_concepto',
            'requiere_confirmacion'      => 'boolean',
            'requiere_distribucion'      => 'boolean',
            'permite_planilla'           => 'boolean',
            'requiere_organismo'         => 'boolean',
            'siif_distribucion_tipo_id'  => 'nullable|exists:siif_distribucion_tipos,id',
        ]);

        Model::create([
            'caja_concepto'             => $this->caja_concepto,
            'requiere_confirmacion'     => $this->requiere_confirmacion ?? false,
            'requiere_distribucion'     => $this->requiere_distribucion ?? false,
            'permite_planilla'          => $this->permite_planilla ?? false,
            'requiere_organismo'        => $this->requiere_organismo ?? false,
            'siif_distribucion_tipo_id' => $this->siif_distribucion_tipo_id ?: null,
        ]);

        $this->resetInput();
        $this->emit('cajaConceptoStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Concepto de caja creado con éxito.', 'toast' => true]);
    }

    public function edit($id)
    {
        $concepto = Model::findOrFail($id);

        $this->caja_concepto_id        = $id;
        $this->caja_concepto           = $concepto->caja_concepto;
        $this->requiere_confirmacion   = $concepto->requiere_confirmacion;
        $this->requiere_distribucion   = $concepto->requiere_distribucion;
        $this->permite_planilla        = $concepto->permite_planilla;
        $this->requiere_organismo      = $concepto->requiere_organismo;
        $this->siif_distribucion_tipo_id = $concepto->siif_distribucion_tipo_id;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'cajaConceptoModal']);
    }

    public function update()
    {
        $this->validate([
            'caja_concepto'              => 'required|string|max:255|unique:tes_caja_conceptos,caja_concepto,' . $this->caja_concepto_id,
            'requiere_confirmacion'      => 'boolean',
            'requiere_distribucion'      => 'boolean',
            'permite_planilla'           => 'boolean',
            'requiere_organismo'         => 'boolean',
            'siif_distribucion_tipo_id'  => 'nullable|exists:siif_distribucion_tipos,id',
        ]);

        if ($this->caja_concepto_id) {
            $concepto = Model::findOrFail($this->caja_concepto_id);
            $concepto->update([
                'caja_concepto'             => $this->caja_concepto,
                'requiere_confirmacion'     => $this->requiere_confirmacion ?? false,
                'requiere_distribucion'     => $this->requiere_distribucion ?? false,
                'permite_planilla'          => $this->permite_planilla ?? false,
                'requiere_organismo'        => $this->requiere_organismo ?? false,
                'siif_distribucion_tipo_id' => $this->siif_distribucion_tipo_id ?: null,
            ]);

            $this->resetInput();
            $this->emit('cajaConceptoUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Concepto de caja actualizado con éxito.', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $concepto = Model::findOrFail($id);
        $concepto->delete();

        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Concepto de caja eliminado con éxito.', 'toast' => true]);
    }

    public function toggleConfirmacion($id)
    {
        $concepto = Model::findOrFail($id);
        $concepto->update([
            'requiere_confirmacion' => !$concepto->requiere_confirmacion,
        ]);
    }

    public function toggleDistribucion($id)
    {
        $concepto = Model::findOrFail($id);
        $concepto->update([
            'requiere_distribucion' => !$concepto->requiere_distribucion,
        ]);
    }

    public function togglePlanilla($id)
    {
        $concepto = Model::findOrFail($id);
        $concepto->update([
            'permite_planilla' => !$concepto->permite_planilla,
        ]);
    }

    public function toggleOrganismo($id)
    {
        $concepto = Model::findOrFail($id);
        $concepto->update([
            'requiere_organismo' => !$concepto->requiere_organismo,
        ]);
    }

    public function showDetails($id)
    {
        $this->selectedConcepto = Model::findOrFail($id);
    }

    public function resetDetails()
    {
        $this->selectedConcepto = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    private function resetInput()
    {
        $this->caja_concepto_id        = null;
        $this->caja_concepto           = null;
        $this->requiere_confirmacion   = false;
        $this->requiere_distribucion   = false;
        $this->permite_planilla        = false;
        $this->requiere_organismo      = false;
        $this->siif_distribucion_tipo_id = null;
    }
}
