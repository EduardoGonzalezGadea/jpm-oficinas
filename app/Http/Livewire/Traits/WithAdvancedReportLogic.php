<?php

namespace App\Http\Livewire\Traits;

trait WithAdvancedReportLogic
{
    public $filters = [];

    public function resetFilters()
    {
        $this->filters = [];
        $this->resetPageIfTraitExists();
    }

    public function updatedFilters()
    {
        $this->resetPageIfTraitExists();
    }

    protected function resetPageIfTraitExists()
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function hasActiveFilters()
    {
        foreach ($this->filters as $value) {
            if (!empty($value)) {
                return true;
            }
        }
        return false;
    }

    public function validateAndGenerate()
    {
        if (!$this->hasActiveFilters()) {
            $this->emit('alert', [
                'type' => 'warning',
                'message' => 'Debe seleccionar al menos un criterio de búsqueda para generar el reporte.'
            ]);
            return;
        }

        // Logic to be implemented by component to generate report URL or redirect
        if (method_exists($this, 'generateReport')) {
            $this->generateReport();
        }
    }
}
