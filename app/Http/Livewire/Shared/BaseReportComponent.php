<?php

namespace App\Http\Livewire\Shared;

use Livewire\Component;

abstract class BaseReportComponent extends Component
{
    public $filters = [];
    public $titulo;
    public $fecha_impresion;
    public $usuario_impresion;
    public $isPdf = false;

    public function mount($filters = [])
    {
        $this->isPdf = request()->query('pdf') == 1;
        // Si no se reciben filtros por argumento, intentar obtenerlos de la request (query string)
        if (empty($filters)) {
            $this->filters = request()->all();
        } else {
            $this->filters = $filters;
        }
        $this->fecha_impresion = now()->format('d/m/Y H:i');
        $this->usuario_impresion = auth()->check() ? auth()->user()->name : 'Sistema';

        if (method_exists($this, 'setupData')) {
            $this->setupData();
        }
    }

    /**
     * Abstract method to setup specific data for the report
     */
    abstract protected function setupData();

    public function render()
    {
        return view($this->getViewName())
            ->layout('layouts.print');
    }

    /**
     * Abstract method to get the view name
     */
    abstract protected function getViewName();
}
