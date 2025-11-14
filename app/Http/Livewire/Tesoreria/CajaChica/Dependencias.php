<?php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tesoreria\Dependencia;
use App\Http\Livewire\Traits\WithModal;

class Dependencias extends Component
{
    use WithPagination, WithModal;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $nombre;
    public $dependenciaId;
    public $modalFormVisible = false;
    public $modalConfirmDeleteVisible = false;


    protected function rules()
    {
        return [
            'nombre' => 'required|string|min:2|max:255|unique:tes_cch_dependencias,dependencia,' . $this->dependenciaId . ',idDependencias,deleted_at,NULL',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre de la dependencia es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
        'nombre.unique' => 'Ya existe una dependencia con este nombre.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->nombre = '';
        $this->dependenciaId = null;
    }

    public function create()
    {
        try {
            $this->resetValidation();
            $this->resetForm();
            $this->openModal('formModal');
        } catch (\Exception $e) {
            \Log::error('Error en create: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $this->resetValidation();
            $this->dependenciaId = $id;
            $dependencia = Dependencia::findOrFail($id);
            $this->nombre = $dependencia->dependencia;
            $this->openModal('formModal');
        } catch (\Exception $e) {
            \Log::error('Error en edit: ' . $e->getMessage());
            $this->dispatchBrowserEvent('swal:error', ['text' => 'Error al cargar la dependencia.']);
        }
    }

    public function save()
    {
        \Log::info('Método save ejecutado para dependencia: ' . $this->nombre);

        try {
            $this->validate();

            if ($this->dependenciaId) {
                $dependencia = Dependencia::findOrFail($this->dependenciaId);
                $dependencia->update(['dependencia' => $this->nombre]);
                $mensaje = 'Dependencia actualizada correctamente.';
            } else {
                Dependencia::create(['dependencia' => $this->nombre]);
                $mensaje = 'Dependencia creada correctamente.';
            }

            \Log::info('DEBUG: About to dispatch success alert for dependencia: ' . $mensaje);

            // SOLUCIÓN CORRECTA: Alerta PRIMERO, luego cerrar modal, luego resetear
            // Esto evita que el re-renderizado interfiera con la alerta
            $this->dispatchBrowserEvent('swal:success', [
                'title' => 'Éxito',
                'text' => $mensaje
            ]);

            \Log::info('DEBUG: Success alert dispatched for dependencia');

            // Cerrar modal ANTES del reset para evitar interferencia
            $this->closeModal('formModal');

            // Resetear formulario DESPUÉS de cerrar modal
            $this->resetForm();

            \Log::info('Dependencia guardada exitosamente: ' . $this->nombre);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // La validación falló, no cerrar el modal ni resetear el formulario
            \Log::warning('Validación fallida para dependencia: ' . json_encode($e->errors()));
            $this->setErrorBag($e->errors());
            // Los errores se mostrarán automáticamente en la vista
            return; // Importante: no continuar con el flujo normal
        } catch (\Exception $e) {
            \Log::error('Error en save: ' . $e->getMessage());
            $this->dispatchBrowserEvent('swal:error', ['text' => 'Error al guardar la dependencia.']);
        }
    }

    public function confirmDelete($id)
    {
        try {
            $this->dependenciaId = $id;
            $this->openModal('deleteModal');
        } catch (\Exception $e) {
            \Log::error('Error en confirmDelete: ' . $e->getMessage());
        }
    }

    public function delete()
    {
        try {
            Dependencia::findOrFail($this->dependenciaId)->delete();

            \Log::info('DEBUG: About to dispatch success alert for delete: ');

            // SOLUCIÓN CORRECTA: Alerta PRIMERO, luego cerrar modal, luego resetear
            $this->dispatchBrowserEvent('swal:success', [
                'title' => 'Éxito',
                'text' => 'Dependencia eliminada correctamente.'
            ]);

            // Cerrar modal ANTES del reset para evitar interferencia
            $this->closeModal('deleteModal');

            // Resetear formulario DESPUÉS de cerrar modal
            $this->resetForm();

            \Log::info('DEBUG: Success alert dispatched for delete');
        } catch (\Exception $e) {
            \Log::error('Error en delete: ' . $e->getMessage());
            $this->dispatchBrowserEvent('swal:error', ['text' => 'Error al eliminar la dependencia.']);
        }
    }

    public function cerrarGestionDependencias()
    {
        $this->emit('cerrarModalDependencias');
    }

    public function render()
    {
        $dependencias = Dependencia::where('dependencia', 'like', '%' . $this->search . '%')
            ->orderBy('dependencia', 'asc')
            ->paginate(10);

        return view('livewire.tesoreria.caja-chica.dependencias', [
            'dependencias' => $dependencias
        ]);
    }
}
