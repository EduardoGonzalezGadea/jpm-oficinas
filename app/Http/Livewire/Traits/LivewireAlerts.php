<?php

namespace App\Http\Livewire\Traits;

trait LivewireAlerts
{
    /**
     * Muestra una alerta de éxito tipo Toast o Modal según configuración frontend.
     */
    public function alertSuccess($message, $title = 'Éxito')
    {
        $this->dispatchBrowserEvent('swal:success', [
            'title' => $title,
            'text' => $message,
        ]);
    }

    /**
     * Muestra una alerta de error.
     */
    public function alertError($message, $title = 'Error')
    {
        $this->dispatchBrowserEvent('swal:error', [
            'title' => $title,
            'text' => $message,
        ]);
    }

    /**
     * Muestra una alerta informativa.
     */
    public function alertInfo($message, $title = 'Información')
    {
        $this->dispatchBrowserEvent('swal:alert', [
            'type' => 'info',
            'title' => $title,
            'text' => $message,
        ]);
    }

    /**
     * Muestra una alerta de advertencia.
     */
    public function alertWarning($message, $title = 'Advertencia')
    {
        $this->dispatchBrowserEvent('swal:alert', [
            'type' => 'warning',
            'title' => $title,
            'text' => $message,
        ]);
    }
}
