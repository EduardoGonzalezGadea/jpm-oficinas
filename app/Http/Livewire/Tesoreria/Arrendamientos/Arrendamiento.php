<?php

namespace App\Http\Livewire\Tesoreria\Arrendamientos;

    use App\Models\Tesoreria\Arrendamiento as Model;
    use App\Models\Tesoreria\MedioDePago;
    use Livewire\Component;
    use Livewire\WithPagination;
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\DB;
    use App\Traits\ConvertirMayusculas;

class Arrendamiento extends Component
{
    use WithPagination, ConvertirMayusculas;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh', 'planillaCreated' => '$refresh', 'planillaDeleted' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $mes, $year;
    public $search;
    public $total;

    public $arrendamiento_id, $fecha, $ingreso, $nombre, $cedula, $telefono, $monto, $detalle, $orden_cobro, $recibo, $medio_de_pago;
    public $selectedArrendamiento = null;

    public function mount()
    {
        // Verificar autenticación antes de procesar cualquier lógica
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'La sesión ha expirado. Por favor, inicie sesión de nuevo.');
        }

        $this->mes = Carbon::now()->month;
        $this->year = Carbon::now()->year;
        $this->medio_de_pago = 'Transferencia';
    }

    public function render()
    {
        $page = $this->page ?: 1;
        $cacheKey = 'arrendamientos_' . $this->year . '_' . $this->mes . '_search_' . $this->search . '_page_' . $page;

        $data = Cache::remember($cacheKey, now()->addDay(), function () {
            $arrendamientos = Model::whereYear('fecha', $this->year)
                ->whereMonth('fecha', $this->mes)
                ->search($this->search)
                ->orderBy('fecha', 'asc')
                ->orderBy('recibo', 'asc')
                ->paginate(10);

            $subtotales = Model::whereYear('fecha', $this->year)
                ->whereMonth('fecha', $this->mes)
                ->search($this->search)
                ->select('medio_de_pago', DB::raw('sum(monto) as total'))
                ->groupBy('medio_de_pago')
                ->get();

            return ['arrendamientos' => $arrendamientos, 'subtotales' => $subtotales];
        });

        $this->total = $data['arrendamientos']->sum('monto');

        $mediosDePago = Cache::remember('medios_de_pago_activos', now()->addDay(), function () {
            return MedioDePago::activos()->ordenado()->get();
        });

        return view('livewire.tesoreria.arrendamientos.arrendamiento', [
            'arrendamientos' => $data['arrendamientos'],
            'subtotales' => $data['subtotales'],
            'mediosDePago' => $mediosDePago,
        ]);
    }

    public function create()
    {
        $this->resetInput();
    }

    public function store()
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'La sesión ha expirado. Por favor, inicie sesión de nuevo.');
        }

        if (!$this->fecha) {
            $this->fecha = Carbon::now()->format('Y-m-d');
        }

        $validated = $this->validate([
            'fecha' => 'required|date',
            'ingreso' => 'nullable|integer',
            'nombre' => 'nullable|string|max:255',
            'cedula' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'monto' => 'required|numeric',
            'detalle' => 'nullable|string',
            'orden_cobro' => 'nullable|string|max:255',
            'recibo' => 'nullable|string|max:255',
            'medio_de_pago' => 'required|string|max:255',
        ]);

        $datos = $this->convertirCamposAMayusculas(
            ['nombre', 'cedula', 'telefono', 'detalle', 'orden_cobro', 'recibo', 'medio_de_pago'],
            $validated
        );

        try {
            DB::beginTransaction();
            Model::create($datos);
            Cache::flush();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Hubo un error al crear el arrendamiento. Por favor, inténtalo nuevamente.']);
            return;
        }

        $this->resetInput();
        $this->emit('arrendamientoStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Arrendamiento creado con éxito!']);
    }

    public function edit($id)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'La sesión ha expirado. Por favor, inicie sesión de nuevo.');
        }

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

        $this->dispatchBrowserEvent('show-modal', ['id' => 'arrendamientoModal']);
    }

    public function editIngreso($id)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'La sesión ha expirado. Por favor, inicie sesión de nuevo.');
        }

        $arrendamiento = Model::findOrFail($id);

        $this->arrendamiento_id = $id;
        $this->nombre = $arrendamiento->nombre;
        $this->monto = $arrendamiento->monto_formateado;
        $this->orden_cobro = $arrendamiento->orden_cobro;
        $this->recibo = $arrendamiento->recibo;
        $this->ingreso = $arrendamiento->ingreso;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'ingresoModal']);
    }

    public function updateIngreso()
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'La sesión ha expirado. Por favor, inicie sesión de nuevo.');
        }

        $this->validate(['ingreso' => 'nullable|integer']);

        if ($this->arrendamiento_id) {
            $arrendamiento = Model::findOrFail($this->arrendamiento_id);

            try {
                DB::beginTransaction();
                $arrendamiento->update(['ingreso' => $this->ingreso]);
                Cache::flush();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Hubo un error al actualizar el ingreso. Por favor, inténtalo nuevamente.']);
                return;
            }

            $this->resetInput();
            $this->emit('arrendamientoUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Ingreso actualizado con éxito!']);
        }
    }

    public function update()
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'La sesión ha expirado. Por favor, inicie sesión de nuevo.');
        }

        $validated = $this->validate([
            'fecha' => 'required|date',
            'ingreso' => 'nullable|integer',
            'nombre' => 'nullable|string|max:255',
            'cedula' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'monto' => 'required|numeric',
            'detalle' => 'nullable|string',
            'orden_cobro' => 'nullable|string|max:255',
            'recibo' => 'nullable|string|max:255',
            'medio_de_pago' => 'required|string|max:255',
        ]);

        if ($this->arrendamiento_id) {
            $arrendamiento = Model::findOrFail($this->arrendamiento_id);
            $datos = $this->convertirCamposAMayusculas(
                ['nombre', 'cedula', 'telefono', 'detalle', 'orden_cobro', 'recibo', 'medio_de_pago'],
                $validated
            );

            try {
                DB::beginTransaction();
                $arrendamiento->update($datos);
                Cache::flush();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Hubo un error al actualizar el arrendamiento. Por favor, inténtalo nuevamente.']);
                return;
            }

            $this->resetInput();
            $this->emit('arrendamientoUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Arrendamiento actualizado con éxito!']);
        }
    }

    public function destroy($id)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'La sesión ha expirado. Por favor, inicie sesión de nuevo.');
        }

        $arrendamiento = Model::findOrFail($id);

        if ($arrendamiento->planilla_id !== null) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'El arrendamiento está incluido en una planilla y no puede ser eliminado.'
            ]);
            return;
        }

        try {
            DB::beginTransaction();
            $arrendamiento->delete();
            Cache::flush();
            DB::commit();
            session()->flash('message', 'Arrendamiento eliminado con éxito.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Hubo un error al eliminar el arrendamiento. Por favor, inténtalo nuevamente.']);
        }
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
        Cache::flush();
    }

    public function updatingMes()
    {
        $this->resetPage();
        Cache::flush();
    }

    public function updatingYear()
    {
        $this->resetPage();
        Cache::flush();
    }

    public function toggleConfirmado($id)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'La sesión ha expirado. Por favor, inicie sesión de nuevo.');
        }

        if (auth()->user()->cannot('gestionar_tesoreria') && auth()->user()->cannot('supervisar_tesoreria')) {
            abort(403);
        }

        $arrendamiento = Model::findOrFail($id);

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
        Cache::flush();

        $this->emit('arrendamientoStatusUpdated'); // Emit the event
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Estado de confirmación actualizado.']);
    }
}
