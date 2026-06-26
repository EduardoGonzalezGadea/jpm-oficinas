<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Modulo;
use Illuminate\Support\Facades\Hash;

class UsersTable extends Component
{
    use WithPagination;
    use Traits\LivewireAlerts;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'userUpdated' => '$refresh',
        'resetUserPassword',
        'toggleUserStatus',
        'deleteUser',
    ];

    public $search = '';
    public $statusFilter = 'all';
    public $moduleFilter = 'all';
    public $perPage = 10;

    public $esAdministrador;
    public $moduloClave;
    public $currentAuthId;

    public function mount()
    {
        $user = auth()->user();
        $this->esAdministrador = $user->esAdministrador();
        $this->moduloClave = $user->moduloClave();
        $this->currentAuthId = $user->id;
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'moduleFilter' => ['except' => 'all'],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingModuleFilter() { $this->resetPage(); }

    public function resetUserPassword($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            $this->alertError('Usuario no encontrado.');
            return;
        }

        if ($user->id === 1) {
            $this->alertError('No se puede restablecer la contraseña del usuario principal.');
            return;
        }

        $user->password = Hash::make('123456');
        $user->save();

        $this->alertSuccess('Contraseña restablecida exitosamente a 123456.');
        $this->emit('userUpdated');
    }

    public function toggleUserStatus($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            $this->alertError('Usuario no encontrado.');
            return;
        }

        if ($user->id === 1) {
            $this->alertError('No se puede cambiar el estado del usuario principal.');
            return;
        }

        $user->activo = !$user->activo;
        $user->save();

        $this->alertSuccess('Estado del usuario cambiado exitosamente.');
        $this->emit('userUpdated');
    }

    public function deleteUser($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            session()->flash('error', 'Usuario no encontrado.');
            return;
        }

        if ($this->currentAuthId === $user->id || $user->id === 1) {
            $this->alertError('No tienes permiso para eliminar este usuario.');
            return;
        }

        $user->delete();
        $this->alertSuccess('Usuario eliminado exitosamente.');
        $this->emit('userUpdated');
    }

    public function render()
    {
        $users = User::query()
            ->with(['modulo', 'roles'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nombre', 'like', '%' . $this->search . '%')
                        ->orWhere('apellido', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('cedula', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('activo', $this->statusFilter === 'active');
            })
            ->when($this->moduleFilter !== 'all', function ($query) {
                $query->where('modulo_id', $this->moduleFilter);
            })
            ->when(!$this->esAdministrador && $this->moduloClave, function ($query) {
                $query->delModulo($this->moduloClave);
            })
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->paginate($this->perPage);

        if ($this->esAdministrador) {
            $modulos = Modulo::activos()->get();
        } elseif ($this->moduloClave) {
            $modulos = Modulo::where('clave', $this->moduloClave)->get();
        } else {
            $modulos = collect();
        }

        return view('livewire.users-table', [
            'users' => $users,
            'modulos' => $modulos,
        ]);
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->moduleFilter = 'all';
        $this->resetPage();
    }
}
