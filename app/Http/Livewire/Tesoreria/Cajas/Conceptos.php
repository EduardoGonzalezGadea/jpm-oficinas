<?php

namespace App\Http\Livewire\Tesoreria\Cajas;

use Livewire\Component;
use App\Models\Tesoreria\Cajas\Concepto;
use Livewire\WithPagination;

class Conceptos extends Component
{
    use WithPagination;

    public $search = '';
    public $sort = 'idConcepto';
    public $direction = 'desc';
    public $cant = '10';

    public $nombre, $tipo, $descripcion, $activo, $idConcepto;
    public $modal = false;

    protected $rules = [
        'nombre' => 'required|string|max:100',
        'tipo' => 'required|in:INGRESO,EGRESO',
        'descripcion' => 'nullable|string',
        'activo' => 'boolean',
    ];

    public function render()
    {
        $conceptos = Concepto::where('nombre', 'like', '%' . $this->search . '%')
            ->orderBy($this->sort, $this->direction)
            ->paginate($this->cant);

        return view('livewire.tesoreria.cajas.conceptos', compact('conceptos'));
    }

    public function openModal()
    {
        $this->modal = true;
    }

    public function closeModal()
    {
        $this->modal = false;
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->nombre = '';
        $this->tipo = '';
        $this->descripcion = '';
        $this->activo = true;
        $this->idConcepto = null;
    }

    public function store()
    {
        $this->validate();

        Concepto::updateOrCreate(['idConcepto' => $this->idConcepto], [
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ]);

        session()->flash('message',
            $this->idConcepto ? 'Concepto actualizado correctamente.' : 'Concepto creado correctamente.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $concepto = Concepto::findOrFail($id);
        $this->idConcepto = $id;
        $this->nombre = $concepto->nombre;
        $this->tipo = $concepto->tipo;
        $this->descripcion = $concepto->descripcion;
        $this->activo = $concepto->activo;
        $this->openModal();
    }

    public function delete($id)
    {
        Concepto::find($id)->delete();
        session()->flash('message', 'Concepto eliminado correctamente.');
    }

    public function order($sort)
    {
        if ($this->sort == $sort) {
            if ($this->direction == 'desc') {
                $this->direction = 'asc';
            } else {
                $this->direction = 'desc';
            }
        } else {
            $this->sort = $sort;
            $this->direction = 'desc';
        }
    }
}
