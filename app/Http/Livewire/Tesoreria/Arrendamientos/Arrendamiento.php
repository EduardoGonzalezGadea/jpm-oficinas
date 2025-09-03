<?php

namespace App\Http\Livewire\Tesoreria\Arrendamientos;

use App\Models\Tesoreria\Arrendamiento as Model;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Arrendamiento extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $mes, $year;
    public $search;
    public $total;

    public $arrendamiento_id, $fecha, $ingreso, $nombre, $cedula, $telefono, $monto, $detalle, $orden_cobro, $recibo, $medio_de_pago;
    public $selectedArrendamiento = null;

    public function mount()
    {
        $this->mes = Carbon::now()->month;
        $this->year = Carbon::now()->year;
        $this->medio_de_pago = 'Transferencia';
    }

    public function render()
    {
        $arrendamientos = Model::whereYear('fecha', $this->year)
            ->whereMonth('fecha', $this->mes)
            ->search($this->search)
            ->orderBy('fecha', 'asc')
            ->orderBy('recibo', 'asc')
            ->paginate(10);

        $this->total = $arrendamientos->sum('monto');

        $subtotales = Model::whereYear('fecha', $this->year)
            ->whereMonth('fecha', $this->mes)
            ->search($this->search)
            ->select('medio_de_pago', DB::raw('sum(monto) as total'))
            ->groupBy('medio_de_pago')
            ->get();

                return view('livewire.tesoreria.arrendamientos.arrendamiento', [
            'arrendamientos' => $arrendamientos,
            'subtotales' => $subtotales,
        ]);
    }

    public function create()
    {
        $this->resetInput();
    }

    public function store()
    {
        $this->validate([
            'fecha' => 'required|date',
            'ingreso' => 'nullable|integer',
            'nombre' => 'nullable|string|max:255',
            'cedula' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'monto' => 'required|numeric',
            'detalle' => 'nullable|string',
            'orden_cobro' => 'nullable|integer',
            'recibo' => 'nullable|integer',
            'medio_de_pago' => 'required|string|max:255',
        ]);

        Model::create([
            'fecha' => $this->fecha,
            'ingreso' => $this->ingreso,
            'nombre' => $this->nombre,
            'cedula' => $this->cedula,
            'telefono' => $this->telefono,
            'monto' => $this->monto,
            'detalle' => $this->detalle,
            'orden_cobro' => $this->orden_cobro,
            'recibo' => $this->recibo,
            'medio_de_pago' => $this->medio_de_pago,
        ]);

        $this->resetInput();
        $this->emit('arrendamientoStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Arrendamiento creado con éxito!']);
    }

    public function edit($id)
    {
        $arrendamiento = Model::findOrFail($id);

        if ($arrendamiento->planilla_id !== null) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'El arrendamiento está incluído en una planilla y no se puede modificar.'
            ]);
            $this->dispatchBrowserEvent('close-modal');
            return;
        }

        $this->arrendamiento_id = $id;
        $this->fecha = Carbon::parse($arrendamiento->fecha)->format('Y-m-d');
        $this->ingreso = $arrendamiento->ingreso;
        $this->nombre = $arrendamiento->nombre;
        $this->cedula = $arrendamiento->cedula;
        $this->telefono = $arrendamiento->telefono;
        $this->monto = $arrendamiento->monto;
        $this->detalle = $arrendamiento->detalle;
        $this->orden_cobro = $arrendamiento->orden_cobro;
        $this->recibo = $arrendamiento->recibo;
        $this->medio_de_pago = $arrendamiento->medio_de_pago;

        // Abrir el modal solo si la edición está permitida
        $this->dispatchBrowserEvent('show-modal', ['id' => 'arrendamientoModal']);
    }

    public function update()
    {
        $this->validate([
            'fecha' => 'required|date',
            'ingreso' => 'nullable|integer',
            'nombre' => 'nullable|string|max:255',
            'cedula' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'monto' => 'required|numeric',
            'detalle' => 'nullable|string',
            'orden_cobro' => 'nullable|integer',
            'recibo' => 'nullable|integer',
            'medio_de_pago' => 'required|string|max:255',
        ]);

        if ($this->arrendamiento_id) {
            $arrendamiento = Model::findOrFail($this->arrendamiento_id);
            $arrendamiento->update([
                'fecha' => $this->fecha,
                'ingreso' => $this->ingreso,
                'nombre' => $this->nombre,
                'cedula' => $this->cedula,
                'telefono' => $this->telefono,
                'monto' => $this->monto,
                'detalle' => $this->detalle,
                'orden_cobro' => $this->orden_cobro,
                'recibo' => $this->recibo,
                'medio_de_pago' => $this->medio_de_pago,
            ]);
            $this->resetInput();
            $this->emit('arrendamientoUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Arrendamiento actualizado con éxito!']);
        }
    }

    public function destroy($id)
    {
        $arrendamiento = Model::findOrFail($id);

        if ($arrendamiento->planilla_id !== null) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'El arrendamiento está incluído en una planilla y no se puede eliminar.'
            ]);
            return;
        }

        $arrendamiento->delete();
        session()->flash('message', 'Arrendamiento eliminado con éxito.');
    }

    public function showDetails($id)
    {
        $this->selectedArrendamiento = Model::findOrFail($id);
    }

    public function resetDetails()
    {
        $this->selectedArrendamiento = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->arrendamiento_id = null;
        $this->fecha = Carbon::now()->format('Y-m-d');
        $this->ingreso = null;
        $this->nombre = null;
        $this->cedula = null;
        $this->telefono = null;
        $this->monto = null;
        $this->detalle = null;
        $this->orden_cobro = null;
        $this->recibo = null;
        $this->medio_de_pago = 'Transferencia';
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleConfirmado($id)
    {
        if (auth()->user()->cannot('gestionar_tesoreria') && auth()->user()->cannot('supervisar_tesoreria')) {
            abort(403);
        }

        $arrendamiento = Model::findOrFail($id);

        // Check if arrendamiento is included in a planilla
        if ($arrendamiento->planilla_id !== null) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Incluído en una planilla.'
            ]);
            $this->dispatchBrowserEvent('revertCheckbox', ['id' => $id, 'checked' => $arrendamiento->confirmado]);
            return;
        }

        $arrendamiento->confirmado = !$arrendamiento->confirmado;
        $arrendamiento->save();

        $this->emit('arrendamientoStatusUpdated'); // Emit the event
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Estado de confirmación actualizado.']);
    }
}
