<?php

namespace App\Livewire\School\Teachers;

use App\Enums\SchoolRole;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\SchoolMembershipRole;
use App\Models\Teacher;
use App\Models\TeacherSchool;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Control de Profesores')]
class Index extends Component
{
    use WithPagination;

    public School $school;

    public string $search = '';

    // Modal Create / Link Teacher
    public bool $showCreateModal = false;

    public string $teacherMode = 'new'; // 'new' | 'existing'

    public string $newFirstName = '';

    public string $newLastName = '';

    public string $newEmployeeNumber = '';

    public string $newPhone = '';

    public string $newEmail = '';

    public bool $createUserAccount = true;

    public string $newPassword = '';

    public string $existingTeacherId = '';

    // Modal Edit
    public bool $showEditModal = false;

    public ?string $editingTeacherId = null;

    public string $editFirstName = '';

    public string $editLastName = '';

    public string $editEmployeeNumber = '';

    public string $editPhone = '';

    public string $editEmail = '';

    // Modal Unlink
    public bool $showUnlinkModal = false;

    public ?string $unlinkingTeacherId = null;

    public string $unlinkingTeacherName = '';

    public function mount(School $school): void
    {
        $this->school = $school;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'newFirstName',
            'newLastName',
            'newEmployeeNumber',
            'newPhone',
            'newEmail',
            'newPassword',
            'existingTeacherId',
        ]);
        $this->teacherMode = 'new';
        $this->createUserAccount = true;
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function createTeacher(): void
    {
        if ($this->teacherMode === 'existing') {
            $this->validate([
                'existingTeacherId' => ['required', 'exists:teachers,id'],
            ]);

            $alreadyLinked = TeacherSchool::where('teacher_id', $this->existingTeacherId)
                ->where('school_id', $this->school->id)
                ->exists();

            if ($alreadyLinked) {
                $this->addError('existingTeacherId', 'Este profesor ya está adscrito a este plantel.');

                return;
            }

            TeacherSchool::create([
                'teacher_id' => $this->existingTeacherId,
                'school_id' => $this->school->id,
            ]);

            $teacher = Teacher::findOrFail($this->existingTeacherId);

            // If teacher has a user account, grant PROFESOR role in this school
            if ($teacher->user_id) {
                $membership = SchoolMembership::firstOrCreate([
                    'school_id' => $this->school->id,
                    'user_id' => $teacher->user_id,
                ]);

                SchoolMembershipRole::firstOrCreate([
                    'school_membership_id' => $membership->id,
                    'role' => SchoolRole::PROFESOR->value,
                ]);
            }

            $this->showCreateModal = false;
            Flux::toast(variant: 'success', text: "Profesor {$teacher->full_name} adscrito a la escuela.");

            return;
        }

        $this->validate([
            'newFirstName' => ['required', 'string', 'max:100'],
            'newLastName' => ['required', 'string', 'max:100'],
            'newEmployeeNumber' => ['nullable', 'string', 'max:50'],
            'newPhone' => ['nullable', 'string', 'max:30'],
            'newEmail' => ['nullable', 'email', 'max:150'],
            'newPassword' => [
                $this->createUserAccount ? 'required' : 'nullable',
                $this->createUserAccount ? 'min:12' : '',
            ],
        ], [
            'newFirstName.required' => 'El nombre es obligatorio.',
            'newLastName.required' => 'Los apellidos son obligatorios.',
            'newPassword.required' => 'La contraseña es obligatoria para crear la cuenta de acceso.',
            'newPassword.min' => 'La contraseña debe tener al menos 12 caracteres.',
        ]);

        $userId = null;

        if ($this->createUserAccount && $this->newEmail) {
            $user = User::where('email', trim($this->newEmail))->first();

            if (! $user) {
                $user = User::create([
                    'name' => trim("{$this->newFirstName} {$this->newLastName}"),
                    'email' => trim($this->newEmail),
                    'password' => Hash::make($this->newPassword),
                    'email_verified_at' => now(),
                ]);
            }

            $userId = $user->id;

            $membership = SchoolMembership::firstOrCreate([
                'school_id' => $this->school->id,
                'user_id' => $user->id,
            ]);

            SchoolMembershipRole::firstOrCreate([
                'school_membership_id' => $membership->id,
                'role' => SchoolRole::PROFESOR->value,
            ]);
        }

        $teacher = Teacher::create([
            'user_id' => $userId,
            'first_name' => trim($this->newFirstName),
            'last_name' => trim($this->newLastName),
            'employee_number' => $this->newEmployeeNumber ? trim($this->newEmployeeNumber) : null,
            'phone' => $this->newPhone ? trim($this->newPhone) : null,
            'email' => $this->newEmail ? trim($this->newEmail) : null,
        ]);

        TeacherSchool::create([
            'teacher_id' => $teacher->id,
            'school_id' => $this->school->id,
        ]);

        $this->showCreateModal = false;
        Flux::toast(variant: 'success', text: "Profesor {$teacher->full_name} registrado exitosamente.");
    }

    public function openEditModal(string $teacherId): void
    {
        $teacher = Teacher::findOrFail($teacherId);

        $this->editingTeacherId = $teacher->id;
        $this->editFirstName = $teacher->first_name;
        $this->editLastName = $teacher->last_name;
        $this->editEmployeeNumber = $teacher->employee_number ?? '';
        $this->editPhone = $teacher->phone ?? '';
        $this->editEmail = $teacher->email ?? '';

        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function updateTeacher(): void
    {
        $teacher = Teacher::findOrFail($this->editingTeacherId);

        $this->validate([
            'editFirstName' => ['required', 'string', 'max:100'],
            'editLastName' => ['required', 'string', 'max:100'],
            'editEmployeeNumber' => ['nullable', 'string', 'max:50'],
            'editPhone' => ['nullable', 'string', 'max:30'],
            'editEmail' => ['nullable', 'email', 'max:150'],
        ]);

        $teacher->update([
            'first_name' => trim($this->editFirstName),
            'last_name' => trim($this->editLastName),
            'employee_number' => $this->editEmployeeNumber ? trim($this->editEmployeeNumber) : null,
            'phone' => $this->editPhone ? trim($this->editPhone) : null,
            'email' => $this->editEmail ? trim($this->editEmail) : null,
        ]);

        $this->showEditModal = false;
        Flux::toast(variant: 'success', text: 'Datos del profesor actualizados.');
    }

    public function openUnlinkModal(string $teacherId): void
    {
        $teacher = Teacher::findOrFail($teacherId);
        $this->unlinkingTeacherId = $teacher->id;
        $this->unlinkingTeacherName = $teacher->full_name;
        $this->showUnlinkModal = true;
    }

    public function unlinkTeacher(): void
    {
        TeacherSchool::where('teacher_id', $this->unlinkingTeacherId)
            ->where('school_id', $this->school->id)
            ->delete();

        $this->showUnlinkModal = false;
        $this->reset(['unlinkingTeacherId', 'unlinkingTeacherName']);

        Flux::toast(variant: 'success', text: 'Profesor desvinculado de este plantel.');
    }

    public function render(): View
    {
        $searchTerm = strtolower(trim($this->search));

        $teachers = Teacher::query()
            ->whereHas('teacherSchools', fn ($q) => $q->where('school_id', $this->school->id))
            ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereLike('first_name', "%{$searchTerm}%")
                        ->orWhereLike('last_name', "%{$searchTerm}%")
                        ->orWhereLike('employee_number', "%{$searchTerm}%")
                        ->orWhereLike('email', "%{$searchTerm}%");
                });
            })
            ->with(['schools', 'classes' => fn ($q) => $q->where('school_id', $this->school->id)])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        $otherTeachers = Teacher::query()
            ->whereDoesntHave('teacherSchools', fn ($q) => $q->where('school_id', $this->school->id))
            ->orderBy('last_name')
            ->get();

        return view('livewire.school.teachers.index', [
            'teachers' => $teachers,
            'otherTeachers' => $otherTeachers,
        ]);
    }
}
