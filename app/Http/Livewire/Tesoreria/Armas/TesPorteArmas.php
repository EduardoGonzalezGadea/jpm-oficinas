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

    public function mount()
    {
        $this->fecha = date('Y-m-d');
    }

    public function render()
    {
        $page = $this->page ?: 1;
        $cacheKey = 'tes_porte_armas_search_' . $this->search . '_page_' . $page;

        $registros = Cache::remember($cacheKey, now()->addDay(), function () {
            return TesPorteArmasModel::where(function($query) {
                $query->where('titular', 'like', '%' . $this->search . '%')
                      ->orWhere('cedula', 'like', '%' . $this->search . '%')
                      ->orWhere('orden_cobro', 'like', '%' . $this->search . '%')
                      ->orWhere('numero_tramite', 'like', '%' . $this->search . '%');
            })
            ->orderBy('fecha', 'asc')
            ->orderBy('recibo', 'asc')
            ->paginate(10);
        });

        return view('livewire.tesoreria.armas.tes-porte-armas', compact('registros'));
    }

    public function updatingSearch()
    {
        $this->resetPage();
        Cache::flush();
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
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
