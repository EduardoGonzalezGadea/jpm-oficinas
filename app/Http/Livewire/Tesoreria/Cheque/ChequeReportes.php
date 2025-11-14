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

        $sql = "
            WITH ChequeGrupos AS (
                SELECT
                    cuenta_bancaria_id,
                    serie,
                    numero_cheque,
                    (numero_cheque - ROW_NUMBER() OVER (PARTITION BY cuenta_bancaria_id, serie ORDER BY numero_cheque)) as grupo_consecutivo
                FROM
                    tes_cheques
                WHERE
                    estado = 'disponible'
            )
            SELECT
                cuenta_bancaria_id,
                serie,
                MIN(numero_cheque) as rango_inicio,
                MAX(numero_cheque) as rango_fin,
                COUNT(*) as cantidad
            FROM
                ChequeGrupos
            GROUP BY
                cuenta_bancaria_id,
                serie,
                grupo_consecutivo
            ORDER BY
                cuenta_bancaria_id,
                serie,
                rango_inicio;
        ";

        $rangosCheques = \Illuminate\Support\Facades\DB::select($sql);

        $chequesPorCuenta = collect($rangosCheques)->groupBy('cuenta_bancaria_id');
        $cuentasInfo = CuentaBancaria::with('banco')->whereIn('id', $chequesPorCuenta->keys())->get()->keyBy('id');
        $stockOrganizado = collect();

        foreach ($chequesPorCuenta as $cuentaId => $rangosDeLaCuenta) {
            $seriesDeLaCuenta = collect();
            $rangosPorSerie = $rangosDeLaCuenta->groupBy('serie');

            foreach ($rangosPorSerie as $serie => $rangos) {
                $libretasCompletas = collect();
                $libretasIncompletas = collect();

                $minCheque = $rangos->min('rango_inicio');
                $maxCheque = $rangos->max('rango_fin');

                if (is_null($minCheque)) continue;

                $libretaActualStart = floor(($minCheque - 1) / 25) * 25 + 1;

                while ($libretaActualStart <= $maxCheque) {
                    $libretaActualEnd = $libretaActualStart + 24;
                    $countEnLibreta = 0;
                    $minEnLibreta = null;
                    $maxEnLibreta = null;

                    foreach ($rangos as $rango) {
                        $overlap_start = max($libretaActualStart, $rango->rango_inicio);
                        $overlap_end = min($libretaActualEnd, $rango->rango_fin);

                        if ($overlap_start <= $overlap_end) {
                            $countEnLibreta += $overlap_end - $overlap_start + 1;
                            if (is_null($minEnLibreta) || $overlap_start < $minEnLibreta) $minEnLibreta = $overlap_start;
                            if (is_null($maxEnLibreta) || $overlap_end > $maxEnLibreta) $maxEnLibreta = $overlap_end;
                        }
                    }

                    if ($countEnLibreta == 25) {
                        $libretasCompletas->push(['numero_inicial' => $libretaActualStart, 'numero_final' => $libretaActualEnd, 'total_cheques' => 25]);
                    } elseif ($countEnLibreta > 0) {
                        $libretasIncompletas->push([
                            'numero_inicial_libreta' => $libretaActualStart,
                            'numero_final_libreta' => $libretaActualEnd,
                            'primer_cheque_disponible' => $minEnLibreta,
                            'ultimo_cheque_disponible' => $maxEnLibreta,
                            'cheques_usados' => $minEnLibreta - $libretaActualStart,
                            'cheques_disponibles' => $countEnLibreta,
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
                    $seriesDeLaCuenta->push([
                        'serie' => $serie ?: 'SIN/VALOR', // Serie Sin especificar
                        'completas' => $tandasCompletas,
                        'incompletas' => $libretasIncompletas->sortBy('primer_cheque_disponible')->values(),
                    ]);
                }
            }

            if ($seriesDeLaCuenta->isNotEmpty()) {
                $stockOrganizado->push([
                    'cuenta' => $cuentasInfo[$cuentaId],
                    'series' => $seriesDeLaCuenta->sortBy('serie'),
                ]);
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
