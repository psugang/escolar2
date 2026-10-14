<?php

namespace App\Livewire\Admin\Schools;

use App\Enums\SchoolRole;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\SchoolMembershipRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Administración de Escuelas')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    // Create modal state
    public bool $showCreateModal = false;

    public string $createName = '';

    public string $createCode = '';

    // Edit modal state
    public bool $showEditModal = false;

    public ?string $editingSchoolId = null;

    public string $editName = '';

    public string $editCode = '';

    // Manage Admins modal state
    public bool $showAdminsModal = false;

    public ?string $selectedSchoolId = null;

    public string $adminMode = 'existing'; // 'existing' | 'new'

    public string $adminEmail = '';

    public string $newAdminName = '';

    public string $newAdminEmail = '';

    public string $newAdminPassword = '';

    // Delete modal state
    public bool $showDeleteModal = false;

    public ?string $deletingSchoolId = null;

    public string $deletingSchoolName = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['createName', 'createCode']);
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function createSchool(): void
    {
        $this->validate([
            'createName' => ['required', 'string', 'max:150'],
            'createCode' => ['nullable', 'string', 'max:30', 'unique:schools,code'],
        ], [
            'createName.required' => 'El nombre de la escuela es obligatorio.',
            'createName.max' => 'El nombre no puede exceder 150 caracteres.',
            'createCode.max' => 'El código no puede exceder 30 caracteres.',
            'createCode.unique' => 'Ya existe una escuela registrada con este código.',
        ]);

        School::create([
            'name' => trim($this->createName),
            'code' => $this->createCode ? trim(strtoupper($this->createCode)) : null,
        ]);

        $this->showCreateModal = false;
        $this->reset(['createName', 'createCode']);

        Flux::toast(variant: 'success', text: 'Escuela registrada correctamente.');
    }

    public function openEditModal(string $schoolId): void
    {
        $school = School::findOrFail($schoolId);

        $this->editingSchoolId = $school->id;
        $this->editName = $school->name;
        $this->editCode = $school->code ?? '';

        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function updateSchool(): void
    {
        $school = School::findOrFail($this->editingSchoolId);

        $this->validate([
            'editName' => ['required', 'string', 'max:150'],
            'editCode' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('schools', 'code')->ignore($school->id),
            ],
        ], [
            'editName.required' => 'El nombre de la escuela es obligatorio.',
            'editName.max' => 'El nombre no puede exceder 150 caracteres.',
            'editCode.max' => 'El código no puede exceder 30 caracteres.',
            'editCode.unique' => 'Ya existe otra escuela con este código.',
        ]);

        $school->update([
            'name' => trim($this->editName),
            'code' => $this->editCode ? trim(strtoupper($this->editCode)) : null,
        ]);

        $this->showEditModal = false;
        $this->reset(['editingSchoolId', 'editName', 'editCode']);

        Flux::toast(variant: 'success', text: 'Escuela actualizada correctamente.');
    }

    public function openAdminsModal(string $schoolId): void
    {
        $this->selectedSchoolId = $schoolId;
        $this->reset([
            'adminMode',
            'adminEmail',
            'newAdminName',
            'newAdminEmail',
            'newAdminPassword',
        ]);
        $this->resetValidation();
        $this->showAdminsModal = true;
    }

    public function assignExistingAdmin(): void
    {
        $this->validate([
            'adminEmail' => ['required', 'email'],
        ], [
            'adminEmail.required' => 'El correo electrónico es obligatorio.',
            'adminEmail.email' => 'El correo electrónico no es válido.',
        ]);

        $user = User::where('email', trim($this->adminEmail))->first();

        if (! $user) {
            $this->addError('adminEmail', 'No existe ningún usuario con este correo electrónico.');

            return;
        }

        $membership = SchoolMembership::firstOrCreate([
            'school_id' => $this->selectedSchoolId,
            'user_id' => $user->id,
        ]);

        $alreadyHasRole = SchoolMembershipRole::where('school_membership_id', $membership->id)
            ->where('role', SchoolRole::ADMIN_ESCOLAR->value)
            ->exists();

        if ($alreadyHasRole) {
            $this->addError('adminEmail', 'Este usuario ya tiene el rol de Administrador Escolar en este plantel.');

            return;
        }

        SchoolMembershipRole::create([
            'school_membership_id' => $membership->id,
            'role' => SchoolRole::ADMIN_ESCOLAR->value,
        ]);

        $this->reset('adminEmail');
        Flux::toast(variant: 'success', text: "Administrador escolar asignado a {$user->name}.");
    }

    public function registerAndAssignNewAdmin(): void
    {
        $this->validate([
            'newAdminName' => ['required', 'string', 'max:255'],
            'newAdminEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'newAdminPassword' => ['required', 'string', 'min:12'],
        ], [
            'newAdminName.required' => 'El nombre es obligatorio.',
            'newAdminEmail.required' => 'El correo electrónico es obligatorio.',
            'newAdminEmail.unique' => 'Ya existe un usuario con este correo electrónico.',
            'newAdminPassword.required' => 'La contraseña es obligatoria.',
            'newAdminPassword.min' => 'La contraseña debe tener al menos 12 caracteres.',
        ]);

        $user = User::create([
            'name' => trim($this->newAdminName),
            'email' => trim($this->newAdminEmail),
            'password' => Hash::make($this->newAdminPassword),
            'email_verified_at' => now(),
        ]);

        $membership = SchoolMembership::create([
            'school_id' => $this->selectedSchoolId,
            'user_id' => $user->id,
        ]);

        SchoolMembershipRole::create([
            'school_membership_id' => $membership->id,
            'role' => SchoolRole::ADMIN_ESCOLAR->value,
        ]);

        $this->reset(['newAdminName', 'newAdminEmail', 'newAdminPassword']);
        Flux::toast(variant: 'success', text: "Nuevo usuario {$user->name} creado y asignado como Administrador Escolar.");
    }

    public function revokeAdminRole(string $membershipId): void
    {
        $membership = SchoolMembership::findOrFail($membershipId);

        SchoolMembershipRole::where('school_membership_id', $membership->id)
            ->where('role', SchoolRole::ADMIN_ESCOLAR->value)
            ->delete();

        // If the membership has no remaining roles, we can remove the membership
        if ($membership->roles()->count() === 0) {
            $membership->delete();
        }

        Flux::toast(variant: 'success', text: 'Rol de Administrador Escolar revocado.');
    }

    public function openDeleteModal(string $schoolId): void
    {
        $school = School::findOrFail($schoolId);
        $this->deletingSchoolId = $school->id;
        $this->deletingSchoolName = $school->name;
        $this->showDeleteModal = true;
    }

    public function deleteSchool(): void
    {
        $school = School::findOrFail($this->deletingSchoolId);

        if ($school->schoolYears()->count() > 0) {
            Flux::toast(variant: 'danger', text: 'No se puede eliminar la escuela porque contiene ciclos escolares asociados.');
            $this->showDeleteModal = false;

            return;
        }

        $school->memberships()->delete();
        $school->delete();

        $this->showDeleteModal = false;
        $this->reset(['deletingSchoolId', 'deletingSchoolName']);

        Flux::toast(variant: 'success', text: 'Escuela eliminada exitosamente.');
    }

    public function render(): View
    {
        $searchTerm = strtolower(trim($this->search));

        $schools = School::query()
            ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereLike('name', "%{$searchTerm}%")
                        ->orWhereLike('code', "%{$searchTerm}%");
                });
            })
            ->withCount(['schoolYears'])
            ->with([
                'memberships' => function ($query) {
                    $query->whereHas('roles', fn ($q) => $q->where('role', SchoolRole::ADMIN_ESCOLAR->value))
                        ->with(['user', 'roles']);
                },
            ])
            ->orderBy('name')
            ->paginate(10);

        $selectedSchool = $this->selectedSchoolId
            ? School::with([
                'memberships' => function ($query) {
                    $query->whereHas('roles', fn ($q) => $q->where('role', SchoolRole::ADMIN_ESCOLAR->value))
                        ->with(['user', 'roles']);
                },
            ])->find($this->selectedSchoolId)
            : null;

        return view('livewire.admin.schools.index', [
            'schools' => $schools,
            'selectedSchool' => $selectedSchool,
        ]);
    }
}
