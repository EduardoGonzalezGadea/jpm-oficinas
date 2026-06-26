<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityTrait
{
    use LogsActivity;

    /**
     * Configuración de opciones de log de actividad.
     * Los modelos pueden sobrescribir este método para personalizar la configuración.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()                              // Registrar todos los atributos
            ->logOnlyDirty()                        // Solo registrar atributos que cambiaron
            ->dontSubmitEmptyLogs()                 // No registrar logs vacíos
            ->useLogName($this->getLogName())       // Nombre del log (módulo)
            ->setDescriptionForEvent(fn(string $eventName) => $this->getActivityDescription($eventName));
    }

    /**
     * Obtener el nombre del log (módulo) basado en el modelo.
     */
    protected function getLogName(): string
    {
        // Obtener el nombre corto de la clase
        $className = class_basename(static::class);

        // Mapeo de modelos a nombres de módulos
        $logNames = [
            'User' => 'usuarios',
            'CertificadoResidencia' => 'certificados',
            'Cheque' => 'cheques',
            'PlanillaCheque' => 'cheques',
            'CajaChica' => 'caja_chica',
            'Pendiente' => 'caja_chica',
            'Pago' => 'caja_chica',
            'Movimiento' => 'caja_chica',
            'Acreedor' => 'caja_chica',
            'TesCchAcreedor' => 'caja_chica',
            'Arrendamiento' => 'arrendamientos',
            'Planilla' => 'arrendamientos',
            'TesPorteArmas' => 'armas',
            'TesTenenciaArmas' => 'armas',
            'TesPorteArmasPlanilla' => 'armas',
            'TesTenenciaArmasPlanilla' => 'armas',
            'TarjetaCobroBrou' => 'tarjetas_cobro_brou',
            'Prenda' => 'prendas',
            'PrendaPlanilla' => 'prendas',
            'DepositoVehiculo' => 'deposito_vehiculos',
            'DepositoVehiculoPlanilla' => 'deposito_vehiculos',
            'Eventual' => 'eventuales',
            'EventualPlanilla' => 'eventuales',
            'EventualInstitucion' => 'eventuales',
            'LibretaValor' => 'valores',
            'EntregaLibretaValor' => 'valores',
            'TipoLibreta' => 'valores',
            'MedioDePago' => 'configuracion',
            'CajaConcepto' => 'configuracion',
            'TesTipoMoneda' => 'configuracion',
            'TesDenominacionMoneda' => 'configuracion',
            'Servicio' => 'configuracion',
            'Multa' => 'infracciones',
            'InfraccionTransito' => 'infracciones',
            'TesMultasItems' => 'multas_cobradas',
            'TesMultasCobradas' => 'multas_cobradas',
            'Cobro' => 'cobros',
            'Modulo' => 'sistema',
            'Anulacion' => 'sistema',
            'Dependencia' => 'sistema',
            // Caja Diaria
            'CajaDiaria' => 'caja_diaria',
            'CajaMovimiento' => 'caja_diaria',
            'DesgloseMoneda' => 'caja_diaria',
            'EstadoCaja' => 'caja_diaria',
            'TipoMovimiento' => 'caja_diaria',
            'MedioPagoCaja' => 'caja_diaria',
            'EstadoDeposito' => 'caja_diaria',
            'InstanciaDesglose' => 'caja_diaria',
            'EstadoER' => 'caja_diaria',
            'Concepto' => 'caja_diaria',
            'Institucion222' => 'caja_diaria',
            'DistribucionER' => 'caja_diaria',
            'EstadoRecaudacion' => 'caja_diaria',
            'EstadoRecaudacionDetalle' => 'caja_diaria',
            // CFE
            'TesCfe' => 'cfes',
            'TesCfeItem' => 'cfes',
            'TesCfeMedioPago' => 'cfes',
            'TesPlanillaEr' => 'ers',
        ];

        return $logNames[$className] ?? 'sistema';
    }

    /**
     * Generar descripción legible para el evento.
     */
    protected function getActivityDescription(string $eventName): string
    {
        $modelName = $this->getModelDisplayName();
        $identifier = $this->getModelIdentifier();

        return match ($eventName) {
            'created' => "Se creó {$modelName} {$identifier}",
            'updated' => "Se actualizó {$modelName} {$identifier}",
            'deleted' => "Se eliminó {$modelName} {$identifier}",
            'restored' => "Se restauró {$modelName} {$identifier}",
            'login' => "El usuario inició sesión",
            'logout' => "El usuario cerró sesión",
            default => ucfirst($eventName) . " en {$modelName} {$identifier}",
        };
    }

    /**
     * Obtener nombre legible del modelo.
     */
    protected function getModelDisplayName(): string
    {
        $className = class_basename(static::class);

        $displayNames = [
            'User' => 'el usuario',
            'CertificadoResidencia' => 'el certificado de residencia',
            'Cheque' => 'el cheque',
            'PlanillaCheque' => 'la planilla de cheques',
            'CajaChica' => 'la caja chica',
            'Pendiente' => 'el pendiente de caja chica',
            'Pago' => 'el pago de caja chica',
            'Movimiento' => 'el movimiento de caja chica',
            'Acreedor' => 'el acreedor',
            'TesCchAcreedor' => 'el acreedor de caja chica',
            'Arrendamiento' => 'el arrendamiento',
            'Planilla' => 'la planilla de arrendamientos',
            'TesPorteArmas' => 'el porte de armas',
            'TesTenenciaArmas' => 'la tenencia de armas',
            'TesPorteArmasPlanilla' => 'la planilla de porte de armas',
            'TesTenenciaArmasPlanilla' => 'la planilla de tenencia de armas',
            'TarjetaCobroBrou' => 'la tarjeta de cobro BROU',
            'Prenda' => 'la prenda',
            'PrendaPlanilla' => 'la planilla de prendas',
            'DepositoVehiculo' => 'el depósito de vehículo',
            'DepositoVehiculoPlanilla' => 'la planilla de depósito de vehículos',
            'Eventual' => 'el eventual',
            'EventualPlanilla' => 'la planilla de eventuales',
            'EventualInstitucion' => 'la institución de eventuales',
            'LibretaValor' => 'la libreta de valores',
            'EntregaLibretaValor' => 'la entrega de libreta de valor',
            'TipoLibreta' => 'el tipo de libreta',
            'MedioDePago' => 'el medio de pago',
            'CajaConcepto' => 'el concepto de caja',
            'TesTipoMoneda' => 'el tipo de moneda',
            'TesDenominacionMoneda' => 'la denominación de moneda',
            'Servicio' => 'el servicio',
            'Multa' => 'la multa/artículo',
            'InfraccionTransito' => 'la infracción de tránsito',
            'TesMultasItems' => 'el item de multa cobrada',
            'TesMultasCobradas' => 'la multa cobrada',
            'Cobro' => 'el cobro',
            'Modulo' => 'el módulo',
            'Anulacion' => 'la anulación',
            'Dependencia' => 'la dependencia',
            // Caja Diaria
            'CajaDiaria' => 'la caja diaria',
            'CajaMovimiento' => 'el movimiento de caja',
            'DesgloseMoneda' => 'el desglose de monedas',
            'EstadoCaja' => 'el estado de caja',
            'TipoMovimiento' => 'el tipo de movimiento',
            'MedioPagoCaja' => 'el medio de pago de caja',
            'EstadoDeposito' => 'el estado de depósito',
            'InstanciaDesglose' => 'la instancia de desglose',
            'EstadoER' => 'el estado de ER',
            'Concepto' => 'el concepto',
            'Institucion222' => 'la institución Art. 222',
            'DistribucionER' => 'la distribución de ER',
            'EstadoRecaudacion' => 'el estado de recaudación',
            'EstadoRecaudacionDetalle' => 'el detalle de estado de recaudación',
            // CFE
            'TesCfe' => 'el CFE',
            'TesCfeItem' => 'el ítem de CFE',
            'TesCfeMedioPago' => 'el medio de pago de CFE',
            'TesPlanillaEr' => 'la planilla de ER',
        ];

        return $displayNames[$className] ?? 'el registro';
    }

    /**
     * Obtener identificador del modelo para la descripción.
     */
    protected function getModelIdentifier(): string
    {
        // Intentar obtener un identificador legible
        if (isset($this->numero)) {
            return "#{$this->numero}";
        }

        if (isset($this->numero_cheque)) {
            return "#{$this->numero_cheque}";
        }

        if (isset($this->nombre) && isset($this->apellido)) {
            return "\"{$this->nombre} {$this->apellido}\"";
        }

        if (isset($this->nombre)) {
            return "\"{$this->nombre}\"";
        }

        if (isset($this->acreedor)) {
            return "\"{$this->acreedor}\"";
        }

        if (isset($this->titular_nombre) && isset($this->titular_apellido)) {
            return "de \"{$this->titular_nombre} {$this->titular_apellido}\"";
        }

        if (isset($this->beneficiario)) {
            return "para \"{$this->beneficiario}\"";
        }

        if (isset($this->cedula)) {
            return "(Cédula: {$this->cedula})";
        }

        if (isset($this->recibo)) {
            return "con recibo #{$this->recibo}";
        }

        if (isset($this->serie) && isset($this->numero_cheque)) {
            return "serie {$this->serie} #{$this->numero_cheque}";
        }

        if (isset($this->mes) && isset($this->anio)) {
            return "de {$this->mes}/{$this->anio}";
        }

        if (isset($this->descripcion)) {
            return "\"{$this->descripcion}\"";
        }

        if (isset($this->documento_serie) && isset($this->documento_numero)) {
            return "serie {$this->documento_serie} #{$this->documento_numero}";
        }

        if (isset($this->detalle)) {
            return "\"{$this->detalle}\"";
        }

        if (isset($this->medio_pago_tipo)) {
            return "\"{$this->medio_pago_tipo}\"";
        }

        return "(ID: " . ($this->id ?? $this->primaryKey ?? 'N/A') . ")";
    }
}
