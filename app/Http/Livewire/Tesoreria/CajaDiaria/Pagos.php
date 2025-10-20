<?php

namespace App\Http\Livewire\Tesoreria\CajaDiaria;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Pago;
use App\Models\Tesoreria\CajaDiaria\ConceptoPago;

class Pagos extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $fecha;
    public $search = '';
    public $pagoId;
    public $concepto_id;
    public $monto;
    public $medio_pago;
    public $descripcion;
    public $numero_comprobante;
    public $conceptos;
    public $showModal = false;

    protected $rules = [
        'concepto_id' => 'required',
        'monto' => 'required|numeric|min:0',
        'medio_pago' => 'required|string',
        'descripcion' => 'nullable|string',
        'numero_comprobante' => 'nullable|string'
    ];

    public function mount($fecha = null)
    {
        $this->fecha = $fecha ?: now()->format('Y-m-d');
        $this->conceptos = ConceptoPago::activos()->orderBy('nombre')->get();
    }

    public function crear()
    {
        $this->reset(['pagoId', 'concepto_id', 'monto', 'medio_pago', 'descripcion', 'numero_comprobante']);
        $this->showModal = true;
    }

    public function editar($id)
    {
        $pago = Pago::find($id);
        if ($pago) {
            $this->pagoId = $pago->id;
            $this->concepto_id = $pago->concepto_id;
            $this->monto = $pago->monto;
            $this->medio_pago = $pago->medio_pago;
            $this->descripcion = $pago->descripcion;
            $this->numero_comprobante = $pago->numero_comprobante;
            $this->showModal = true;
        }
    }

    public function guardar()
    {
        $this->validate();

        $data = [
            'fecha' => $this->fecha,
            'concepto_id' => $this->concepto_id,
            'monto' => $this->monto,
            'medio_pago' => $this->medio_pago,
            'descripcion' => $this->descripcion,
            'numero_comprobante' => $this->numero_comprobante
        ];

        if ($this->pagoId) {
            $pago = Pago::find($this->pagoId);
            $pago->update($data + ['updated_by' => auth()->id()]);
            $message = 'Pago actualizado exitosamente';
        } else {
            Pago::create($data + ['created_by' => auth()->id()]);
            $message = 'Pago registrado exitosamente';
        }

        $this->showModal = false;
        $this->reset(['pagoId', 'concepto_id', 'monto', 'medio_pago', 'descripcion', 'numero_comprobante']);
        session()->flash('message', $message);
    }

    public function eliminar($id)
    {
        $pago = Pago::find($id);
        if ($pago) {
            $pago->delete();
            session()->flash('message', 'Pago eliminado exitosamente');
        }
    }

    public function render()
    {
        $pagos = Pago::with('concepto')
            ->whereDate('fecha', $this->fecha)
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('descripcion', 'like', '%'.$this->search.'%')
                        ->orWhere('numero_comprobante', 'like', '%'.$this->search.'%')
                        ->orWhereHas('concepto', function($q) {
                            $q->where('nombre', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.tesoreria.caja-diaria.pagos', [
            'pagos' => $pagos,
            'conceptos' => $this->conceptos
        ]);
    }
}
