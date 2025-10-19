<?php

namespace App\Http\Livewire\Tesoreria\CajaDiaria;

use Livewire\Component;

use App\Models\Cobro;
use App\Models\Tesoreria\CajaDiaria\ConceptoCobro;
use App\Models\Tesoreria\CajaDiaria\CobroCampoValor;
use App\Models\Tesoreria\CajaDiaria\TesCajaDiarias;
use App\Models\Tesoreria\MedioDePago;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Cobros extends Component
{

    public $fecha;

    public $modal = false;
    public $concepto_id = '';
    public $monto = '';
    public $medio_pago_id = '';
    public $descripcion = '';
    public $recibo = '';
    public $camposDinamicos = [];
    public $conceptoSeleccionado = null;
    public $camposValores = [];
    public $confirmingCobroDeletion = false;
    public $cobroIdToDelete = null;
    public $cobro_id = null;

    protected $listeners = ['refreshComponent' => '$refresh'];

    protected $rules = [
        'fecha' => 'required|date',
        'concepto_id' => 'required|exists:tes_cd_conceptos_cobro,id',
        'monto' => 'required|numeric|min:0',
        'medio_pago_id' => 'required|exists:tes_medio_de_pagos,id',
        'recibo' => 'nullable|integer|min:1',
        'descripcion' => 'nullable|string|max:1000'
    ];

    public function mount($fecha = null)
    {
        $this->fecha = $fecha ?: now()->format('Y-m-d');
    }



    public function updatedConceptoId()
    {
        $this->cargarCamposDinamicos();
    }

    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'camposValores.')) {
            $campoId = str_replace('camposValores.', '', $propertyName);
            $campo = collect($this->camposDinamicos)->firstWhere('id', $campoId);

            if ($campo) {
                $rules = [];
                if ($campo['requerido']) {
                    $rules[$propertyName] = 'required';
                }
                if ($campo['tipo'] === 'number') {
                    $rules[$propertyName] = isset($rules[$propertyName]) ? $rules[$propertyName] . '|numeric' : 'numeric';
                } elseif ($campo['tipo'] === 'date') {
                    $rules[$propertyName] = isset($rules[$propertyName]) ? $rules[$propertyName] . '|date' : 'date';
                }

                if (!empty($rules)) {
                    $this->validateOnly($propertyName, $rules);
                }
            } else {
                // Campo dinámico no encontrado, inicializarlo si no existe
                if (!isset($this->camposValores[$campoId])) {
                    $this->camposValores[$campoId] = '';
                }
            }
        } else {
            $this->validateOnly($propertyName);
        }
    }

    public function cargarCamposDinamicos()
    {
        if ($this->concepto_id) {
            $concepto = ConceptoCobro::with('campos')->find($this->concepto_id);
            $this->conceptoSeleccionado = $concepto;
            $this->camposDinamicos = $concepto ? $concepto->campos->toArray() : [];
            // Inicializar valores de campos dinámicos
            $this->camposValores = [];
            foreach ($this->camposDinamicos as $campo) {
                $this->camposValores[$campo['id']] = '';
            }
            $this->emit('dynamicFieldsLoaded');
        } else {
            $this->conceptoSeleccionado = null;
            $this->camposDinamicos = [];
            $this->camposValores = [];
        }
    }

    public function render()
    {
        $cobros = Cobro::with(['concepto', 'campoValores.campo'])
            ->whereDate('fecha', $this->fecha)
            ->orderBy('recibo', 'asc')
            ->get();

        // Agrupar cobros por concepto y luego por medio de pago
        $cobrosAgrupados = [];
        foreach ($cobros as $cobro) {
            $conceptoNombre = $cobro->concepto ? $cobro->concepto->nombre : 'Sin Concepto';
            $medioPago = $cobro->medio_pago;

            if (!isset($cobrosAgrupados[$conceptoNombre])) {
                $cobrosAgrupados[$conceptoNombre] = [];
            }
            if (!isset($cobrosAgrupados[$conceptoNombre][$medioPago])) {
                $cobrosAgrupados[$conceptoNombre][$medioPago] = [];
            }

            $cobrosAgrupados[$conceptoNombre][$medioPago][] = $cobro;
        }

        // Ordenar conceptos alfabéticamente y medios de pago alfabéticamente
        ksort($cobrosAgrupados);
        foreach ($cobrosAgrupados as $concepto => &$medios) {
            ksort($medios);
        }

        $conceptos = ConceptoCobro::activos()->ordenado()->get();
        $mediosPago = MedioDePago::activos()->ordenado()->get();

        return view('livewire.tesoreria.caja-diaria.cobros', [
            'cobrosAgrupados' => $cobrosAgrupados,
            'conceptos' => $conceptos,
            'mediosPago' => $mediosPago
        ]);
    }

    public function create()
    {
        $cajaDiaria = TesCajaDiarias::whereDate('fecha', $this->fecha)->first();

        if (!$cajaDiaria) {
            $this->dispatchBrowserEvent('swal:alert', [
                'type' => 'warning',
                'title' => 'No existe caja inicial',
                'text' => 'No se puede crear un nuevo cobro porque no existe una caja inicial para la fecha seleccionada.',
                'modalToClose' => 'cobroModal',
            ]);
            return;
        }

        $this->resetForm();

        // Establecer 'Efectivo' como medio de pago predeterminado
        $medioPagoEfectivo = MedioDePago::where('nombre', 'Efectivo')->first();
        if ($medioPagoEfectivo) {
            $this->medio_pago_id = $medioPagoEfectivo->id;
        }
    }

    public function edit($id)
    {
        $cobro = Cobro::with('campoValores')->find($id);
        if ($cobro) {
            $this->cobro_id = $cobro->id;
            $this->fecha = $cobro->fecha->format('Y-m-d');
            $this->concepto_id = $cobro->concepto_id;
            $this->monto = $cobro->monto;

            $medioPago = MedioDePago::where('nombre', $cobro->medio_pago)->first();
            $this->medio_pago_id = $medioPago ? $medioPago->id : '';

            $this->descripcion = $cobro->descripcion;
            $this->recibo = $cobro->recibo;

            $this->cargarCamposDinamicos();

            $this->camposValores = [];
            foreach ($cobro->campoValores as $valor) {
                $this->camposValores[$valor->campo_id] = $valor->valor;
            }

            $this->dispatchBrowserEvent('show-cobro-modal');
        }
    }

    public function closeModal()
    {
        $this->modal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->cobro_id = null;
        $this->concepto_id = '';
        $this->monto = '';
        $this->medio_pago_id = '';
        $this->descripcion = '';
        $this->recibo = '';
        $this->camposDinamicos = [];
        $this->conceptoSeleccionado = null;
        $this->camposValores = [];
    }

    public function store()
    {
        $this->validate();

        // Validar campos dinámicos
        $reglasDinamicas = [];
        foreach ($this->camposDinamicos as $campo) {
            $key = 'camposValores.' . $campo['id'];
            if ($campo['requerido']) {
                $reglasDinamicas[$key] = 'required';
            }
            if ($campo['tipo'] === 'number') {
                $reglasDinamicas[$key] = isset($reglasDinamicas[$key]) ? $reglasDinamicas[$key] . '|numeric' : 'numeric';
            } elseif ($campo['tipo'] === 'date') {
                $reglasDinamicas[$key] = isset($reglasDinamicas[$key]) ? $reglasDinamicas[$key] . '|date' : 'date';
            }
        }

        if (!empty($reglasDinamicas)) {
            $this->validate($reglasDinamicas);
        }

        DB::transaction(function () {
            $medioPago = MedioDePago::find($this->medio_pago_id);
            $data = [
                'fecha' => $this->fecha,
                'monto' => $this->monto,
                'medio_pago' => $medioPago->nombre,
                'descripcion' => $this->descripcion,
                'recibo' => $this->recibo,
                'concepto_id' => $this->concepto_id,
                'updated_by' => Auth::id()
            ];

            if ($this->cobro_id) {
                $cobro = Cobro::find($this->cobro_id);
                $cobro->update($data);
                session()->flash('message', 'Cobro actualizado correctamente.');
            } else {
                $data['created_by'] = Auth::id();
                $cobro = Cobro::create($data);
                session()->flash('message', 'Cobro registrado correctamente.');
            }

            // Guardar valores de campos dinámicos
            $cobro->campoValores()->delete();
            foreach ($this->camposDinamicos as $campo) {
                if (isset($this->camposValores[$campo['id']]) && !empty($this->camposValores[$campo['id']])) {
                    CobroCampoValor::create([
                        'cobro_id' => $cobro->id,
                        'campo_id' => $campo['id'],
                        'valor' => $this->camposValores[$campo['id']]
                    ]);
                }
            }
        });

        $this->emit('cobroStore');
        $this->emit('refreshComponent');
    }

    public function delete($id)
    {
        $this->cobroIdToDelete = $id;
        $this->confirmingCobroDeletion = true;
    }

    public function cancelDelete()
    {
        $this->cobroIdToDelete = null;
        $this->confirmingCobroDeletion = false;
    }

    public function deleteCobro()
    {
        $cobro = Cobro::find($this->cobroIdToDelete);
        if ($cobro) {
            $cobro->delete();
            session()->flash('message', 'Cobro eliminado correctamente.');
        }

        $this->confirmingCobroDeletion = false;
        $this->cobroIdToDelete = null;
        $this->emit('refreshComponent');
    }

    public function order($column)
    {
        // Implementar ordenamiento si es necesario
    }
}
