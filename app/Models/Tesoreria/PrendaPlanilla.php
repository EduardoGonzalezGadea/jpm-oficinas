<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsActivityTrait;
use App\Models\User;

/**
 * Modelo de PrendaPlanilla
 *
 * Este modelo representa una planilla de prendas. Una planilla es un documento
 * que agrupa varias prendas para su control y gestión dentro del sistema de tesorería.
 *
 * Las planillas permiten organizar y controlar grupos de prendas de manera eficiente.
 * Cada planilla tiene un número único generado automáticamente con formato: YYYY-MM-DD-N
 *
 * La tabla asociada es 'tes_prendas_planillas' y permite soft deletes.
 *
 * @package  App\Models\Tesoreria
 * @author   Sistema de Tesorería
 * @version  1.0.0
 */
class PrendaPlanilla extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    /**
     * Nombre de la tabla en la base de datos
     *
     * @var string
     */
    protected $table = 'tes_prendas_planillas';

    /**
     * Atributos que son asignables masivamente
     *
     * Estos campos pueden ser llenados usando métodos como PrendaPlanilla::create()
     * o $planilla->update().
     *
     * @var array
     */
    protected $fillable = [
        'fecha',              // Fecha de la planilla (se asigna automáticamente si no se proporciona)
        'numero',             // Número único de planilla (generado automáticamente)
        'anulada_fecha',      // Fecha de anulación (null si está activa)
        'anulada_user_id',    // Usuario que anuló la planilla
        'created_by',        // Usuario que creó la planilla
        'updated_by',        // Usuario que último modificó la planilla
    ];

    /**
     * Conversión de tipos para los atributos
     *
     * Estos campos serán convertidos automáticamente a los tipos indicados.
     *
     * @var array
     */
    protected $casts = [
        'fecha' => 'date',              // Convierte a objeto Carbon\Carbon
        'anulada_fecha' => 'datetime',   // Convierte a objeto Carbon\Carbon con hora
    ];

    /**
     * Método de inicio - Configura eventos del modelo
     *
     * Este método se ejecuta automáticamente albootear el modelo.
     * Configura los eventos creating y updating para:
     * - Generar número de planilla automáticamente
     * - Asignar created_by y updated_by automáticamente
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        /**
         * Evento: Creating
         * Se ejecuta ANTES de crear el registro.
         * - Asigna la fecha actual si no se proporciona
         * - Genera el número de planilla automáticamente
         * - Asigna el ID del usuario autenticado a created_by
         */
        static::creating(function ($model) {
            // Si no se proporciona fecha, usa la fecha y hora actual
            if (!$model->fecha) {
                $model->fecha = now();
            }

            // Si no se proporciona número, genera uno automáticamente
            if (!$model->numero) {
                $model->numero = static::generarNumero($model->fecha);
            }

            // Asigna el usuario creador
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        /**
         * Evento: Updating
         * Se ejecuta ANTES de actualizar el registro.
         * Asigna el ID del usuario autenticado a updated_by.
         */
        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    // =================================================================
    // MÉTODOS ESTÁTICOS
    // =================================================================

    /**
     * Genera el siguiente número de planilla para una fecha específica
     *
     * El número de planilla tiene el formato: YYYY-MM-DD-N
     * Donde:
     * - YYYY-MM-DD: Fecha de la planilla
     * - N: Número secuencial del día (inicia en 1)
     *
     * Ejemplos:
     * - 2024-01-15-1 = Primera planilla del 15 de enero de 2024
     * - 2024-01-15-3 = Tercera planilla del 15 de enero de 2024
     *
     * @param \Carbon\Carbon|string $fecha Fecha para la cual generar el número
     * @return string Número de planilla generado
     */
    public static function generarNumero($fecha)
    {
        // Convierte la fecha a string con formato YYYY-MM-DD
        $fechaStr = \Carbon\Carbon::parse($fecha)->format('Y-m-d');

        // Busca la última planilla creada para esta fecha
        // Se busca por el patrón YYYY-MM-DD-%
        $ultimaPlanilla = static::where('numero', 'like', $fechaStr . '-%')
            ->orderBy('numero', 'desc')  // Ordena descendente para obtener la última
            ->first();

        // Determina el siguiente número secuencial
        if ($ultimaPlanilla) {
            // Extrae el número secuencial del último número
            // Ejemplo: de "2024-01-15-3" extrae "3"
            $parts = explode('-', $ultimaPlanilla->numero);
            $lastSequential = (int) end($parts);
            $nextSequential = $lastSequential + 1;
        } else {
            // Si no hay planillas para esta fecha, inicia en 1
            $nextSequential = 1;
        }

        // Construye y retorna el nuevo número
        return $fechaStr . '-' . $nextSequential;
    }

    // =================================================================
    // RELACIONES
    // =================================================================

    /**
     * Relación con Prenda (HasMany)
     *
     * Una planilla puede contener muchas prendas.
     * Este método devuelve todas las prendas asociadas a esta planilla.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prendas()
    {
        return $this->hasMany(Prenda::class, 'planilla_id');
    }

    /**
     * Relación con el Usuario Anulador
     *
     * Obtiene el usuario que anuló la planilla (si está anulada).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anuladaPor()
    {
        return $this->belongsTo(User::class, 'anulada_user_id');
    }

    /**
     * Relación con el Usuario Creador
     *
     * Obtiene el usuario que creó la planilla.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el Último Editor
     *
     * Obtiene el usuario que último modificó la planilla.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // =================================================================
    // MÉTODOS DE GESTIÓN
    // =================================================================

    /**
     * Verifica si la planilla está anulada
     *
     * Retorna true si la planilla tiene una fecha de anulación asignada.
     * Las planillas anuladas no deben procesarse ni imprimirse.
     *
     * @return bool True si está anulada, false si está activa
     */
    public function isAnulada()
    {
        return !is_null($this->anulada_fecha);
    }

    /**
     * Anula la planilla actual
     *
     * Este método:
     * 1. Asigna la fecha y hora actual a anulada_fecha
     * 2. Asigna el ID del usuario autenticado a anulada_user_id
     * 3. Guarda los cambios
     * 4. Libera todas las prendas asociadas (les quita la planilla)
     *
     * Las prendas quedan con planilla_id en null y pueden ser
     * asignadas a una nueva planilla si es necesario.
     *
     * @return void
     */
    public function anular()
    {
        // Asigna la fecha/hora de anulación y el usuario
        $this->anulada_fecha = now();
        if (Auth::check()) {
            $this->anulada_user_id = Auth::id();
        }
        $this->save();

        // Libera todas las prendas asociadas
        // Esto les quita la referencia a esta planilla
        $this->prendas()->update(['planilla_id' => null]);
    }

    // =================================================================
    // ATRIBUTOS COMPUTADOS (ACCESORS)
    // =================================================================

    /**
     * Calcula el total de montos de todas las prendas en la planilla
     *
     * Suma el campo 'monto' de todas las prendas asociadas.
     * Solo considera prendas que no han sido eliminadas.
     *
     * @return float Suma total de los montos
     */
    public function getTotalAttribute()
    {
        return $this->prendas->sum('monto');
    }

    /**
     * Obtiene el total formateado como moneda
     *
     * Convierte el valor numérico del total a formato de moneda
     * con separador de miles y decimales.
     *
     * Ejemplo: 5000 → "5.000,00"
     *
     * @return string Total formateado
     */
    public function getTotalFormateadoAttribute()
    {
        return number_format($this->total, 2, ',', '.');
    }
}
