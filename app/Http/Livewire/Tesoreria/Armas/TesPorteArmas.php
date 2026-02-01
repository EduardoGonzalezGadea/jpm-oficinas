<?php

namespace App\Http\Livewire\Tesoreria\Armas;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tesoreria\TesPorteArmas as TesPorteArmasModel;
use App\Traits\ConvertirMayusculas;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TesPorteArmas extends Component
{
    use WithPagination, ConvertirMayusculas;

    protected $paginationTheme = 'bootstrap';

    public $showModal = false;
    public $editMode = false;
    public $showDeleteModal = false;
    public $showDetailModal = false;
    public $deleteId = null;

    public $registro_id;
    public $fecha;
    public $orden_cobro;
    public $numero_tramite;
    public $ingreso_contabilidad;
    public $recibo;
    public $monto;
    public $titular;
    public $cedula;
    public $telefono;

    public $selectedRegistro = null;
    public $search = '';
    public $anio;
    public $selectedRegistros = [];
    public $selectAll = false;

    public $edit_id;
    protected $queryString = ['anio', 'edit_id' => ['except' => null]];
    public function showDetails($id)
    {
        $this->showModal = false;
        $this->showDeleteModal = false;
        $this->editMode = false;
        $this->selectedRegistro = TesPorteArmasModel::findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedRegistro = null;
    }

    protected $rules = [
        'fecha' => 'required|date',
        'monto' => 'required|numeric|min:0',
        'titular' => 'required|string|max:255',
        'cedula' => 'required|string|max:255',
        'orden_cobro' => 'nullable|string|max:255',
        'numero_tramite' => 'nullable|string|max:255',
        'ingreso_contabilidad' => 'nullable|string|max:255',
        'recibo' => 'nullable|string|max:255',
        'telefono' => 'nullable|string|max:255',
    ];

    protected $messages = [
        'fecha.required' => 'La fecha es obligatoria',
        'monto.required' => 'El monto es obligatorio',
        'monto.numeric' => 'El monto debe ser un número',
        'titular.required' => 'El titular es obligatorio',
        'cedula.required' => 'La cédula es obligatoria',
    ];

    public function mount($anio = null)
    {
        $this->fecha = date('Y-m-d');
        $this->anio = $anio ?: date('Y');
    }

    public function checkEditId()
    {
        $id = $this->edit_id ?: session('edit_id');

        if ($id) {
            $this->edit($id);
            // Limpiar el parámetro para que no se re-abra si se refresca la página
            $this->edit_id = null;
        }
    }

    public function updatedAnio()
    {
        $this->resetPage();
        Cache::flush();
    }

    public function updatedSelectAll($value)
    {
        $currentFilteredIds = TesPorteArmasModel::where(function ($query) {
            $query->where('titular', 'like', '%' . $this->search . '%')
                ->orWhere('cedula', 'like', '%' . $this->search . '%')
                ->orWhere('orden_cobro', 'like', '%' . $this->search . '%')
                ->orWhere('numero_tramite', 'like', '%' . $this->search . '%');
        })
            ->whereYear('fecha', $this->anio)
            ->whereNull('planilla_id')
            ->pluck('id')
            ->map(fn($id) => (string)$id)
            ->toArray();

        if ($value) {
            // Agregar los IDs filtrados a los ya seleccionados (sin duplicados)
            $this->selectedRegistros = array_values(array_unique(array_merge($this->selectedRegistros, $currentFilteredIds)));
        } else {
            // Quitar de la selección solo los IDs que coinciden con el filtro actual
            $this->selectedRegistros = array_values(array_diff($this->selectedRegistros, $currentFilteredIds));
        }

        $this->updateSelectAllState();
    }

    public function render()
    {
        $page = $this->page ?: 1;
        $cacheKey = 'tes_porte_armas_desc_anio_' . $this->anio . '_search_' . $this->search . '_page_' . $page;

        $registros = Cache::remember($cacheKey, now()->addDay(), function () {
            return TesPorteArmasModel::where(function ($query) {
                $query->where('titular', 'like', '%' . $this->search . '%')
                    ->orWhere('cedula', 'like', '%' . $this->search . '%')
                    ->orWhere('orden_cobro', 'like', '%' . $this->search . '%')
                    ->orWhere('numero_tramite', 'like', '%' . $this->search . '%');
            })
                ->whereYear('fecha', $this->anio)
                ->with('planilla')
                ->orderBy('fecha', 'desc')
                ->orderBy('recibo', 'asc')
                ->paginate(10);
        });

        return view('livewire.tesoreria.armas.tes-porte-armas', compact('registros'));
    }

    public function updatedSelectedRegistros()
    {
        $this->updateSelectAllState();
    }

    private function updateSelectAllState()
    {
        $currentFilteredIds = TesPorteArmasModel::where(function ($query) {
            $query->where('titular', 'like', '%' . $this->search . '%')
                ->orWhere('cedula', 'like', '%' . $this->search . '%')
                ->orWhere('orden_cobro', 'like', '%' . $this->search . '%')
                ->orWhere('numero_tramite', 'like', '%' . $this->search . '%');
        })
            ->whereYear('fecha', $this->anio)
            ->whereNull('planilla_id')
            ->pluck('id')
            ->map(fn($id) => (string)$id)
            ->toArray();

        if (empty($currentFilteredIds)) {
            $this->selectAll = false;
        } else {
            $allPresent = empty(array_diff($currentFilteredIds, $this->selectedRegistros));
            $this->selectAll = $allPresent;
        }
    }

    public function createPlanilla()
    {
        if (empty($this->selectedRegistros)) {
            $this->emit('error', 'Debe seleccionar al menos un registro.');
            return;
        }

        DB::transaction(function () {
            $planilla = \App\Models\Tesoreria\TesPorteArmasPlanilla::create([
                'fecha' => now(),
            ]);

            TesPorteArmasModel::whereIn('id', $this->selectedRegistros)
                ->update(['planilla_id' => $planilla->id]);

            $this->selectedRegistros = [];
            $this->selectAll = false;
            Cache::flush();

            session()->flash('message', 'Planilla creada exitosamente. Número: ' . $planilla->numero);
        });
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
        Cache::flush();
        $this->updateSelectAllState();
    }

    public function updatingSearch()
    {
        $this->resetPage();
        Cache::flush();
    }

    public function updatedSearch()
    {
        $this->updateSelectAllState();
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;

        // Autoincremento de recibo
        $ultimoRegistro = TesPorteArmasModel::orderBy('id', 'desc')->first();
        $this->recibo = ($ultimoRegistro && $ultimoRegistro->recibo) ? intval($ultimoRegistro->recibo) + 1 : '';

        $this->showModal = true;
        $this->emit('modalOpened');
    }

    public function edit($id)
    {
        $registro = TesPorteArmasModel::findOrFail($id);

        $this->registro_id = $registro->id;
        $this->fecha = $registro->fecha->format('Y-m-d');
        $this->orden_cobro = $registro->orden_cobro;
        $this->numero_tramite = $registro->numero_tramite;
        $this->ingreso_contabilidad = $registro->ingreso_contabilidad;
        $this->recibo = $registro->recibo;
        $this->monto = $registro->monto;
        $this->titular = $registro->titular;
        $this->cedula = $registro->cedula;
        $this->telefono = $registro->telefono;

        $this->editMode = true;
        $this->showModal = true;
        $this->emit('modalOpened');
    }

    public function save()
    {
        $this->validate();

        $data = $this->convertirCamposAMayusculas(
            ['titular', 'cedula', 'telefono', 'orden_cobro', 'numero_tramite', 'ingreso_contabilidad', 'recibo'],
            [
                'fecha' => $this->fecha,
                'orden_cobro' => $this->orden_cobro,
                'numero_tramite' => $this->numero_tramite,
                'ingreso_contabilidad' => $this->ingreso_contabilidad,
                'recibo' => $this->recibo,
                'monto' => $this->monto,
                'titular' => $this->titular,
                'cedula' => $this->cedula,
                'telefono' => $this->telefono,
            ]
        );

        DB::transaction(function () use ($data) {
            if ($this->editMode) {
                TesPorteArmasModel::find($this->registro_id)->update($data);
                session()->flash('message', 'Registro actualizado exitosamente.');
            } else {
                TesPorteArmasModel::create($data);
                session()->flash('message', 'Registro creado exitosamente.');
            }
            Cache::flush();
        });

        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        DB::transaction(function () {
            TesPorteArmasModel::find($this->deleteId)->delete();
            Cache::flush();
            session()->flash('message', 'Registro eliminado exitosamente.');
        });
        $this->showDeleteModal = false;
        $this->deleteId = null;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deleteId = null;
    }

    private function resetForm()
    {
        $this->registro_id = null;
        $this->fecha = date('Y-m-d');
        $this->orden_cobro = '';
        $this->numero_tramite = '';
        $this->ingreso_contabilidad = '';
        $this->recibo = '';
        $this->monto = '';
        $this->titular = '';
        $this->cedula = '';
        $this->telefono = '';
        $this->resetErrorBag();
    }
}
