<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Modulo;
use Illuminate\Support\Facades\Hash; // Importar Hash para restablecer contraseña

class UsersTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Escucha los eventos para refrescar el componente o ejecutar métodos
    protected $listeners = [
        'userUpdated' => '$refresh', // Para refrescar la tabla después de una acción
        'resetUserPassword',        // Escucha el evento 'resetUserPassword'
        'toggleUserStatus',         // Escucha el evento 'toggleUserStatus'
        'deleteUser',               // Escucha el evento 'deleteUser'
    ];

    // Propiedades para filtros y paginación
    public $search = '';
    public $statusFilter = 'all';
    public $moduleFilter = 'all';
    public $perPage = 10;

    // Propiedades para la lógica de permisos del usuario autenticado
    public $esAdministrador;
    public $idModulo;
    public $currentAuthId; // Añadimos esta propiedad para tener el ID del usuario autenticado

    /**
     * Se ejecuta una vez cuando el componente es inicializado.
     * Aquí se inicializan las propiedades de permisos del usuario autenticado.
     */
    public function mount()
    {
        $this->esAdministrador = auth()->user()->esAdministrador();
        $this->idModulo = auth()->user()->modulo_id;
        $this->currentAuthId = auth()->id(); // Asignamos el ID del usuario autenticado
    }

    // Propiedades que se sincronizan con la URL
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'moduleFilter' => ['except' => 'all'],
    ];

    /**
     * Resetea la paginación cuando el filtro de búsqueda cambia.
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Resetea la paginación cuando el filtro de estado cambia.
     */
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    /**
     * Resetea la paginación cuando el filtro de módulo cambia.
     */
    public function updatingModuleFilter()
    {
        $this->resetPage();
    }

    /**
     * Restablece la contraseña de un usuario a un valor predeterminado.
     *
     * @param int $userId El ID del usuario cuya contraseña se va a restablecer.
     */
    public function resetUserPassword($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            session()->flash('error', 'Usuario no encontrado.');
            return;
        }

        if ($user->id === 1) { // No permitir restablecer contraseña del usuario con ID 1
            session()->flash('error', 'No se puede restablecer la contraseña del usuario principal.');
            return;
        }

        $user->password = Hash::make('123456'); // Contraseña predeterminada
        $user->save();

        session()->flash('success', 'Contraseña restablecida exitosamente a 123456.');
        $this->emit('userUpdated'); // Emitir evento para refrescar la tabla y mostrar mensaje
    }

    /**
     * Cambia el estado (activo/inactivo) de un usuario.
     *
     * @param int $userId El ID del usuario cuyo estado se va a cambiar.
     */
    public function toggleUserStatus($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            session()->flash('error', 'Usuario no encontrado.');
            return;
        }

        if ($user->id === 1) { // No permitir cambiar estado del usuario con ID 1
            session()->flash('error', 'No se puede cambiar el estado del usuario principal.');
            return;
        }

        $user->activo = !$user->activo;
        $user->save();

        session()->flash('success', 'Estado del usuario cambiado exitosamente.');
        $this->emit('userUpdated'); // Emitir evento para refrescar la tabla y mostrar mensaje
    }

    /**
     * Elimina un usuario del sistema.
     *
     * @param int $userId El ID del usuario a eliminar.
     */
    public function deleteUser($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            session()->flash('error', 'Usuario no encontrado.');
            return;
        }

        // Asegurarse de que el usuario autenticado no se intente eliminar a sí mismo
        // y que el usuario con ID 1 (ej. administrador principal) no pueda ser eliminado.
        if ($this->currentAuthId === $user->id || $user->id === 1) {
            session()->flash('error', 'No tienes permiso para eliminar este usuario.');
            return;
        }

        $user->delete();
        session()->flash('success', 'Usuario eliminado exitosamente.');
        $this->emit('userUpdated'); // Emitir evento para refrescar la tabla y mostrar mensaje
    }

    /**
     * Renderiza la vista del componente y pasa los datos necesarios.
     * Aquí se construye la consulta de usuarios y se obtienen los módulos.
     */
    public function render()
    {
        // Construye la consulta de usuarios con filtros
        $users = User::query()
            ->with(['modulo', 'roles']) // Carga las relaciones 'modulo' y 'roles'
            ->when($this->search, function ($query) {
                // Filtra por nombre, apellido, email o cédula
                $query->where(function ($q) {
                    $q->where('nombre', 'like', '%' . $this->search . '%')
                        ->orWhere('apellido', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('cedula', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                // Filtra por estado (activo/inactivo)
                if ($this->statusFilter === 'active') {
                    $query->where('activo', true);
                } else {
                    $query->where('activo', false);
                }
            })
            ->when($this->moduleFilter !== 'all', function ($query) {
                // Filtra por ID de módulo
                $query->where('modulo_id', $this->moduleFilter);
            })
            ->when(!$this->esAdministrador, function ($query) {
                // Si el usuario no es administrador, solo ve usuarios de su módulo
                $query->where('modulo_id', $this->idModulo);
            })
            ->orderBy('nombre', 'asc') // Ordena por nombre ascendente
            ->orderBy('apellido', 'asc') // Luego por apellido ascendente
            ->paginate($this->perPage); // Pagina los resultados

        // Obtiene los módulos según los permisos del usuario autenticado
        if ($this->esAdministrador) {
            $modulos = Modulo::activos()->get();
        } else {
            $modulos = Modulo::activos()->where('id', $this->idModulo)->get();
        }

        // Pasa los datos a la vista del componente
        return view('livewire.users-table', [
            'users' => $users,
            'modulos' => $modulos,
            // 'currentAuthId' ya está disponible como propiedad del componente
        ]);
    }

    /**
     * Resetea todos los filtros y la paginación.
     */
    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->moduleFilter = 'all';
        $this->resetPage();
    }
}
