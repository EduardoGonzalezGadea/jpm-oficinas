<?php

namespace App\Repositories;

use App\Models\TesCfePendiente;

/**
 * Repository para manejo centralizado de CFE Pendientes.
 * Desacopla la persistencia del servicio de procesamiento.
 */
class CfePendienteRepository
{
    public function __construct(
        private readonly TesCfePendiente $model
    ) {}

    /**
     * Crea un nuevo registro de CFE pendiente.
     *
     * @param array $datos
     * @return TesCfePendiente
     */
    public function crear(array $datos): TesCfePendiente
    {
        return $this->model->newQuery()->create($datos);
    }

    /**
     * Busca un CFE pendiente por ID.
     *
     * @param int $id
     * @return TesCfePendiente|null
     */
    public function buscarPorId(int $id): ?TesCfePendiente
    {
        return $this->model->newQuery()->find($id);
    }

    /**
     * Marca un CFE pendiente como procesado.
     *
     * @param int $id
     * @return void
     */
    public function marcarProcesado(int $id): void
    {
        $this->model->newQuery()
            ->where('id', $id)
            ->update(['estado' => 'procesado']);
    }

    /**
     * Busca CFE pendientes por estado.
     *
     * @param string $estado
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function buscarPorEstado(string $estado): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->newQuery()
            ->where('estado', $estado)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Elimina un CFE pendiente por ID.
     *
     * @param int $id
     * @return void
     */
    public function eliminar(int $id): void
    {
        $this->model->newQuery()->where('id', $id)->delete();
    }
}
