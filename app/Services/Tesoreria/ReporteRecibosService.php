<?php

namespace App\Services\Tesoreria;

use Carbon\Carbon;
use App\Models\Tesoreria\Arrendamiento;
use App\Models\Tesoreria\CertificadoResidencia;
use App\Models\Tesoreria\DepositoVehiculo;
use App\Models\Tesoreria\Eventual;
use App\Models\Tesoreria\TesMultasCobradas;
use App\Models\Tesoreria\TesPorteArmas;
use App\Models\Tesoreria\TesTenenciaArmas;
use App\Models\Tesoreria\Prenda;
use App\Models\Tesoreria\TesCfe;

class ReporteRecibosService
{
    /**
     * Definición extensible de secciones del reporte.
     * Para agregar una nueva sección, solo agregar una entrada a este array.
     */
    protected function getSecciones(): array
    {
        return [
            [
                'nombre' => 'Arrendamientos',
                'modelo' => Arrendamiento::class,
                'campo_recibo' => 'recibo',
                'campo_fecha' => 'fecha',
                'campo_cedula' => 'cedula',
                'campo_titular' => 'nombre',
                'campo_monto' => 'monto',
                'recibo_compuesto' => false,
                'filtro_adicional' => null,
            ],
            [
                'nombre' => 'Certificados de Residencia',
                'modelo' => CertificadoResidencia::class,
                'campo_recibo' => 'numero_recibo',
                'campo_fecha' => 'fecha_entregado',
                'campo_cedula' => 'retira_nro_documento',
                'campo_titular' => ['retira_nombre', 'retira_apellido'],
                'campo_monto' => 'monto',
                'recibo_compuesto' => false,
                'filtro_adicional' => function ($query) {
                    $query->where('estado', 'Entregado');
                },
            ],
            [
                'nombre' => 'Depósito de Vehículos',
                'modelo' => DepositoVehiculo::class,
                'campo_recibo' => ['recibo_serie', 'recibo_numero'],
                'campo_fecha' => 'recibo_fecha',
                'campo_cedula' => 'cedula',
                'campo_titular' => 'titular',
                'campo_monto' => 'monto',
                'recibo_compuesto' => true,
                'filtro_adicional' => null,
            ],
            [
                'nombre' => 'Eventuales',
                'modelo' => Eventual::class,
                'campo_recibo' => 'recibo',
                'campo_fecha' => 'fecha',
                'campo_cedula' => null,
                'campo_titular' => ['institucion', 'titular'],
                'campo_monto' => 'monto',
                'recibo_compuesto' => false,
                'filtro_adicional' => null,
            ],
            [
                'nombre' => 'Multas por carecer de SOA',
                'modelo' => TesMultasCobradas::class,
                'campo_recibo' => 'recibo',
                'campo_fecha' => 'fecha',
                'campo_cedula' => 'cedula',
                'campo_titular' => 'nombre',
                'campo_monto' => 'monto',
                'recibo_compuesto' => false,
                'filtro_adicional' => function ($query) {
                    // Recibos donde TODOS los items son SOA (solo SOA)
                    $query->whereHas('items', function ($q) {
                        $q->where('detalle', 'LIKE', '%CARECER DE SOA%');
                    })->whereDoesntHave('items', function ($q) {
                        $q->where('detalle', 'NOT LIKE', '%CARECER DE SOA%');
                    });
                },
            ],
            [
                'nombre' => 'Multas por carecer de SOA y otras',
                'modelo' => TesMultasCobradas::class,
                'campo_recibo' => 'recibo',
                'campo_fecha' => 'fecha',
                'campo_cedula' => 'cedula',
                'campo_titular' => 'nombre',
                'campo_monto' => 'monto',
                'recibo_compuesto' => false,
                'filtro_adicional' => function ($query) {
                    // Recibos que tienen SOA Y TAMBIÉN otros items no-SOA
                    $query->whereHas('items', function ($q) {
                        $q->where('detalle', 'LIKE', '%CARECER DE SOA%');
                    })->whereHas('items', function ($q) {
                        $q->where('detalle', 'NOT LIKE', '%CARECER DE SOA%');
                    });
                },
            ],
            [
                'nombre' => 'Multas varias',
                'modelo' => TesMultasCobradas::class,
                'campo_recibo' => 'recibo',
                'campo_fecha' => 'fecha',
                'campo_cedula' => 'cedula',
                'campo_titular' => 'nombre',
                'campo_monto' => 'monto',
                'recibo_compuesto' => false,
                'filtro_adicional' => function ($query) {
                    // Recibos que NO tienen ningún item SOA
                    $query->whereDoesntHave('items', function ($q) {
                        $q->where('detalle', 'LIKE', '%CARECER DE SOA%');
                    });
                },
            ],
            [
                'nombre' => 'Porte de Armas',
                'modelo' => TesPorteArmas::class,
                'campo_recibo' => 'recibo',
                'campo_fecha' => 'fecha',
                'campo_cedula' => 'cedula',
                'campo_titular' => 'titular',
                'campo_monto' => 'monto',
                'recibo_compuesto' => false,
                'filtro_adicional' => null,
            ],
            [
                'nombre' => 'Tenencia de Armas (THATA)',
                'modelo' => TesTenenciaArmas::class,
                'campo_recibo' => 'recibo',
                'campo_fecha' => 'fecha',
                'campo_cedula' => 'cedula',
                'campo_titular' => 'titular',
                'campo_monto' => 'monto',
                'recibo_compuesto' => false,
                'filtro_adicional' => null,
            ],
            [
                'nombre' => 'Prendas',
                'modelo' => Prenda::class,
                'campo_recibo' => ['recibo_serie', 'recibo_numero'],
                'campo_fecha' => 'recibo_fecha',
                'campo_cedula' => 'titular_cedula',
                'campo_titular' => 'titular_nombre',
                'campo_monto' => 'monto',
                'recibo_compuesto' => true,
                'filtro_adicional' => null,
            ],
        ];
    }

    /**
     * Genera el reporte completo agrupado por sección.
     */
    public function generarReporte(Carbon $desde, Carbon $hasta): array
    {
        $secciones = [];
        $granTotalCantidad = 0;
        $granTotalMonto = 0.0;

        foreach ($this->getSecciones() as $config) {
            $seccion = $this->procesarSeccion($config, $desde, $hasta);
            $secciones[] = $seccion;
            $granTotalCantidad += $seccion['cantidad'];
            $granTotalMonto += $seccion['monto_total'];
        }

        foreach ($this->procesarSeccionesCfe($desde, $hasta) as $seccion) {
            $secciones[] = $seccion;
            $granTotalCantidad += $seccion['cantidad'];
            $granTotalMonto += $seccion['monto_total'];
        }

        return [
            'secciones' => $secciones,
            'gran_total_cantidad' => $granTotalCantidad,
            'gran_total_monto' => $granTotalMonto,
            'gran_total_monto_formateado' => $this->formatearMonto($granTotalMonto),
            'fecha_desde' => $desde->format('d/m/Y'),
            'fecha_hasta' => $hasta->format('d/m/Y'),
        ];
    }

    /**
     * Procesa una sección individual: consulta, normaliza y totaliza.
     */
    protected function procesarSeccion(array $config, Carbon $desde, Carbon $hasta): array
    {
        $modelo = $config['modelo'];
        $query = $modelo::query();

        // Filtro de fecha
        $query->whereDate($config['campo_fecha'], '>=', $desde)
              ->whereDate($config['campo_fecha'], '<=', $hasta);

        // Filtro adicional (ej: estado = Entregado para certificados)
        if ($config['filtro_adicional'] !== null) {
            $config['filtro_adicional']($query);
        }

        // Ordenar por fecha y recibo
        $campoFecha = $config['campo_fecha'];
        $query->orderBy($campoFecha, 'asc');

        $registros = $query->get();

        // Normalizar registros a estructura uniforme
        $registrosNormalizados = $registros->map(function ($registro) use ($config) {
            return $this->normalizarRegistro($registro, $config);
        })->toArray();

        $montoTotal = $registros->sum($config['campo_monto']);

        return [
            'nombre' => $config['nombre'],
            'cantidad' => count($registrosNormalizados),
            'monto_total' => $montoTotal,
            'monto_total_formateado' => $this->formatearMonto($montoTotal),
            'registros' => $registrosNormalizados,
        ];
    }

    /**
     * Nombre de secciones existentes que tienen un modelo de negocio dedicado.
     * Los CFE cuyo concepto de caja coincida con alguna de estas se excluyen
     * para evitar duplicación con los datos ya reportados por los modelos.
     */
    private const SECCIONES_MODELO = [
        'Arrendamientos',
        'Certificados de Residencia',
        'Depósito de Vehículos',
        'Eventuales',
        'Multas por carecer de SOA',
        'Multas por carecer de SOA y otras',
        'Multas varias',
        'Porte de Armas',
        'Tenencia de Armas (THATA)',
        'Prendas',
    ];

    /**
     * Mapeo de nombres de CajaConcepto a nombres de sección del reporte.
     * Cuando un CFE tiene un concepto de caja que mapea a una sección existente,
     * se omite para evitar doble conteo.
     */
    private const MAPA_CONCEPTO_A_SECCION = [
        'ARRENDAMIENTOS'                                         => 'Arrendamientos',
        'CERTIFICADO DE RESIDENCIA'                               => 'Certificados de Residencia',
        'DEPÓSITO DE VEHÍCULOS'                                   => 'Depósito de Vehículos',
        'MULTAS DE TRÁNSITO'                                      => 'Multas varias',
        'PORTE DE ARMAS'                                          => 'Porte de Armas',
        'TITULO HABILITACIÓN Y TENENCIA DE ARMA (TAHTA)'          => 'Tenencia de Armas (THATA)',
    ];

    /**
     * Procesa los CFE cargados vía Gestión de CFEs agrupándolos por concepto de caja.
     * Omite conceptos que ya tienen una sección dedicada en getSecciones().
     */
    protected function procesarSeccionesCfe(Carbon $desde, Carbon $hasta): array
    {
        $secciones = [];

        $cfes = TesCfe::whereNull('deleted_at')
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->with('cajaConcepto')
            ->orderBy('fecha', 'asc')
            ->get();

        $agrupados = $cfes->groupBy(function ($cfe) {
            return $cfe->cajaConcepto?->caja_concepto ?? 'Sin concepto';
        });

        foreach ($agrupados as $nombreConcepto => $grupo) {
            $nombreSeccion = self::MAPA_CONCEPTO_A_SECCION[$nombreConcepto] ?? $nombreConcepto;

            if (in_array($nombreSeccion, self::SECCIONES_MODELO)) {
                continue;
            }

            $registros = $grupo->map(function ($cfe) {
                $recibo = '';
                if ($cfe->documento_tipo) {
                    $recibo = trim($cfe->documento_tipo . ' ' . ($cfe->documento_serie ?? ''));
                    $recibo = trim($recibo . '-' . $cfe->documento_numero, ' -');
                } else {
                    $recibo = $cfe->documento_numero ?? '';
                }

                return [
                    'recibo' => $recibo,
                    'fecha' => $cfe->fecha ? $cfe->fecha->format('d/m/Y') : '',
                    'cedula' => $cfe->receptor_documento_ruc ?? '',
                    'titular' => mb_strtoupper($cfe->receptor_nombre_denominacion ?? ''),
                    'monto' => (float) ($cfe->total_a_pagar ?? 0),
                    'monto_formateado' => $this->formatearMonto((float) ($cfe->total_a_pagar ?? 0)),
                ];
            })->toArray();

            $montoTotal = $grupo->sum('total_a_pagar');

            $secciones[] = [
                'nombre' => $nombreSeccion,
                'cantidad' => count($registros),
                'monto_total' => (float) $montoTotal,
                'monto_total_formateado' => $this->formatearMonto((float) $montoTotal),
                'registros' => $registros,
            ];
        }

        return $secciones;
    }

    /**
     * Normaliza un registro individual a la estructura uniforme del reporte.
     */
    protected function normalizarRegistro($registro, array $config): array
    {
        // Recibo
        if ($config['recibo_compuesto'] && is_array($config['campo_recibo'])) {
            $serie = $registro->{$config['campo_recibo'][0]} ?? '';
            $numero = $registro->{$config['campo_recibo'][1]} ?? '';
            $recibo = trim($serie . '-' . $numero, '-');
        } elseif (is_array($config['campo_recibo'])) {
            $recibo = implode('-', array_filter(array_map(
                fn($campo) => $registro->{$campo} ?? '',
                $config['campo_recibo']
            )));
        } else {
            $recibo = $registro->{$config['campo_recibo']} ?? '';
        }

        // Fecha (formateada al estilo uruguayo)
        $fechaRaw = $registro->{$config['campo_fecha']};
        $fecha = $fechaRaw ? Carbon::parse($fechaRaw)->format('d/m/Y') : '';

        // Cédula
        $cedula = '';
        if ($config['campo_cedula'] !== null) {
            $cedula = $registro->{$config['campo_cedula']} ?? '';
        }

        // Titular (puede ser un campo simple o array de campos a concatenar)
        if (is_array($config['campo_titular'])) {
            $partes = array_map(
                fn($campo) => trim($registro->{$campo} ?? ''),
                $config['campo_titular']
            );
            // Unir con espacio, separando con " - " si son campos tipo institucion+titular
            $titular = implode(' ', array_filter($partes));
        } else {
            $titular = $registro->{$config['campo_titular']} ?? '';
        }

        // Monto
        $monto = (float) ($registro->{$config['campo_monto']} ?? 0);

        return [
            'recibo' => $recibo,
            'fecha' => $fecha,
            'cedula' => $cedula,
            'titular' => mb_strtoupper($titular),
            'monto' => $monto,
            'monto_formateado' => $this->formatearMonto($monto),
        ];
    }

    /**
     * Formatea un monto al estilo uruguayo: $ 1.234,56
     */
    public function formatearMonto(float $monto): string
    {
        return '$' . "\u{00A0}" . number_format($monto, 2, ',', '.');
    }
}
