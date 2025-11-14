<?php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tesoreria\TesCchAcreedor;
use App\Http\Livewire\Traits\WithModal;

class Acreedores extends Component
{
    use WithPagination, WithModal;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $nombre;
    public $acreedorId;


    protected function rules()
    {
        return [
            'nombre' => 'required|string|min:2|max:255|unique:tes_cch_acreedores,acreedor,' . $this->acreedorId . ',idAcreedores,deleted_at,NULL',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del acreedor es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
        'nombre.unique' => 'Ya existe un acreedor con este nombre.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }


    public function resetForm()
    {
        $this->nombre = '';
        $this->acreedorId = null;
        $this->resetValidation();
    }

    public function create()
    {
        try {
            \Log::info('Intentando crear un nuevo acreedor');
            $this->resetForm();
            $this->openModal('formModalAcreedores'); // Llamada corregida
        } catch (\Exception $e) {
            \Log::error('Error en create: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            \Log::info('Intentando cargar acreedor con ID: ' . $id);
            $this->resetForm();
            $this->acreedorId = $id;
            $acreedor = TesCchAcreedor::findOrFail($id);
            \Log::info('Acreedor cargado: ' . json_encode($acreedor));
            $this->nombre = $acreedor->acreedor;
            $this->openModal('formModalAcreedores'); // Llamada corregida
        } catch (\Exception $e) {
            \Log::error('Error en edit: ' . $e->getMessage());
            $this->dispatchBrowserEvent('swal:error', ['text' => 'Error al cargar el acreedor.']);
        }
    }

    public function save()
    {
        \Log::info('Método save ejecutado para acreedor: ' . $this->nombre);

        try {
            $this->validate();

            if ($this->acreedorId) {
                $acreedor = TesCchAcreedor::findOrFail($this->acreedorId);
                $acreedor->update(['acreedor' => $this->nombre]);
                $mensaje = 'Acreedor actualizado correctamente.';
            } else {
                TesCchAcreedor::create(['acreedor' => $this->nombre]);
                $mensaje = 'Acreedor creado correctamente.';
            }

            \Log::info('DEBUG: About to dispatch success alert for acreedor: ' . $mensaje);

            // SOLUCIÓN CORRECTA: Alerta PRIMERO, luego cerrar modal, luego resetear
            // Esto evita que el re-renderizado interfiera con la alerta
            $this->dispatchBrowserEvent('swal:success', [
                'title' => 'Éxito',
                'text' => $mensaje
            ]);

            \Log::info('DEBUG: Success alert dispatched for acreedor');

            // Cerrar modal ANTES del reset para evitar interferencia
            $this->closeModal('formModalAcreedores');

            // Resetear formulario DESPUÉS de cerrar modal
            $this->resetForm();

        } catch (\Illuminate\Validation\ValidationException $e) {
            // La validación falló, no cerrar el modal ni resetear el formulario
            \Log::warning('Validación fallida para acreedor: ' . json_encode($e->errors()));
            $this->setErrorBag($e->errors());
            // Los errores se mostrarán automáticamente en la vista
            return; // Importante: no continuar con el flujo normal
        } catch (\Exception $e) {
            \Log::error('Error en save: ' . $e->getMessage());
            $this->dispatchBrowserEvent('swal:error', ['text' => 'Error al guardar el acreedor.']);
        }
    }

    public function confirmDelete($id)
    {
        try {
            \Log::info('Intentando confirmar eliminación para el acreedor con ID: ' . $id);
            $this->resetForm();
            $this->acreedorId = $id;
            $this->openModal('deleteModalAcreedores'); // Llamada corregida
        } catch (\Exception $e) {
            \Log::error('Error en confirmDelete: ' . $e->getMessage());
        }
    }

    public function delete()
    {
        try {
            TesCchAcreedor::findOrFail($this->acreedorId)->delete();

            \Log::info('DEBUG: About to dispatch success alert for delete acreedor: ');

            // SOLUCIÓN CORRECTA: Alerta PRIMERO, luego cerrar modal, luego resetear
            $this->dispatchBrowserEvent('swal:success', [
                'title' => 'Éxito',
                'text' => 'Acreedor eliminado correctamente.'
            ]);

            // Cerrar modal ANTES del reset para evitar interferencia
            $this->closeModal('deleteModalAcreedores');

            // Resetear formulario DESPUÉS de cerrar modal
            $this->resetForm();

            \Log::info('DEBUG: Success alert dispatched for delete acreedor');
        } catch (\Exception $e) {
            \Log::error('Error en delete: ' . $e->getMessage());
            $this->dispatchBrowserEvent('swal:error', ['text' => 'Error al eliminar el acreedor.']);
        }
    }


    public function render()
    {
        $acreedores = TesCchAcreedor::where('acreedor', 'like', '%' . $this->search . '%')
            ->orderBy('acreedor', 'asc')
            ->paginate(10);

        return view('livewire.tesoreria.caja-chica.acreedores', [
            'acreedores' => $acreedores
        ]);
    }
}
