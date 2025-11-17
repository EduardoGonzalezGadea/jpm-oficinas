<?php

namespace App\Http\Livewire\Tesoreria\Cheque;

use App\Models\Tesoreria\Banco;
use App\Models\Tesoreria\Cheque;
use App\Models\Tesoreria\CuentaBancaria;
use App\Models\Tesoreria\PlanillaCheque;
use Livewire\Component;
use Livewire\WithPagination;

class ChequeReportes extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Propiedades para filtros generales
    public $filtroEstado = '';
    public $filtroFechaIngresoDesde = '';
    public $filtroFechaIngresoHasta = '';
    public $filtroFechaEmisionDesde = '';
    public $filtroFechaEmisionHasta = '';
    public $filtroFechaAnulacionDesde = '';
    public $filtroFechaAnulacionHasta = '';
    public $filtroEnPlanilla = '';
    public $filtroBanco = '';
    public $filtroCuentaBancaria = '';

    public $bancos = [];
    public $cuentasBancarias = [];

    // Propiedades para reportes específicos
    public $reporteMes = '';
    public $reporteAnio = '';
    public $reporteTipo = 'stock'; // stock, anulados_mes, emitidos_mes, planillas_mes, planillas_anuladas_mes, listado_general
    public $showReport = false;

    // Propiedades para paginación
    public $perPage = 10;

    protected $queryString = [
        'reporteTipo' => ['except' => ''],
        'reporteMes' => ['except' => ''],
        'reporteAnio' => ['except' => ''],
        'filtroEstado' => ['except' => ''],
        'filtroEnPlanilla' => ['except' => ''],
        'filtroBanco' => ['except' => ''],
        'filtroCuentaBancaria' => ['except' => ''],
    ];

    public $printMode = false;

    public function mount()
    {
        $this->bancos = Banco::orderBy('codigo')->get();
        $this->cuentasBancarias = collect();
        $this->printMode = request()->query('print', false);
        if ($this->printMode) {
            $this->showReport = true;
        }
    }

    public function updatedFiltroBanco($bancoId)
    {
        if ($bancoId) {
            $this->cuentasBancarias = CuentaBancaria::where('banco_id', $bancoId)->orderBy('numero_cuenta')->get();
        } else {
            $this->cuentasBancarias = collect();
        }
        $this->filtroCuentaBancaria = '';
        $this->resetPage();
    }

    public function updating($property)
    {
        if (in_array($property, ['filtroEstado', 'filtroFechaIngresoDesde', 'filtroFechaIngresoHasta',
                                'filtroFechaEmisionDesde', 'filtroFechaEmisionHasta',
                                'filtroFechaAnulacionDesde', 'filtroFechaAnulacionHasta', 'filtroEnPlanilla', 'filtroBanco', 'filtroCuentaBancaria'])) {
            $this->resetPage();
        }
    }

    public function cambiarReporte($tipo)
    {
        $this->reporteTipo = $tipo;
        $this->showReport = false;
        $this->resetPage();
    }

    public function generarReporte()
    {
        $this->showReport = true;
    }

    public function limpiarFiltros()
    {
        $this->filtroEstado = '';
        $this->filtroFechaIngresoDesde = '';
        $this->filtroFechaIngresoHasta = '';
        $this->filtroFechaEmisionDesde = '';
        $this->filtroFechaEmisionHasta = '';
        $this->filtroFechaAnulacionDesde = '';
        $this->filtroFechaAnulacionHasta = '';
        $this->filtroEnPlanilla = '';
        $this->filtroBanco = '';
        $this->filtroCuentaBancaria = '';
        $this->cuentasBancarias = collect();
        $this->resetPage();
    }

    public function getStockChequesProperty()
    {
        if (!$this->showReport) {
            return collect();
        }

        $grupos = Cheque::select('cuenta_bancaria_id', 'serie')->distinct()->orderBy('cuenta_bancaria_id')->orderBy('serie')->get();
        $cuentasInfo = CuentaBancaria::with('banco')->whereIn('id', $grupos->pluck('cuenta_bancaria_id')->unique())->get()->keyBy('id');
        $stockOrganizado = collect();

        foreach ($grupos as $grupo) {
            $cuentaId = $grupo->cuenta_bancaria_id;
            $serie = $grupo->serie;

            $rangoTotal = Cheque::where('cuenta_bancaria_id', $cuentaId)
                ->where('serie', $serie)
                ->selectRaw('MIN(CAST(numero_cheque AS UNSIGNED)) as min_cheque, MAX(CAST(numero_cheque AS UNSIGNED)) as max_cheque')
                ->first();

            if (is_null($rangoTotal->min_cheque)) continue;

            $libretasCompletas = collect();
            $libretasIncompletas = collect();
            $libretaActualStart = floor(($rangoTotal->min_cheque - 1) / 25) * 25 + 1;

            while ($libretaActualStart <= $rangoTotal->max_cheque) {
                $libretaActualEnd = $libretaActualStart + 24;

                $chequesEnLibretaBruto = Cheque::where('cuenta_bancaria_id', $cuentaId)
                    ->where('serie', $serie)
                    ->whereRaw('CAST(numero_cheque AS UNSIGNED) BETWEEN ? AND ?', [$libretaActualStart, $libretaActualEnd])
                    ->get();

                // Sanear datos para manejar duplicados, priorizando el estado más definitivo.
                $statusPriority = ['emitido' => 1, 'en_planilla' => 2, 'anulado' => 3, 'disponible' => 4];
                $chequesEnLibreta = $chequesEnLibretaBruto->groupBy('numero_cheque')->map(function ($cheques) use ($statusPriority) {
                    if ($cheques->count() == 1) {
                        return $cheques->first();
                    }
                    return $cheques->sortBy(function ($cheque) use ($statusPriority) {
                        return $statusPriority[$cheque->estado] ?? 99;
                    })->first();
                });

                if ($chequesEnLibreta->isEmpty()) {
                    $libretaActualStart += 25;
                    continue;
                }

                $disponibles = $chequesEnLibreta->where('estado', 'disponible');
                $countDisponibles = $disponibles->count();

                if ($countDisponibles == 25) {
                    $libretasCompletas->push(['numero_inicial' => $libretaActualStart, 'numero_final' => $libretaActualEnd, 'total_cheques' => 25]);
                } elseif ($countDisponibles > 0) {
                    $primerChequeDisponibleNum = $disponibles->min('numero_cheque');

                    // 'Usados' son solo los consumidos secuencialmente antes del primer disponible.
                    $chequesUsados = $chequesEnLibreta
                        ->whereIn('estado', ['emitido', 'en_planilla'])
                        ->filter(function ($cheque) use ($primerChequeDisponibleNum) {
                            return (int)$cheque->numero_cheque < (int)$primerChequeDisponibleNum;
                        })
                        ->count();
                    
                    // 'Anulados' son todos los cheques anulados en la libreta.
                    $chequesAnulados = $chequesEnLibreta->where('estado', 'anulado')->count();

                    $libretasIncompletas->push([
                        'numero_inicial_libreta' => $libretaActualStart,
                        'numero_final_libreta' => $libretaActualEnd,
                        'primer_cheque_disponible' => $primerChequeDisponibleNum,
                        'ultimo_cheque_disponible' => $disponibles->max('numero_cheque'),
                        'cheques_usados' => $chequesUsados,
                        'cheques_anulados' => $chequesAnulados,
                        'cheques_disponibles' => $countDisponibles,
                    ]);
                }
                $libretaActualStart += 25;
            }

            $tandasCompletas = collect();
            if ($libretasCompletas->count() > 0) {
                $libretasCompletas = $libretasCompletas->sortBy('numero_inicial')->values();
                $tandaActual = ['tanda' => 1, 'numero_inicial' => $libretasCompletas[0]['numero_inicial'], 'numero_final' => $libretasCompletas[0]['numero_final'], 'total_cheques' => 25];
                for ($i = 1; $i < $libretasCompletas->count(); $i++) {
                    if ($tandaActual['numero_final'] + 1 === $libretasCompletas[$i]['numero_inicial']) {
                        $tandaActual['numero_final'] = $libretasCompletas[$i]['numero_final'];
                        $tandaActual['total_cheques'] += 25;
                    } else {
                        $tandasCompletas->push($tandaActual);
                        $tandaActual = ['tanda' => $tandasCompletas->count() + 1, 'numero_inicial' => $libretasCompletas[$i]['numero_inicial'], 'numero_final' => $libretasCompletas[$i]['numero_final'], 'total_cheques' => 25];
                    }
                }
                $tandasCompletas->push($tandaActual);
            }

            if ($tandasCompletas->isNotEmpty() || $libretasIncompletas->isNotEmpty()) {
                $cuentaIndex = $stockOrganizado->search(fn($item) => $item['cuenta']->id === $cuentaId);
                if ($cuentaIndex !== false) {
                    $stockOrganizado[$cuentaIndex]['series']->push(['serie' => $serie ?: 'SIN/VALOR', 'completas' => $tandasCompletas, 'incompletas' => $libretasIncompletas->sortBy('primer_cheque_disponible')->values()]);
                } else {
                    $stockOrganizado->push(['cuenta' => $cuentasInfo[$cuentaId], 'series' => collect([['serie' => $serie ?: 'SIN/VALOR', 'completas' => $tandasCompletas, 'incompletas' => $libretasIncompletas->sortBy('primer_cheque_disponible')->values()]])]);
                }
            }
        }
        return $stockOrganizado->sortBy('cuenta.numero_cuenta')->values();
    }


    public function getChequesAnuladosMesProperty()
    {
        if (!$this->showReport || !$this->reporteMes || !$this->reporteAnio) {
            return collect();
        }

        return Cheque::where('estado', 'anulado')
            ->whereYear('fecha_anulacion', $this->reporteAnio)
            ->whereMonth('fecha_anulacion', $this->reporteMes)
            ->with('cuentaBancaria.banco')
            ->orderBy('fecha_anulacion', 'desc')
            ->get();
    }

    public function getChequesEmitidosMesProperty()
    {
        if (!$this->showReport || !$this->reporteMes || !$this->reporteAnio) {
            return collect();
        }

        return Cheque::whereIn('estado', ['emitido', 'en_planilla'])
            ->whereYear('fecha_emision', $this->reporteAnio)
            ->whereMonth('fecha_emision', $this->reporteMes)
            ->with('cuentaBancaria.banco')
            ->orderBy('fecha_emision', 'desc')
            ->get();
    }

    public function getPlanillasEmitidasMesProperty()
    {
        if (!$this->showReport || !$this->reporteMes || !$this->reporteAnio) {
            return collect();
        }

        return PlanillaCheque::where('estado', 'generada')
            ->whereYear('fecha_generacion', $this->reporteAnio)
            ->whereMonth('fecha_generacion', $this->reporteMes)
            ->with('cheques.cuentaBancaria.banco')
            ->orderBy('fecha_generacion', 'desc')
            ->get();
    }

    public function getPlanillasAnuladasMesProperty()
    {
        if (!$this->showReport || !$this->reporteMes || !$this->reporteAnio) {
            return collect();
        }

        return PlanillaCheque::where('estado', 'anulada')
            ->whereYear('fecha_anulacion', $this->reporteAnio)
            ->whereMonth('fecha_anulacion', $this->reporteMes)
            ->with('cheques.cuentaBancaria.banco')
            ->orderBy('fecha_anulacion', 'desc')
            ->get();
    }

    public function getChequesFiltradosProperty()
    {
        if (!$this->showReport) {
            return $this->printMode ? collect() : new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        }

        $query = Cheque::query();

        if ($this->filtroBanco) {
            $query->whereHas('cuentaBancaria', function ($q) {
                $q->where('banco_id', $this->filtroBanco);
            });
        }

        if ($this->filtroCuentaBancaria) {
            $query->where('cuenta_bancaria_id', $this->filtroCuentaBancaria);
        }

        // Aplicar filtros
        if ($this->filtroEstado) {
            $query->where('estado', $this->filtroEstado);
        } else {
            $query->where('estado', '!=', 'anulado');
        }

        if ($this->filtroFechaIngresoDesde) {
            $query->whereDate('created_at', '>=', $this->filtroFechaIngresoDesde);
        }

        if ($this->filtroFechaIngresoHasta) {
            $query->whereDate('created_at', '<=', $this->filtroFechaIngresoHasta);
        }

        if ($this->filtroFechaEmisionDesde) {
            $query->whereDate('fecha_emision', '>=', $this->filtroFechaEmisionDesde);
        }

        if ($this->filtroFechaEmisionHasta) {
            $query->whereDate('fecha_emision', '<=', $this->filtroFechaEmisionHasta);
        }

        if ($this->filtroFechaAnulacionDesde) {
            $query->whereDate('fecha_anulacion', '>=', $this->filtroFechaAnulacionDesde);
        }

        if ($this->filtroFechaAnulacionHasta) {
            $query->whereDate('fecha_anulacion', '<=', $this->filtroFechaAnulacionHasta);
        }

        if ($this->filtroEnPlanilla !== '') {
            if ($this->filtroEnPlanilla === 'si') {
                $query->whereNotNull('planilla_id');
            } elseif ($this->filtroEnPlanilla === 'no') {
                $query->whereNull('planilla_id');
            }
        }

        $query->with('cuentaBancaria.banco', 'planilla')->orderBy('numero_cheque');

        if ($this->printMode) {
            return $query->get();
        } else {
            return $query->paginate($this->perPage);
        }
    }

    protected $listeners = ['getPrintableHtml'];

    public function getPrintableHtml()
    {
        $this->printMode = true;
        $this->showReport = true;

        $html = $this->render()->render();

        $this->dispatchBrowserEvent('printableHtmlReady', ['html' => $html]);
    }

    public function render()
    {
        $data = [];
        $reportTitle = ''; // Inicializar la variable

        if ($this->showReport) {
            switch ($this->reporteTipo) {
                case 'stock':
                    $data['stockCheques'] = $this->stockCheques;
                    $reportTitle = 'STOCK DE CHEQUES';
                    break;
                case 'anulados_mes':
                    $data['chequesAnuladosMes'] = $this->chequesAnuladosMes;
                    $reportTitle = 'CHEQUES ANULADOS POR MES';
                    break;
                case 'emitidos_mes':
                    $data['chequesEmitidosMes'] = $this->chequesEmitidosMes;
                    $reportTitle = 'CHEQUES EMITIDOS POR MES';
                    break;
                case 'planillas_mes':
                    $data['planillasEmitidasMes'] = $this->planillasEmitidasMes;
                    $reportTitle = 'PLANILLAS EMITIDAS POR MES';
                    break;
                case 'planillas_anuladas_mes':
                    $data['planillasAnuladasMes'] = $this->planillasAnuladasMes;
                    $reportTitle = 'PLANILLAS ANULADAS POR MES';
                    break;
                case 'listado_general':
                    $data['chequesFiltrados'] = $this->chequesFiltrados;
                    $reportTitle = 'LISTADO GENERAL DE CHEQUES'; // Título más descriptivo
                    break;
            }
        }

        // Añadir el título a los datos que se pasan a la vista
        $data['reportTitle'] = $reportTitle;

        return view('livewire.tesoreria.cheque.cheque-reportes', $data);
    }
}
