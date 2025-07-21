<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Modulo;

class UsersTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['userStatusUpdated' => '$refresh']; // Opcional: Para recargar la tabla completa

    public $search = '';
    public $statusFilter = 'all';
    public $moduleFilter = 'all';
    public $perPage = 10; 
    public $esAdministrador;
    public $idModulo;

    public function mount()
    {
        $this->esAdministrador = auth()->user()->esAdministrador();
        $this->idModulo = auth()->user()->modulo_id;
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'moduleFilter' => ['except' => 'all'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingModuleFilter()
    {
        $this->resetPage();
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
                if ($this->statusFilter === 'active') {
                    $query->where('activo', true);
                } else {
                    $query->where('activo', false);
                }
            })
            ->when($this->moduleFilter !== 'all', function ($query) {
                $query->where('modulo_id', $this->moduleFilter);
            })
            ->when(!$this->esAdministrador, function ($query) {
                $query->where('modulo_id', $this->idModulo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        if ($this->esAdministrador) {
            $modulos = Modulo::activos()->get();
        } else {
            $modulos = Modulo::activos()->where('id', $this->idModulo)->get();
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