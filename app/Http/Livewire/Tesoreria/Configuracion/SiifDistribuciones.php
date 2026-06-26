<?php

namespace App\Http\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\SiifDistribucion as Model;
use App\Models\Tesoreria\SiifDistribucionTipo;
use App\Models\Tesoreria\SiifDistribucionDependencia;
use App\Models\Tesoreria\TesCfeItem;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class SiifDistribuciones extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $filtroTipo = '';
    public $filtroDependencia = '';
    public $siif_distribucion_id;
    public $tipo_id;
    public $dependencia_id;
    public $rubro;
    public $sub_rubro;
    public $recurso;
    public $concepto;
    public $codigo_sir;
    public $porcentaje;
    public $financiacion;
    public $inciso;
    public $unidad_ejecutora;
    public $selectedDistribucion = null;

    public function render()
    {
        $distribuciones = $this->queryBase()
            ->ordenado()
            ->paginate(15);

        $subtotalesConcepto = $this->queryBase()
            ->selectRaw('tipo_id, dependencia_id, concepto, SUM(porcentaje) as total, COUNT(*) as cantidad')
            ->groupBy('tipo_id', 'dependencia_id', 'concepto')
            ->get()
            ->mapWithKeys(fn($row) => [
                $row->tipo_id . '-' . $row->dependencia_id . '-' . ($row->concepto ?? '_sin_concepto_') => [
                    'total' => $row->total,
                    'cantidad' => $row->cantidad,
                ]
            ]);

        $tipos = SiifDistribucionTipo::ordenado()->get();
        $dependencias = SiifDistribucionDependencia::ordenado()->get();

        return view('livewire.tesoreria.configuracion.siif-distribuciones', [
            'distribuciones' => $distribuciones,
            'tipos' => $tipos,
            'dependencias' => $dependencias,
            'subtotalesConcepto' => $subtotalesConcepto,
        ]);
    }

    private function queryBase()
    {
        $query = Model::with(['tipo', 'dependencia'])->search($this->search);

        if ($this->filtroTipo !== '') {
            $query->where('tipo_id', $this->filtroTipo);
        }

        if ($this->filtroDependencia !== '') {
            $query->where('dependencia_id', $this->filtroDependencia);
        }

        return $query;
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'siifDistribucionModal']);
    }

    public function store()
    {
        $this->validate([
            'tipo_id' => 'required|integer|exists:siif_distribucion_tipos,id',
            'dependencia_id' => 'required|integer|exists:siif_distribucion_dependencias,id',
            'rubro' => 'nullable|string|max:255',
            'sub_rubro' => 'nullable|string|max:255',
            'recurso' => 'nullable|string|max:255',
            'concepto' => ['nullable', 'string', 'max:255',
                Rule::unique('siif_distribucions', 'concepto')
                    ->where('tipo_id', $this->tipo_id)
                    ->where('dependencia_id', $this->dependencia_id),
            ],
            'codigo_sir' => 'nullable|string|max:255',
            'porcentaje' => 'required|numeric|min:0|max:100',
            'financiacion' => 'nullable|string|max:255',
            'inciso' => 'nullable|string|max:255',
            'unidad_ejecutora' => 'nullable|string|max:255',
        ]);

        Model::create([
            'tipo_id' => $this->tipo_id,
            'dependencia_id' => $this->dependencia_id,
            'rubro' => $this->rubro,
            'sub_rubro' => $this->sub_rubro,
            'recurso' => $this->recurso,
            'concepto' => $this->concepto,
            'codigo_sir' => $this->codigo_sir,
            'porcentaje' => $this->porcentaje,
            'financiacion' => $this->financiacion,
            'inciso' => $this->inciso,
            'unidad_ejecutora' => $this->unidad_ejecutora,
        ]);

        $this->resetInput();
        $this->emit('siifDistribucionStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Distribución SIIF creada con éxito.', 'toast' => true]);
    }

    public function edit($id)
    {
        $d = Model::findOrFail($id);

        $this->siif_distribucion_id = $id;
        $this->tipo_id = $d->tipo_id;
        $this->dependencia_id = $d->dependencia_id;
        $this->rubro = $d->rubro;
        $this->sub_rubro = $d->sub_rubro;
        $this->recurso = $d->recurso;
        $this->concepto = $d->concepto;
        $this->codigo_sir = $d->codigo_sir;
        $this->porcentaje = $d->porcentaje;
        $this->financiacion = $d->financiacion;
        $this->inciso = $d->inciso;
        $this->unidad_ejecutora = $d->unidad_ejecutora;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'siifDistribucionModal']);
    }

    public function update()
    {
        $this->validate([
            'tipo_id' => 'required|integer|exists:siif_distribucion_tipos,id',
            'dependencia_id' => 'required|integer|exists:siif_distribucion_dependencias,id',
            'rubro' => 'nullable|string|max:255',
            'sub_rubro' => 'nullable|string|max:255',
            'recurso' => 'nullable|string|max:255',
            'concepto' => ['nullable', 'string', 'max:255',
                Rule::unique('siif_distribucions', 'concepto')
                    ->where('tipo_id', $this->tipo_id)
                    ->where('dependencia_id', $this->dependencia_id)
                    ->ignore($this->siif_distribucion_id),
            ],
            'codigo_sir' => 'nullable|string|max:255',
            'porcentaje' => 'required|numeric|min:0|max:100',
            'financiacion' => 'nullable|string|max:255',
            'inciso' => 'nullable|string|max:255',
            'unidad_ejecutora' => 'nullable|string|max:255',
        ]);

        if ($this->siif_distribucion_id) {
            $d = Model::findOrFail($this->siif_distribucion_id);
            $d->update([
                'tipo_id' => $this->tipo_id,
                'dependencia_id' => $this->dependencia_id,
                'rubro' => $this->rubro,
                'sub_rubro' => $this->sub_rubro,
                'recurso' => $this->recurso,
                'concepto' => $this->concepto,
                'codigo_sir' => $this->codigo_sir,
                'porcentaje' => $this->porcentaje,
                'financiacion' => $this->financiacion,
                'inciso' => $this->inciso,
                'unidad_ejecutora' => $this->unidad_ejecutora,
            ]);

            $this->resetInput();
            $this->emit('siifDistribucionUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Distribución SIIF actualizada con éxito.', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $d = Model::findOrFail($id);
        $usos = TesCfeItem::where('siif_distribucion_id', $id)->count();

        if ($usos > 0) {
            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'warning',
                'title' => 'Distribución en uso',
                'text' => "Esta distribución está asignada a {$usos} ítem(es) de CFE. No se puede eliminar.",
            ]);
            return;
        }

        $d->delete();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Distribución SIIF eliminada con éxito.', 'toast' => true]);
    }

    public function showDetails($id)
    {
        $this->selectedDistribucion = Model::with(['tipo', 'dependencia'])->findOrFail($id);
    }

    public function resetDetails()
    {
        $this->selectedDistribucion = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFiltroTipo()
    {
        $this->resetPage();
    }

    public function updatingFiltroDependencia()
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->search = '';
        $this->filtroTipo = '';
        $this->filtroDependencia = '';
        $this->resetPage();
    }

    private function resetInput()
    {
        $this->siif_distribucion_id = null;
        $this->tipo_id = null;
        $this->dependencia_id = null;
        $this->rubro = null;
        $this->sub_rubro = null;
        $this->recurso = null;
        $this->concepto = null;
        $this->codigo_sir = null;
        $this->porcentaje = null;
        $this->financiacion = null;
        $this->inciso = null;
        $this->unidad_ejecutora = null;
    }
}
