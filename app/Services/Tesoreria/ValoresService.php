<?php

namespace App\Services\Tesoreria;

use App\Models\Tesoreria\LibretaValor;
use App\Models\Tesoreria\TipoLibreta;
use App\Models\Tesoreria\Servicio;
use App\Models\Tesoreria\EntregaLibretaValor;
use Illuminate\Support\Facades\DB;
use Exception;

class ValoresService
{
    /**
     * Crea una o más libretas de valores basadas en los datos proporcionados.
     *
     * @param array $data Datos validados para la creación.
     * @return void
     */
    public function crearLibretas(array $data): void
    {
        $tipoLibreta = TipoLibreta::find($data['tipo_libreta_id']);
        $recibosPorLibreta = $tipoLibreta->cantidad_recibos;
        $numeroActual = intval($data['numero_inicial']);

        DB::transaction(function () use ($data, $recibosPorLibreta, &$numeroActual) {
            for ($i = 0; $i < $data['cantidad_libretas']; $i++) {
                $numero_final_libreta = $numeroActual + $recibosPorLibreta - 1;

                LibretaValor::create([
                    'tipo_libreta_id' => $data['tipo_libreta_id'],
                    'serie' => $data['serie'],
                    'numero_inicial' => $numeroActual,
                    'numero_final' => $numero_final_libreta,
                    'proximo_recibo_disponible' => $numeroActual,
                    'fecha_recepcion' => $data['fecha_recepcion'],
                    'estado' => 'en_stock',
                ]);

                $numeroActual = $numero_final_libreta + 1;
            }
        });
    }

    /**
     * Registra la entrega de una libreta de valor a un servicio.
     *
     * @param LibretaValor $libreta La libreta a entregar.
     * @param array $data Datos validados para la entrega.
     * @return void
     * @throws \Exception
     */
    public function registrarEntrega(LibretaValor $libreta, array $data): void
    {
        $servicio = Servicio::find($data['servicio_entrega_id']);
        if (!$servicio || !$servicio->activo) {
            throw new Exception('El servicio seleccionado no está activo o no existe.');
        }

        DB::transaction(function () use ($libreta, $servicio, $data) {
            // Crear la entrega
            EntregaLibretaValor::create([
                'libreta_valor_id' => $libreta->id,
                'servicio_id' => $data['servicio_entrega_id'],
                'numero_recibo_entrega' => $data['numero_recibo_entrega'],
                'fecha_entrega' => $data['fecha_entrega'],
                'observaciones' => $data['observaciones_entrega'],
                'estado' => 'activo',
            ]);

            // Actualizar estado de la libreta
            $libreta->update([
                'estado' => 'asignada',
                'servicio_asignado_id' => $data['servicio_entrega_id'],
            ]);
        });
    }
}
