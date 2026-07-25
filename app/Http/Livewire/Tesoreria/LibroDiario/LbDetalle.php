<?php

namespace App\Http\Livewire\Tesoreria\LibroDiario;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle as Model;
use Livewire\Component;
use Livewire\WithPagination;

class LbDetalle extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $item_id, $nombre, $concepto_id;
    public $selectedItem = null;
    public $suggestedNames = [];
    public $filtros = [];

    public array $opcionesAdicionales = [];
    public array $adicionalesSeleccionados = [];

    public function render()
    {
        $query = Model::search($this->search)
            ->with('concepto')
            ->ordenado();

        if ($this->filtros['concepto_id'] ?? null) {
            $query->where('concepto_id', $this->filtros['concepto_id']);
        }

        $items = $query->paginate(50);

        $conceptos = LbConcepto::ordenado()->get();

        return view('livewire.tesoreria.libro-diario.lb-detalle', [
            'items' => $items,
            'conceptos' => $conceptos,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modal']);
    }

    public function seleccionarTodasAdicionales(bool $valor): void
    {
        $this->adicionalesSeleccionados = array_fill(0, count($this->opcionesAdicionales), $valor);
    }

    private function getOpcionesAdicionales(): array
    {
        return [
            '{detalle} (rechazo BROU)',
            '{detalle} (rechazo otros bancos)',
            '{detalle} (con quitas)',
            'Retención Judicial de {detalle}',
            'Retención Judicial de {detalle} (rechazo BROU)',
            'Retención Judicial de {detalle} (rechazo otros bancos)',
            'Retención Judicial de {detalle} (con quitas)',
            'Aguinaldo de {detalle}',
            'Aguinaldo de {detalle} (rechazo BROU)',
            'Aguinaldo de {detalle} (rechazo otros bancos)',
            'Aguinaldo de {detalle} (con quitas)',
            'Retención Judicial de Aguinaldo de {detalle}',
            'Retención Judicial de Aguinaldo de {detalle} (rechazo BROU)',
            'Retención Judicial de Aguinaldo de {detalle} (rechazo otros bancos)',
            'Retención Judicial de Aguinaldo de {detalle} (con quitas)',
        ];
    }

    private function esConceptoVentanilla(?int $conceptoId): bool
    {
        if (!$conceptoId) return false;
        $ventanilla = LbConcepto::where('nombre', 'like', '%Boletos en ventanilla%')->first();
        return $ventanilla && (int) $conceptoId === (int) $ventanilla->id;
    }

    public function updatedNombre($value)
    {
        $this->suggestedNames = [];
        if (strlen($value) >= 2 && $this->concepto_id) {
            $this->suggestedNames = Model::where('concepto_id', $this->concepto_id)
                ->where('nombre', 'like', "%{$value}%")
                ->orderBy('nombre')
                ->limit(10)
                ->pluck('nombre')
                ->toArray();
        }
    }

    public function updatedConceptoId()
    {
        $this->suggestedNames = [];
        if (strlen($this->nombre ?? '') >= 2 && $this->concepto_id) {
            $this->suggestedNames = Model::where('concepto_id', $this->concepto_id)
                ->where('nombre', 'like', "%{$this->nombre}%")
                ->orderBy('nombre')
                ->limit(10)
                ->pluck('nombre')
                ->toArray();
        }

        if ($this->esConceptoVentanilla($this->concepto_id)) {
            $this->opcionesAdicionales = $this->getOpcionesAdicionales();
            $this->adicionalesSeleccionados = array_fill(0, count($this->opcionesAdicionales), false);
        } else {
            $this->opcionesAdicionales = [];
            $this->adicionalesSeleccionados = [];
        }
    }

    public function store()
    {
        $this->validate([
            'nombre' => 'required|string|max:100',
            'concepto_id' => 'required|exists:tes_lb_conceptos,id',
        ]);

        $creados = 0;
        $yaExistentes = 0;

        if (!Model::where('nombre', $this->nombre)
            ->where('concepto_id', $this->concepto_id)
            ->exists()
        ) {
            Model::create([
                'nombre' => $this->nombre,
                'concepto_id' => $this->concepto_id,
            ]);
            $creados++;
        } else {
            $yaExistentes++;
        }

        if (!empty($this->adicionalesSeleccionados)) {
            foreach ($this->adicionalesSeleccionados as $idx => $seleccionado) {
                if ($seleccionado && isset($this->opcionesAdicionales[$idx])) {
                    $nombreAdicional = str_replace('{detalle}', $this->nombre, $this->opcionesAdicionales[$idx]);

                    if (!Model::where('nombre', $nombreAdicional)
                        ->where('concepto_id', $this->concepto_id)
                        ->exists()
                    ) {
                        Model::create([
                            'nombre' => $nombreAdicional,
                            'concepto_id' => $this->concepto_id,
                        ]);
                        $creados++;
                    } else {
                        $yaExistentes++;
                    }
                }
            }
        }

        $this->resetInput();
        $this->emit('itemStore');

        if ($creados > 0) {
            $msg = "Se crearon {$creados} registro(s)";
            if ($yaExistentes > 0) {
                $msg .= ", {$yaExistentes} ya existían y fueron omitidos";
            }
            $msg .= '.';
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => $msg, 'toast' => true]);
        } else {
            $this->dispatchBrowserEvent('alert', ['type' => 'info', 'message' => 'Todos los registros ya existían. No se creó ninguno nuevo.', 'toast' => true]);
        }
    }

    public function edit($id)
    {
        $item = Model::findOrFail($id);

        $this->item_id = $id;
        $this->nombre = $item->nombre;
        $this->concepto_id = $item->concepto_id;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'modal']);
    }

    public function update()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_lb_detalle,nombre,' . $this->item_id . ',id,concepto_id,' . $this->concepto_id,
            'concepto_id' => 'required|exists:tes_lb_conceptos,id',
        ]);

        if ($this->item_id) {
            $item = Model::findOrFail($this->item_id);
            $item->update([
                'nombre' => $this->nombre,
                'concepto_id' => $this->concepto_id,
            ]);
            $this->resetInput();
            $this->emit('itemUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Detalle actualizado con éxito!', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $item = Model::findOrFail($id);
        $item->delete();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Detalle eliminado con éxito!', 'toast' => true]);
    }

    public function showDetails($id)
    {
        $this->selectedItem = Model::with('concepto')->findOrFail($id);
    }

    public function resetDetails()
    {
        $this->selectedItem = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->item_id = null;
        $this->nombre = null;
        $this->concepto_id = null;
        $this->suggestedNames = [];
        $this->opcionesAdicionales = [];
        $this->adicionalesSeleccionados = [];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedFiltrosConceptoId()
    {
        $this->resetPage();
    }
}
