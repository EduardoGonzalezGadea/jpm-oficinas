<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\ConvertirMayusculas;
use App\Traits\LogsActivityTrait;
use App\Models\User;

/**
 * Modelo de Prenda
 *
 * Este modelo representa una prenda individual registrada en el sistema.
 *
 * La tabla asociada es 'tes_prendas' y permite soft deletes para mantener trazabilidad.
 *
 * @package  App\Models\Tesoreria
 * @author   Sistema de Tesorería
 * @version  1.0.0
 */
class Prenda extends Model
{
    use HasFactory, SoftDeletes, ConvertirMayusculas, LogsActivityTrait;

    /**
     * Nombre de la tabla en la base de datos
     *
     * @var string
     */
    protected $table = 'tes_prendas';

    /**
     * Atributos que son asignables masivamente
     *
     * Estos campos pueden ser llenados usando métodos como Prenda::create()
     * o $prenda->update().
     *
     * @var array
     */
    protected $fillable = [
        'planilla_id',        // FK a la tabla tes_prendas_planillas (nullable)
        'recibo_serie',       // Serie del recibo (ej: "A", "B")
        'recibo_numero',      // Número del recibo
        'recibo_fecha',       // Fecha de emisión del recibo
        'orden_cobro',        // Orden de cobro asociada
        'titular_nombre',     // Nombre del titular de la prenda
        'titular_cedula',     // Cédula de identidad del titular
        'titular_telefono',   // Teléfono de contacto del titular
        'medio_pago_id',      // FK al medio de pago utilizado
        'monto',              // Monto de la prenda
        'concepto',           // Descripción del objeto empeñado
        'transferencia',      // Número de transferencia (si aplica)
        'transferencia_fecha', // Fecha de la transferencia
        'created_by',        // Usuario que creó el registro
        'updated_by',        // Usuario que último modificó el registro
        'deleted_by',        // Usuario que eliminó el registro
    ];

    /**
     * Campos que serán convertidos automáticamente a mayúsculas
     *
     * Cuando se asigna un valor a estos campos, se convierte a mayúsculas
     * usando el método toUpper() del trait ConvertirMayusculas.
     *
     * @var array
     */
    protected $uppercaseFields = [
        'recibo_serie',
        'recibo_numero',
        'orden_cobro',
        'titular_nombre',
        'titular_cedula',
        'concepto',
        'transferencia',
    ];

    /**
     * Conversión de tipos para los atributos
     *
     * Estos campos serán convertidos automáticamente a los tipos indicados.
     *
     * @var array
     */
    protected $casts = [
        'recibo_fecha' => 'date',          // Convierte a objeto Carbon\Carbon
        'transferencia_fecha' => 'date',    // Convierte a objeto Carbon\Carbon
    ];

    /**
     * Método de inicio - Configura eventos del modelo
     *
     * Este método se ejecuta automáticamente albootear el modelo.
     * Configura los eventos creating, updating y deleting para:
     * - Asignar created_by, updated_by, deleted_by automáticamente
     * - Registrar la actividad del usuario
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        /**
         * Evento: Creating
         * Se ejecuta ANTES de crear el registro.
         * Asigna el ID del usuario autenticado a created_by y updated_by.
         */
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
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

        /**
         * Evento: Deleting
         * Se ejecuta ANTES de eliminar (soft delete) el registro.
         * Asigna el ID del usuario autenticado a deleted_by.
         */
        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                $model->save(); // Guarda el deleted_by antes de eliminar
            }
        });
    }

    /**
     * Mutador personalizado para asignar atributos
     *
     * Convierte automáticamente los campos definidos en $uppercaseFields
     * a mayúsculas antes de guardarlos en la base de datos.
     *
     * @param string $key   Nombre del atributo
     * @param mixed  $value Valor a asignar
     * @return void
     */
    public function setAttribute($key, $value)
    {
        // Si el campo está en la lista de campos a convertir a mayúsculas
        if (in_array($key, $this->uppercaseFields) && is_string($value)) {
            // Convierte a mayúsculas usando el trait ConvertirMayusculas
            $this->attributes[$key] = $this->toUpper($value);
        } else {
            // Usa el comportamiento por defecto del padre
            parent::setAttribute($key, $value);
        }
    }

    // =================================================================
    // RELACIONES
    // =================================================================

    /**
     * Relación con MedioDePago
     *
     * Una prenda tiene un medio de pago asociado.
     * Esta relación usa belongsTo porque una prenda pertenece a un medio de pago.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function medioPago()
    {
        return $this->belongsTo(MedioDePago::class, 'medio_pago_id');
    }

    /**
     * Relación con el Usuario Creador
     *
     * Obtiene el usuario que creó el registro.
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
     * Obtiene el usuario que último modificó el registro.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relación con PrendaPlanilla
     *
     * Una prenda puede pertenecer a una planilla (nullable).
     * Si planilla_id es null, la prenda no está asignada a ninguna planilla.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function planilla()
    {
        return $this->belongsTo(PrendaPlanilla::class, 'planilla_id');
    }

    /**
     * Relación con el Usuario Eliminador
     *
     * Obtiene el usuario que eliminó (soft delete) el registro.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // =================================================================
    // ATRIBUTOS COMPUTADOS (ACCESORS)
    // =================================================================

    /**
     * Obtiene el monto formateado como moneda
     *
     * Convierte el valor numérico del monto a formato de moneda
     * con separador de miles y decimales.
     *
     * Ejemplo: 1500 → "1.500,00"
     *
     * @return string Monto formateado
     */
    public function getMontoFormateadoAttribute()
    {
        return number_format($this->monto, 2, ',', '.');
    }
}
