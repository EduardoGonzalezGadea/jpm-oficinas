<?php

namespace App\Http\Livewire\Tesoreria\Eventuales;

use App\Models\Tesoreria\Eventual as Model;
use App\Models\Tesoreria\EventualInstitucion;
use App\Models\Tesoreria\MedioDePago;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Traits\ConvertirMayusculas;

class Eventuales extends Component
{
    use WithPagination, ConvertirMayusculas;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh', 'planillaCreated' => 'refreshData', 'planillaDeleted' => 'refreshData'];

    protected $paginationTheme = 'bootstrap';

    public $mes, $year;
    public $search;
    public $generalTotal, $total;
    public $totalesPorInstitucion = [];

    public $eventual_id, $fecha, $ingreso, $institucion, $titular, $monto, $medio_de_pago, $detalle, $orden_cobro, $recibo;
    public $selectedEventual = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'page' => ['except' => 1],
        'edit_id' => ['except' => null],
    ];

    public $edit_id;

    public function mount()
    {
        // Verificar autenticación antes de procesar cualquier lógica
        if (!auth()->check()) {
            abort(500, 'La sesión ha expirado. Por favor, inicie sesión de nuevo.');
        }

        $this->mes = Carbon::now()->month;
        $this->year = Carbon::now()->year;
        $this->fecha = Carbon::now()->format('Y-m-d');
        $this->medio_de_pago = $this->getDefaultMedioDePago();
    }

    public function checkEditId()
    {
        $id = $this->edit_id ?: session('edit_eventual_id');

        if ($id) {
            $this->edit($id);
            // Limpiar el parámetro para que no se re-abra si se refresca la página
            $this->edit_id = null;
        }
    }

    public function refreshData()
    {
        $this->clearCache();
    }

    private function clearCache()
    {
        $version = Cache::get('eventuales_version', 1);
        Cache::put('eventuales_version', $version + 1, now()->addYear());
    }

    public function render()
    {
        $page = $this->page ?: 1;
        $version = Cache::get('eventuales_version', 1);
        $cacheKey = 'eventuales_v' . $version . '_' . $this->year . '_' . $this->mes . '_search_' . $this->search . '_page_' . $page;

        $data = Cache::remember($cacheKey, now()->addDay(), function () {
            $query = Model::whereYear('fecha', $this->year)
                ->whereMonth('fecha', $this->mes)
                ->search($this->search);

            $generalTotal = (float) $query->sum('monto');

            $eventuales = $query->orderBy('fecha', 'desc')->orderBy('recibo', 'asc')->paginate(10);

            $subtotales = Model::whereYear('fecha', $this->year)
                ->whereMonth('fecha', $this->mes)
                ->search($this->search)
                ->select('medio_de_pago', DB::raw('sum(monto) as total_submonto'))
                ->groupBy('medio_de_pago')
                ->get();

            $totalesPorInstitucion = Model::whereYear('fecha', $this->year)
                ->whereMonth('fecha', $this->mes)
                ->search($this->search)
                ->select('institucion', DB::raw('SUM(monto) as total_monto'))
                ->groupBy('institucion')
                ->orderBy('institucion', 'asc')
                ->toBase()
                ->get();

            return [
                'eventuales' => $eventuales,
                'generalTotal' => $generalTotal,
                'subtotales' => $subtotales,
                'totalesPorInstitucion' => $totalesPorInstitucion,
            ];
        });

        $this->generalTotal = $data['generalTotal'];
        $eventuales = $data['eventuales'];
        $subtotales = $data['subtotales'];
        $this->totalesPorInstitucion = $data['totalesPorInstitucion'];
        $this->total = $eventuales->sum('monto'); // Recalcular total de la página actual

        // Obtener instituciones activas para el select
        $instituciones = Cache::remember('eventual_instituciones_activas', now()->addDay(), function () {
            return EventualInstitucion::activas()->orderBy('nombre')->get();
        });

        // Obtener medios de pago activos
        $mediosDePago = Cache::remember('medios_de_pago_activos', now()->addDay(), function () {
            return MedioDePago::activos()->ordenado()->get();
        });

        return view('livewire.tesoreria.eventuales.eventuales', [
            'eventuales' => $eventuales,
            'subtotales' => $subtotales,
            'totalesPorInstitucion' => $this->totalesPorInstitucion,
            'generalTotal' => $this->generalTotal,
            'instituciones' => $instituciones,
            'mediosDePago' => $mediosDePago,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'eventualModal']);
    }

    public function store()
    {


        if (empty($this->orden_cobro)) {
            $this->orden_cobro = null;
        }
        if (empty($this->ingreso)) {
            $this->ingreso = null;
        }

        $this->validate([
            'fecha' => 'required|date',
            'ingreso' => 'nullable|integer',
            'institucion' => 'nullable|string|max:255',
            'titular' => 'nullable|string|max:255',
            'monto' => 'required|numeric',
            'medio_de_pago' => 'required|string|max:255',
            'detalle' => 'nullable|string',
            'orden_cobro' => 'nullable|string|max:255',
            'recibo' => 'nullable|string|max:255',
        ]);

        $datos = $this->convertirCamposAMayusculas(
            ['institucion', 'titular', 'detalle', 'orden_cobro', 'recibo', 'medio_de_pago'],
            [
                'fecha' => $this->fecha,
                'ingreso' => $this->ingreso,
                'institucion' => $this->institucion,
                'titular' => $this->titular,
                'monto' => $this->monto,
                'medio_de_pago' => $this->medio_de_pago,
                'detalle' => $this->detalle,
                'orden_cobro' => $this->orden_cobro,
                'recibo' => $this->recibo,
            ]
        );

        try {
            DB::beginTransaction();
            Model::create($datos);
            $this->clearCache();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Error al crear el eventual: ' . $e->getMessage(), 'toast' => true]);
            return;
        }

        $this->resetInput();
        $this->emit('eventualStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Eventual creado con éxito!', 'toast' => true]);
    }

    public function edit($id)
    {


        $eventual = Model::findOrFail($id);

        if ($eventual->planilla_id !== null) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'El eventual está incluído en una planilla y no se puede modificar.'
            ]);
            $this->dispatchBrowserEvent('close-modal');
            return;
        }

        $this->eventual_id = $id;
        $this->fecha = Carbon::parse($eventual->fecha)->format('Y-m-d');
        $this->ingreso = $eventual->ingreso;
        $this->institucion = $eventual->institucion ? mb_strtoupper($eventual->institucion, 'UTF-8') : null;
        $this->titular = $eventual->titular;
        $this->monto = $eventual->monto;
        $this->medio_de_pago = $eventual->medio_de_pago ? mb_strtoupper($eventual->medio_de_pago, 'UTF-8') : null;
        $this->detalle = $eventual->detalle;
        $this->orden_cobro = $eventual->orden_cobro;
        $this->recibo = $eventual->recibo;

        // Abrir el modal solo si la edición está permitida
        $this->dispatchBrowserEvent('show-modal', ['id' => 'eventualModal']);
    }

    public function update()
    {
        if (empty($this->orden_cobro)) {
            $this->orden_cobro = null;
        }
        if (empty($this->ingreso)) {
            $this->ingreso = null;
        }

        $validatedData = $this->validate([
            'fecha' => 'required|date',
            'ingreso' => 'nullable|integer',
            'institucion' => 'nullable|string|max:255',
            'titular' => 'nullable|string|max:255',
            'monto' => 'required|numeric',
            'medio_de_pago' => 'required|string|max:255',
            'detalle' => 'nullable|string',
            'orden_cobro' => 'nullable|string|max:255',
            'recibo' => 'nullable|string|max:255',
        ]);

        if ($this->eventual_id) {
            $eventual = Model::findOrFail($this->eventual_id);
            $datos = $this->convertirCamposAMayusculas(
                ['institucion', 'titular', 'detalle', 'orden_cobro', 'recibo', 'medio_de_pago'],
                $validatedData
            );

            try {
                DB::beginTransaction();
                $eventual->update($datos);
                $this->clearCache();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Error al actualizar el eventual: ' . $e->getMessage(), 'toast' => true]);
                return;
            }

            $this->resetInput();
            $this->emit('eventualUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Eventual actualizado con éxito!', 'toast' => true]);
        }
    }

    public function destroy($id)
    {


        $eventual = Model::findOrFail($id);

        if ($eventual->planilla_id !== null) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'El eventual está incluído en una planilla y no se puede eliminar.'
            ]);
            return;
        }

        try {
            DB::beginTransaction();
            $eventual->delete();
            $this->clearCache();
            DB::commit();
            session()->flash('message', 'Eventual eliminado con éxito.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Error al eliminar el eventual: ' . $e->getMessage()]);
        }
    }

    public function showDetails($id)
    {
        $this->selectedEventual = Model::findOrFail($id);
    }

    public function resetDetails()
    {
        $this->selectedEventual = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->eventual_id = null;
        $this->fecha = Carbon::now()->format('Y-m-d');
        $this->ingreso = null;
        $this->institucion = null;
        $this->titular = null;
        $this->monto = null;
        $this->medio_de_pago = $this->getDefaultMedioDePago();
        $this->detalle = null;
        $this->orden_cobro = null;
        $this->recibo = null;
    }
    private function getDefaultMedioDePago()
    {
        return Cache::remember('default_medio_de_pago_transferencia', now()->addDay(), function () {
            $medio = MedioDePago::activos()
                ->where('nombre', 'like', '%Transferencia%')
                ->first();
            $nombre = $medio ? $medio->nombre : 'Transferencia';
            return mb_strtoupper($nombre, 'UTF-8');
        });
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->clearCache();
    }

    public function updatingMes()
    {
        $this->resetPage();
        $this->clearCache();
    }

    public function updatingYear()
    {
        $this->resetPage();
        $this->clearCache();
    }

    public function toggleConfirmado($id)
    {


        if (auth()->user()->cannot('gestionar_tesoreria') && auth()->user()->cannot('supervisar_tesoreria')) {
            abort(403);
        }

        $eventual = Model::findOrFail($id);

        if ($eventual->planilla_id !== null) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Incluído en una planilla.'
            ]);
            $this->dispatchBrowserEvent('revertCheckbox', ['id' => $id, 'checked' => $eventual->confirmado]);
            return;
        }

        $eventual->confirmado = !$eventual->confirmado;
        $eventual->save();
        $this->clearCache();

        $this->emit('eventualStatusUpdated');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Estado de confirmación actualizado.', 'toast' => true]);
    }
}
