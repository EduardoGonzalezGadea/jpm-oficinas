<?php

namespace App\Livewire\Traits;

trait LivewireAlerts
{
    /**
     * Muestra una alerta de éxito tipo Toast o Modal según configuración frontend.
     */
    public function alertSuccess($message, $title = 'Éxito')
    {
        $this->dispatch('swal:success', title: $title, text: $message);
    }

    /**
     * Muestra una alerta de error.
     */
    public function alertError($message, $title = 'Error')
    {
        $this->dispatch('swal:error', title: $title, text: $message);
    }

    /**
     * Muestra una alerta informativa.
     */
    public function alertInfo($message, $title = 'Información')
    {
        $this->dispatch('swal:alert', type: 'info', title: $title, text: $message);
    }

    /**
     * Muestra una alerta de advertencia.
     */
    public function alertWarning($message, $title = 'Advertencia')
    {
        $this->dispatch('swal:alert', type: 'warning', title: $title, text: $message);
    }
}
