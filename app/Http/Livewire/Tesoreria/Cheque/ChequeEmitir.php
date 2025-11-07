<?php
// app/Http/Livewire/Tesoreria/Cheque/ChequeEmitir.php
namespace App\Http\Livewire\Tesoreria\Cheque;

use App\Models\Tesoreria\Cheque;
use App\Models\Tesoreria\PlanillaCheque;
use Livewire\Component;
use Livewire\WithPagination;

class ChequeEmitir extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $showModal = false;
    public $selectedChequeId = null;
    public $serie = '';
    public $beneficiario = '';
    public $monto = '';
    public $concepto = '';
    public $fecha_emision = '';
    public $selectedCheque = null;
    public $selectedChequeAnular = null;
    public $motivo_anulacion = '';
    public $selectedCheques = [];
    public $chequesEmitidos = null;
    public $selectAll = false;
    public $selectedChequeEditar = null;
    public $edit_fecha_emision = '';
    public $edit_monto = '';
    public $edit_beneficiario = '';
    public $edit_concepto = '';
    public $edit_serie = '';
    public $beneficiariosSugerencias = [];
    public $conceptosSugerencias = [];
    protected $listeners = [
        'refreshEmitir' => '$refresh',
        'chequesActualizados' => '$refresh'
    ];


    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedBeneficiario()
    {
        if (strlen($this->beneficiario) >= 2) {
            $this->beneficiariosSugerencias = Cheque::sinAnulacionesPlanilla()
                ->where('beneficiario', 'like', '%' . $this->beneficiario . '%')
                ->whereNotNull('beneficiario')
                ->where('beneficiario', '!=', '')
                ->select('beneficiario')
                ->distinct()
                ->orderBy('beneficiario')
                ->limit(10)
                ->pluck('beneficiario')
                ->toArray();
        } else {
            $this->beneficiariosSugerencias = [];
        }
    }

    public function seleccionarBeneficiario($beneficiario)
    {
        $this->beneficiario = $beneficiario;
        $this->beneficiariosSugerencias = [];
    }

    public function updatedConcepto()
    {
        if (strlen($this->concepto) >= 2) {
            $this->conceptosSugerencias = Cheque::sinAnulacionesPlanilla()
                ->where('concepto', 'like', '%' . $this->concepto . '%')
                ->whereNotNull('concepto')
                ->where('concepto', '!=', '')
                ->select('concepto')
                ->distinct()
                ->orderBy('concepto')
                ->limit(10)
                ->pluck('concepto')
                ->toArray();
        } else {
            $this->conceptosSugerencias = [];
        }
    }

    public function seleccionarConcepto($concepto)
    {
        $this->concepto = $concepto;
        $this->conceptosSugerencias = [];
    }

    public function updatedEditBeneficiario()
    {
        if (strlen($this->edit_beneficiario) >= 2) {
            $this->beneficiariosSugerencias = Cheque::sinAnulacionesPlanilla()
                ->where('beneficiario', 'like', '%' . $this->edit_beneficiario . '%')
                ->whereNotNull('beneficiario')
                ->where('beneficiario', '!=', '')
                ->select('beneficiario')
                ->distinct()
                ->orderBy('beneficiario')
                ->limit(10)
                ->pluck('beneficiario')
                ->toArray();
        } else {
            $this->beneficiariosSugerencias = [];
        }
    }

    public function updatedEditConcepto()
    {
        if (strlen($this->edit_concepto) >= 2) {
            $this->conceptosSugerencias = Cheque::sinAnulacionesPlanilla()
                ->where('concepto', 'like', '%' . $this->edit_concepto . '%')
                ->whereNotNull('concepto')
                ->where('concepto', '!=', '')
                ->select('concepto')
                ->distinct()
                ->orderBy('concepto')
                ->limit(10)
                ->pluck('concepto')
                ->toArray();
        } else {
            $this->conceptosSugerencias = [];
        }
    }

    public function updatedSelectedCheques()
    {
        // Actualizar el estado del checkbox maestro
        $this->selectAll = $this->chequesEmitidos &&
                          count($this->selectedCheques) === $this->chequesEmitidos->count() &&
                          $this->chequesEmitidos->count() > 0;
    }

    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedCheques = $this->chequesEmitidos ? $this->chequesEmitidos->pluck('id')->toArray() : [];
        } else {
            $this->selectedCheques = [];
        }
    }

    public function selectAllCheques()
    {
        if ($this->chequesEmitidos) {
            $this->selectedCheques = $this->chequesEmitidos->pluck('id')->toArray();
            $this->selectAll = true;
        }
    }

    public function deselectAllCheques()
    {
        $this->selectedCheques = [];
        $this->selectAll = false;
    }


    public function toggleChequeSelection($chequeId)
    {
        if (in_array($chequeId, $this->selectedCheques)) {
            $this->selectedCheques = array_diff($this->selectedCheques, [$chequeId]);
        } else {
            $this->selectedCheques[] = $chequeId;
        }
    }

    public function formarPlanilla()
    {
        if (empty($this->selectedCheques)) {
            $this->dispatchBrowserEvent('swal', [
                'title' => 'Selección requerida',
                'text' => 'Debe seleccionar al menos un cheque para formar la planilla.',
                'type' => 'warning'
            ]);
            return;
        }

        // Crear la planilla directamente
        $planilla = PlanillaCheque::create([
            'numero_planilla' => 'PL-' . now()->format('Y') . '-' . str_pad(PlanillaCheque::count() + 1, 4, '0', STR_PAD_LEFT),
            'fecha_generacion' => now(),
            'estado' => 'generada',
            'generada_por' => auth()->id()
        ]);

        // Asignar los cheques seleccionados a la planilla
        Cheque::whereIn('id', $this->selectedCheques)
            ->where('estado', 'emitido')
            ->whereNull('planilla_id')
            ->update(['planilla_id' => $planilla->id]);

        // Limpiar la selección
        $this->selectedCheques = [];
        $this->selectAll = false;

        // Mostrar mensaje de éxito y redirigir al listado de planillas
        $this->dispatchBrowserEvent('swal', [
            'title' => 'Planilla creada exitosamente',
            'text' => "Se creó la planilla {$planilla->numero_planilla} con " . count($this->selectedCheques) . " cheques.",
            'type' => 'success'
        ]);

        // Emitir evento para actualizar el listado de planillas
        $this->emit('planillaCreada');

        // Redirigir al listado de planillas después de un breve delay
        $this->dispatchBrowserEvent('redirect-after-success', [
            'url' => route('tesoreria.cheques.index') . '#planillas',
            'delay' => 2000
        ]);
    }

    public function getTotalSeleccionadosProperty()
    {
        if (empty($this->selectedCheques) || !$this->chequesEmitidos) {
            return 0;
        }

        return $this->chequesEmitidos->whereIn('id', $this->selectedCheques)->sum('monto');
    }

    public function getTotalEmitidosProperty()
    {
        return $this->chequesEmitidos ? $this->chequesEmitidos->sum('monto') : 0;
    }

    public function openEmitirModal($chequeId)
    {
        $this->selectedChequeId = $chequeId;
        $cheque = Cheque::with('cuentaBancaria.banco')
            ->where('id', $chequeId)
            ->where('estado', 'disponible')
            ->first();

        if (!$cheque) {
            // Handle case where cheque is not found
            return;
        }

        $this->selectedCheque = $cheque->toArray();
        $this->serie = $cheque->serie; // Set the serie property

        $this->beneficiario = '';
        $this->monto = '';
        $this->concepto = '';
        $this->fecha_emision = now()->format('Y-m-d');
        $this->showModal = true;
        $this->dispatchBrowserEvent('showEmitirModal');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedChequeId = null;
        $this->selectedCheque = null;
        $this->beneficiario = '';
        $this->monto = '';
        $this->concepto = '';
        $this->fecha_emision = '';
        $this->serie = '';
    }

    public function closeAnularModal()
    {
        $this->selectedChequeAnular = null;
        $this->motivo_anulacion = '';
    }

    public function closeEditarModal()
    {
        $this->selectedChequeEditar = null;
        $this->edit_fecha_emision = '';
        $this->edit_monto = '';
        $this->edit_beneficiario = '';
        $this->edit_concepto = '';
        $this->edit_serie = '';
    }

    public function emitir()
    {
        $this->validate([
            'beneficiario' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'concepto' => 'required|string|max:500',
            'fecha_emision' => 'required|date',
            'serie' => 'nullable|string|max:11',
        ]);

        $cheque = Cheque::find($this->selectedChequeId);
        if ($cheque && $cheque->estado === 'disponible') {
            $cheque->update([
                'estado' => 'emitido',
                'fecha_emision' => $this->fecha_emision,
                'beneficiario' => $this->beneficiario,
                'monto' => $this->monto,
                'concepto' => $this->concepto,
                'serie' => $this->serie,
                'emitido_por' => auth()->id()
            ]);

            $this->closeModal();
            $this->dispatchBrowserEvent('hideEmitirModal');
            $this->emit('chequeEmitido');
        }
    }

    public function openAnularModal($chequeId)
    {
        $this->selectedChequeAnular = Cheque::with('cuentaBancaria.banco')
            ->where('id', $chequeId)
            ->where('estado', 'disponible')
            ->first()
            ->toArray();
        $this->motivo_anulacion = '';
        $this->dispatchBrowserEvent('showAnularModal');
    }

    public function openEditarModal($chequeId)
    {
        $this->selectedChequeEditar = Cheque::with('cuentaBancaria.banco')
            ->where('id', $chequeId)
            ->where('estado', 'emitido')
            ->first()
            ->toArray();

        if ($this->selectedChequeEditar) {
            $this->edit_fecha_emision = $this->selectedChequeEditar['fecha_emision'];
            $this->edit_monto = $this->selectedChequeEditar['monto'];
            $this->edit_beneficiario = $this->selectedChequeEditar['beneficiario'];
            $this->edit_concepto = $this->selectedChequeEditar['concepto'];
            $this->edit_serie = $this->selectedChequeEditar['serie'];
            $this->dispatchBrowserEvent('showEditarModal');
        }
    }

    public function anular()
    {
        $this->validate([
            'motivo_anulacion' => 'required|string|max:500',
        ]);

        $cheque = Cheque::find($this->selectedChequeAnular['id']);
        if ($cheque && $cheque->estado === 'disponible') {
            $cheque->update([
                'estado' => 'anulado',
                'fecha_anulacion' => now(),
                'motivo_anulacion' => $this->motivo_anulacion,
                'anulado_por' => auth()->id()
            ]);

            // Limpiar datos del modal inmediatamente
            $this->selectedChequeAnular = null;
            $this->motivo_anulacion = '';

            // Cerrar modal usando el mismo patrón que los arrendamientos
            $this->dispatchBrowserEvent('hideAnularModal');

            // Emitir evento para mostrar mensaje
            $this->emit('chequeAnulado');
        } else {
            $this->dispatchBrowserEvent('swal', ['title' => 'Error al anular el cheque', 'type' => 'error']);
        }
    }

    public function editar()
    {
        $this->validate([
            'edit_fecha_emision' => 'required|date',
            'edit_monto' => 'required|numeric|min:0.01',
            'edit_beneficiario' => 'required|string|max:255',
            'edit_concepto' => 'required|string|max:500',
            'edit_serie' => 'nullable|string|max:11',
        ]);

        $cheque = Cheque::find($this->selectedChequeEditar['id']);
        if ($cheque && $cheque->estado === 'emitido') {
            $cheque->update([
                'fecha_emision' => $this->edit_fecha_emision,
                'monto' => $this->edit_monto,
                'beneficiario' => $this->edit_beneficiario,
                'concepto' => $this->edit_concepto,
                'serie' => $this->edit_serie,
                'modificado_por' => auth()->id(),
                'fecha_modificacion' => now()
            ]);

            $this->closeEditarModal();
            $this->dispatchBrowserEvent('hideEditarModal');
            $this->emit('chequeEditado');
        } else {
            $this->dispatchBrowserEvent('swal', ['title' => 'Error al editar el cheque', 'type' => 'error']);
        }
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $cheques = Cheque::sinAnulacionesPlanilla()
            ->with('cuentaBancaria.banco')
            ->where('estado', 'disponible')
            ->where(function($query) {
                $query->where('numero_cheque', 'like', "%{$this->search}%")
                      ->orWhere('serie', 'like', "%{$this->search}%")
                      ->orWhereHas('cuentaBancaria.banco', fn($q) => $q->where('codigo', 'like', "%{$this->search}%"));
            })
            ->paginate(5);

        // Obtener cheques emitidos (sin planilla asignada)
        $this->chequesEmitidos = Cheque::sinAnulacionesPlanilla()
            ->with('cuentaBancaria.banco')
            ->where('estado', 'emitido')
            ->whereNull('planilla_id')
            ->orderBy('fecha_emision', 'desc')
            ->get();

        return view('livewire.tesoreria.cheque.cheque-emitir', compact('cheques'));
    }
}
