<?php

namespace App\Http\Livewire\Tesoreria\EstadosRecaudacion;

use App\Models\Tesoreria\SiifDistribucion;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesPlanillaEr;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Confirmar extends Component
{
    public TesPlanillaEr $planilla;

    protected $listeners = ['$refresh', 'eliminarPlanilla'];

    public function mount(TesPlanillaEr $planilla)
    {
        $this->planilla = $planilla->load([
            'tipo', 'dependencia',
            'items.cfe.cajaConcepto',
            'items.cfe.siifDistribucionDependencia',
            'items.cfe.siifDistribucionTipo',
            'items.cfe.mediosPago',
            'items.cfe.items',
            'items.siifDistribucion',
        ]);
    }

    public function cambiarDistribucion(int $itemId, int $siifDistribucionId)
    {
        $item = TesCfeItem::findOrFail($itemId);

        if ($item->planilla_er_id !== $this->planilla->id) {
            abort(403);
        }

        if (empty($siifDistribucionId)) {
            $item->update([
                'siif_distribucion_id' => null,
                'confirmado' => false,
            ]);
            $this->desconfirmarPlanillaSiEsNecesario();
            $this->refrescarPlanilla();
            $this->dispatchBrowserEvent('swal:toast-success', [
                'text' => 'Distribución SIIF removida.',
            ]);
            return;
        }

        $distribucion = SiifDistribucion::findOrFail($siifDistribucionId);
        $concepto = mb_strtolower($distribucion->concepto);
        $esNocturno = str_contains($concepto, 'nocturno');
        $planillaEsNocturna = mb_strtolower(trim($this->planilla->turno ?? '')) === 'nocturno';

        if ($esNocturno === $planillaEsNocturna) {
            $this->aplicarCambioDistribucion($item, $siifDistribucionId);
            return;
        }

        if ($esNocturno && !$planillaEsNocturna) {
            $this->manejarCambioANocturno($item, $siifDistribucionId);
        } else {
            $this->manejarCambioANoNocturno($item, $siifDistribucionId);
        }
    }

    public function confirmarCambioPlanilla(int $itemId, int $distribucionId, ?int $targetPlanillaId, string $action, bool $incluirAdicionales = false)
    {
        $item = TesCfeItem::findOrFail($itemId);

        try {
            DB::beginTransaction();

            if ($action === 'crear') {
                $targetPlanilla = $this->crearNuevaPlanilla($distribucionId);
                $targetPlanillaId = $targetPlanilla->id;

                if ($incluirAdicionales) {
                    $distribucion = SiifDistribucion::findOrFail($distribucionId);
                    $esNocturno = str_contains(mb_strtolower($distribucion->concepto), 'nocturno');
                    $otrosIds = $this->buscarOtrosItemsPendientes($item, $esNocturno)->pluck('id')->toArray();

                    if (!empty($otrosIds)) {
                        TesCfeItem::whereIn('id', $otrosIds)
                            ->update(['planilla_er_id' => $targetPlanillaId]);
                    }
                }
            }

            $item->update([
                'siif_distribucion_id' => $distribucionId,
                'planilla_er_id' => $targetPlanillaId,
                'confirmado' => false,
            ]);

            $this->desconfirmarPlanillaSiEsNecesario();

            if ($targetPlanillaId) {
                $targetPlanilla = TesPlanillaEr::find($targetPlanillaId);
                if ($targetPlanilla && $targetPlanilla->confirmada) {
                    $targetPlanilla->update(['confirmada' => false]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'Error al reasignar el ítem: ' . $e->getMessage(),
            ]);
            return;
        }

        $this->refrescarPlanilla();

        $texto = 'El ítem fue movido a la planilla correspondiente.';
        if ($action === 'crear' && $incluirAdicionales) {
            $texto .= ' También se integraron otros ítems pendientes.';
        }

        $this->dispatchBrowserEvent('swal:success', [
            'title' => 'Ítem reasignado',
            'text' => $texto,
        ]);
    }

    public function cancelarCambioPlanilla()
    {
        $this->refrescarPlanilla();
    }

    private function aplicarCambioDistribucion(TesCfeItem $item, int $siifDistribucionId)
    {
        $item->update([
            'siif_distribucion_id' => $siifDistribucionId,
            'confirmado' => false,
        ]);
        $this->desconfirmarPlanillaSiEsNecesario();
        $this->refrescarPlanilla();
        $this->dispatchBrowserEvent('swal:toast-success', [
            'text' => 'Distribución SIIF actualizada.',
        ]);
    }

    private function refrescarPlanilla()
    {
        $this->planilla = TesPlanillaEr::with([
            'tipo', 'dependencia',
            'items.cfe.cajaConcepto',
            'items.cfe.siifDistribucionDependencia',
            'items.cfe.siifDistribucionTipo',
            'items.cfe.mediosPago',
            'items.cfe.items',
            'items.siifDistribucion',
        ])->find($this->planilla->id);
    }

    private function desconfirmarPlanillaSiEsNecesario()
    {
        if ($this->planilla->confirmada) {
            $this->planilla->update(['confirmada' => false]);
        }
    }

    private function manejarCambioANocturno(TesCfeItem $item, int $siifDistribucionId)
    {
        $targetPlanilla = TesPlanillaEr::where('tipo_id', $this->planilla->tipo_id)
            ->where('dependencia_id', $this->planilla->dependencia_id)
            ->where('fecha', $this->planilla->fecha)
            ->where('turno', 'Nocturno')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        if ($targetPlanilla && $targetPlanilla->confirmada) {
            $this->dispatchBrowserEvent('swal:confirmar-cambio-planilla', [
                'title' => 'Cambio a distribución nocturna',
                'html' => "El ítem tiene distribución nocturna pero la planilla actual no es nocturna. " .
                          "Existe la planilla <strong>{$targetPlanilla->numero}</strong> (Nocturna) que ya está <strong>confirmada</strong>.<br><br>" .
                          "¿Desea mover el ítem a esa planilla? Se marcará como no confirmada.",
                'itemId' => $item->id,
                'distribucionId' => $siifDistribucionId,
                'targetPlanillaId' => $targetPlanilla->id,
                'action' => 'mover',
            ]);
        } elseif ($targetPlanilla) {
            $this->dispatchBrowserEvent('swal:confirmar-cambio-planilla', [
                'title' => 'Cambio a distribución nocturna',
                'html' => "El ítem tiene distribución nocturna pero la planilla actual no es nocturna. " .
                          "Existe la planilla <strong>{$targetPlanilla->numero}</strong> (Nocturna, pendiente).<br><br>" .
                          "¿Desea mover el ítem a esa planilla?",
                'itemId' => $item->id,
                'distribucionId' => $siifDistribucionId,
                'targetPlanillaId' => $targetPlanilla->id,
                'action' => 'mover',
            ]);
        } else {
            $otrosItems = $this->buscarOtrosItemsPendientes($item, true);
            $count = $otrosItems->count();

            $html = "El ítem tiene distribución nocturna pero no existe una planilla nocturna para esta fecha.";
            if ($count > 0) {
                $html .= "<br><br>Hay <strong>{$count} ítem(s)</strong> sin asignar con la misma fecha y dependencia SIIF que también tienen distribución nocturna.";
            }
            $html .= "<br><br>Se creará una nueva planilla con turno <strong>Nocturno</strong>.<br><br>¿Desea continuar?";

            $this->dispatchBrowserEvent('swal:confirmar-cambio-planilla', [
                'title' => 'Cambio a distribución nocturna',
                'html' => $html,
                'itemId' => $item->id,
                'distribucionId' => $siifDistribucionId,
                'targetPlanillaId' => null,
                'action' => 'crear',
                'otrosItemsCount' => $count,
            ]);
        }
    }

    private function manejarCambioANoNocturno(TesCfeItem $item, int $siifDistribucionId)
    {
        $targetPlanilla = TesPlanillaEr::where('tipo_id', $this->planilla->tipo_id)
            ->where('dependencia_id', $this->planilla->dependencia_id)
            ->where('fecha', $this->planilla->fecha)
            ->where(function ($q) {
                $q->whereNull('turno')->orWhere('turno', '!=', 'Nocturno');
            })
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        if ($targetPlanilla && $targetPlanilla->confirmada) {
            $this->dispatchBrowserEvent('swal:confirmar-cambio-planilla', [
                'title' => 'Cambio a distribución no nocturna',
                'html' => "El ítem tiene distribución <strong>no nocturna</strong> pero la planilla actual es nocturna. " .
                          "Existe la planilla <strong>{$targetPlanilla->numero}</strong> que ya está <strong>confirmada</strong>.<br><br>" .
                          "¿Desea mover el ítem a esa planilla? Se marcará como no confirmada.",
                'itemId' => $item->id,
                'distribucionId' => $siifDistribucionId,
                'targetPlanillaId' => $targetPlanilla->id,
                'action' => 'mover',
            ]);
        } elseif ($targetPlanilla) {
            $this->dispatchBrowserEvent('swal:confirmar-cambio-planilla', [
                'title' => 'Cambio a distribución no nocturna',
                'html' => "El ítem tiene distribución <strong>no nocturna</strong> pero la planilla actual es nocturna. " .
                          "Existe la planilla <strong>{$targetPlanilla->numero}</strong> (pendiente).<br><br>" .
                          "¿Desea mover el ítem a esa planilla?",
                'itemId' => $item->id,
                'distribucionId' => $siifDistribucionId,
                'targetPlanillaId' => $targetPlanilla->id,
                'action' => 'mover',
            ]);
        } else {
            $otrosItems = $this->buscarOtrosItemsPendientes($item, false);
            $count = $otrosItems->count();

            $html = "El ítem tiene distribución <strong>no nocturna</strong> pero la planilla actual es nocturna y no existe otra planilla para esta fecha.";
            if ($count > 0) {
                $html .= "<br><br>Hay <strong>{$count} ítem(s)</strong> sin asignar con la misma fecha y dependencia SIIF que también tienen distribución no nocturna.";
            }
            $html .= "<br><br>Se creará una nueva planilla.<br><br>¿Desea continuar?";

            $this->dispatchBrowserEvent('swal:confirmar-cambio-planilla', [
                'title' => 'Cambio a distribución no nocturna',
                'html' => $html,
                'itemId' => $item->id,
                'distribucionId' => $siifDistribucionId,
                'targetPlanillaId' => null,
                'action' => 'crear',
                'otrosItemsCount' => $count,
            ]);
        }
    }

    private function crearNuevaPlanilla(int $distribucionId): TesPlanillaEr
    {
        $distribucion = SiifDistribucion::findOrFail($distribucionId);
        $esNocturno = str_contains(mb_strtolower($distribucion->concepto), 'nocturno');
        $turno = $esNocturno ? 'Nocturno' : null;

        $fechaCarbon = \Carbon\Carbon::parse($this->planilla->fecha);
        $prefijo = $fechaCarbon->format('d-m-Y');

        $ultimo = TesPlanillaEr::whereYear('fecha', $fechaCarbon->year)
            ->whereMonth('fecha', $fechaCarbon->month)
            ->where('numero', 'like', $prefijo . '-%')
            ->orderBy('id', 'desc')
            ->first();

        $siguiente = $ultimo ? (int) last(explode('-', $ultimo->numero)) + 1 : 1;
        $numero = $prefijo . '-' . $siguiente;

        return TesPlanillaEr::create([
            'fecha' => $this->planilla->fecha,
            'numero' => $numero,
            'tipo_id' => $this->planilla->tipo_id,
            'dependencia_id' => $this->planilla->dependencia_id,
            'turno' => $turno,
            'er_numero' => null,
            'egresos_numero' => null,
            'ingresos_numero' => null,
            'transferencia_fecha' => null,
            'transferencia_confirmacion' => null,
        ]);
    }

    private function buscarOtrosItemsPendientes(TesCfeItem $item, bool $nocturno)
    {
        return TesCfeItem::whereNull('planilla_er_id')
            ->where('id', '!=', $item->id)
            ->whereHas('cfe', function ($q) use ($item) {
                $q->where('fecha', $item->cfe->fecha)
                  ->where('siif_distribucion_dependencia_id', $item->cfe->siif_distribucion_dependencia_id);
            })
            ->whereHas('siifDistribucion', function ($q) use ($nocturno) {
                if ($nocturno) {
                    $q->whereRaw('LOWER(concepto) LIKE ?', ['%nocturno%']);
                } else {
                    $q->whereRaw('LOWER(concepto) NOT LIKE ?', ['%nocturno%']);
                }
            })
            ->get();
    }

    public function eliminarPlanilla(int $id)
    {
        if ($id !== $this->planilla->id) {
            abort(403);
        }

        $user = auth()->user();
        if (!$user || !$user->hasAnyPermission(['tesoreria.supervisar'])) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'No tiene permisos para eliminar planillas.',
            ]);
            return;
        }

        try {
            DB::beginTransaction();
            $this->planilla->items()->update(['planilla_er_id' => null]);
            $this->planilla->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'Error al eliminar la planilla: ' . $e->getMessage(),
            ]);
            return;
        }

        return redirect()->route('tesoreria.gestion-cfe.estados-recaudacion.no-confirmadas');
    }

    public function toggleItemConfirmado(int $itemId)
    {
        $item = TesCfeItem::findOrFail($itemId);

        if ($item->planilla_er_id !== $this->planilla->id) {
            abort(403);
        }

        $item->update(['confirmado' => !$item->confirmado]);
        $this->refrescarPlanilla();
    }

    public function toggleConfirmadoCfe(int $cfeId)
    {
        $items = TesCfeItem::where('tes_cfe_id', $cfeId)
            ->where('planilla_er_id', $this->planilla->id)
            ->get();

        if ($items->isEmpty()) {
            abort(403);
        }

        $todosConfirmados = $items->every(fn($i) => $i->confirmado);

        TesCfeItem::where('tes_cfe_id', $cfeId)
            ->where('planilla_er_id', $this->planilla->id)
            ->update(['confirmado' => !$todosConfirmados]);

        $this->refrescarPlanilla();
    }

    public function toggleConfirmada()
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyPermission(['tesoreria.supervisar'])) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'No tiene permisos para confirmar planillas.',
            ]);
            return;
        }

        $itemsSinConfirmar = $this->planilla->items->where('confirmado', false);

        if ($itemsSinConfirmar->isNotEmpty()) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'No se puede confirmar la planilla. Todos los ítems deben estar confirmados primero.',
            ]);
            return;
        }

        $this->planilla->update(['confirmada' => !$this->planilla->confirmada]);
        $this->planilla = $this->planilla->fresh([
            'tipo', 'dependencia',
            'items.cfe.cajaConcepto',
            'items.cfe.siifDistribucionDependencia',
            'items.cfe.siifDistribucionTipo',
            'items.cfe.mediosPago',
            'items.cfe.items',
            'items.siifDistribucion',
        ]);
    }

    public function render()
    {
        $planillaItemIds = $this->planilla->items->pluck('id')->toArray();

        $itemsPorCfe = [];
        foreach ($this->planilla->items as $itemPlanilla) {
            $cfe = $itemPlanilla->cfe;
            if (!$cfe) continue;

            $cfeLabel = "{$cfe->documento_tipo} {$cfe->documento_serie}-{$cfe->documento_numero}";
            if (isset($itemsPorCfe[$cfeLabel])) continue;

            $itemsConFlag = $cfe->items->map(function ($i) use ($planillaItemIds) {
                $i->enPlanilla = in_array($i->id, $planillaItemIds);
                return $i;
            });

            $itemsPorCfe[$cfeLabel] = $itemsConFlag;
        }

        $paresUnicos = [];
        foreach ($this->planilla->items as $item) {
            $cfe = $item->cfe;
            if (!$cfe || !$cfe->siif_distribucion_tipo_id || !$cfe->siif_distribucion_dependencia_id) continue;
            $key = $cfe->siif_distribucion_tipo_id . '-' . $cfe->siif_distribucion_dependencia_id;
            $paresUnicos[$key] = [
                'tipo_id' => $cfe->siif_distribucion_tipo_id,
                'dependencia_id' => $cfe->siif_distribucion_dependencia_id,
            ];
        }

        $opcionesPorTipoDep = [];
        foreach ($paresUnicos as $key => $par) {
            $opcionesPorTipoDep[$key] = SiifDistribucion::where('tipo_id', $par['tipo_id'])
                ->where('dependencia_id', $par['dependencia_id'])
                ->whereNull('deleted_at')
                ->get()
                ->unique(fn($d) => $d->tipo_id . '-' . $d->dependencia_id . '-' . $d->concepto)
                ->values();
        }

        $gruposRecaudacion = $this->calcularGruposRecaudacion();

        $totalGeneral = $this->planilla->items->sum('importe');

        return view('livewire.tesoreria.estados-recaudacion.confirmar', compact(
            'itemsPorCfe', 'opcionesPorTipoDep', 'gruposRecaudacion', 'totalGeneral'
        ))->extends('layouts.app')->section('content');
    }

    private function calcularGruposRecaudacion(): array
    {
        $items = $this->planilla->items;
        $grupos = [];
        $agregados = [];

        foreach ($items as $item) {
            $cfe = $item->cfe;
            if (!$cfe) continue;

            $dep = $cfe->siifDistribucionDependencia;
            $tipo = $cfe->siifDistribucionTipo;
            $tabKey = ($dep?->id ?? 'X') . '-' . ($tipo?->id ?? 'X');
            $label = ($dep?->abreviatura ?? 'S/D') . ' — ' . ($tipo?->tipo ?? 'S/T');
            $distKey = $item->siifDistribucion?->concepto ?? $cfe->cajaConcepto?->caja_concepto ?? 'Sin distribución';
            $cfeKey = $item->tes_cfe_id;
            $uniq = "{$tabKey}|{$distKey}|{$cfeKey}";

            if (!isset($grupos[$tabKey])) {
                $grupos[$tabKey] = [
                    'label' => $label,
                    'distribuciones' => [],
                    'total_efectivo' => 0,
                    'total_cheque' => 0,
                    'total_transferencia' => 0,
                    'total_pos' => 0,
                ];
            }

            if (!isset($grupos[$tabKey]['distribuciones'][$distKey])) {
                $grupos[$tabKey]['distribuciones'][$distKey] = [
                    'concepto' => $distKey,
                    'items' => [],
                    'total_efectivo' => 0,
                    'total_cheque' => 0,
                    'total_transferencia' => 0,
                    'total_pos' => 0,
                ];
            }

            if (!isset($agregados[$uniq])) {
                $agregados[$uniq] = [
                    'cfe' => $cfe,
                    'sumImporte' => 0,
                ];
            }
            $agregados[$uniq]['sumImporte'] += $item->importe;
        }

        foreach ($agregados as $uniq => $aggr) {
            [$tabKey, $distKey, $cfeId] = explode('|', $uniq, 3);
            $cfe = $aggr['cfe'];
            $sumImporte = $aggr['sumImporte'];

            $cfeTotalItems = $cfe->items->sum('importe');
            $proporcion = $cfeTotalItems > 0 ? $sumImporte / $cfeTotalItems : 0;

            $efectivo = 0; $cheque = 0; $transferencia = 0; $pos = 0;

            foreach ($cfe->mediosPago as $mp) {
                $tipoStr = mb_strtolower($mp->medio_pago_tipo);
                $valorProrated = round($mp->medio_pago_valor * $proporcion, 2);

                if (str_contains($tipoStr, 'efectivo')) {
                    $efectivo += $valorProrated;
                } elseif (str_contains($tipoStr, 'cheque')) {
                    $cheque += $valorProrated;
                } elseif (str_contains($tipoStr, 'transferencia')) {
                    $transferencia += $valorProrated;
                } elseif (str_contains($tipoStr, 'tarjeta') || str_contains($tipoStr, 'debito') || str_contains($tipoStr, 'débito')) {
                    $pos += $valorProrated;
                }
            }

            $rowData = [
                'cfe' => $cfe,
                'efectivo' => $efectivo,
                'cheque' => $cheque,
                'transferencia' => $transferencia,
                'pos' => $pos,
            ];

            $grupos[$tabKey]['distribuciones'][$distKey]['items'][] = $rowData;
            $grupos[$tabKey]['distribuciones'][$distKey]['total_efectivo'] += $efectivo;
            $grupos[$tabKey]['distribuciones'][$distKey]['total_cheque'] += $cheque;
            $grupos[$tabKey]['distribuciones'][$distKey]['total_transferencia'] += $transferencia;
            $grupos[$tabKey]['distribuciones'][$distKey]['total_pos'] += $pos;
            $grupos[$tabKey]['total_efectivo'] += $efectivo;
            $grupos[$tabKey]['total_cheque'] += $cheque;
            $grupos[$tabKey]['total_transferencia'] += $transferencia;
            $grupos[$tabKey]['total_pos'] += $pos;
        }

        return $grupos;
    }
}
