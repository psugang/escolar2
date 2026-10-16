<?php

namespace App\Livewire\School\Teachers;

use App\Enums\SchoolRole;
use App\Mail\TeacherInvitationMail;
use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\SchoolMembershipRole;
use App\Models\Teacher;
use App\Models\TeacherSchool;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Control de Profesores')]
class Index extends Component
{
    use WithPagination;

    #[Locked]
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

    // Modal Invite Teacher
    public bool $showInviteModal = false;

    public string $inviteFirstName = '';

    public string $inviteLastName = '';

    public string $inviteEmail = '';

    public string $inviteEmployeeNumber = '';

    public string $invitePhone = '';

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
        abort_if(auth()->user()?->isTeacherOnly($school), 403);

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
        $teacher = $this->school->teachers()->findOrFail($teacherId);

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
        $teacher = $this->school->teachers()->findOrFail($this->editingTeacherId);

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
        $teacher = $this->school->teachers()->findOrFail($teacherId);
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

    public function openInviteModal(): void
    {
        $this->reset([
            'inviteFirstName',
            'inviteLastName',
            'inviteEmail',
            'inviteEmployeeNumber',
            'invitePhone',
        ]);
        $this->resetValidation();
        $this->showInviteModal = true;
    }

    public function sendInvitation(): void
    {
        $this->validate([
            'inviteFirstName' => ['required', 'string', 'max:100'],
            'inviteLastName' => ['required', 'string', 'max:100'],
            'inviteEmail' => ['required', 'email', 'max:150'],
            'inviteEmployeeNumber' => ['nullable', 'string', 'max:50'],
            'invitePhone' => ['nullable', 'string', 'max:30'],
        ], [
            'inviteFirstName.required' => 'El nombre es obligatorio.',
            'inviteLastName.required' => 'Los apellidos son obligatorios.',
            'inviteEmail.required' => 'El correo electrónico es obligatorio.',
            'inviteEmail.email' => 'El correo electrónico no es válido.',
        ]);

        $email = strtolower(trim($this->inviteEmail));

        $alreadyTeacherInSchool = Teacher::where('email', $email)
            ->whereHas('teacherSchools', fn ($q) => $q->where('school_id', $this->school->id))
            ->exists();

        if ($alreadyTeacherInSchool) {
            $this->addError('inviteEmail', 'Este profesor ya está adscrito a este plantel escolar.');

            return;
        }

        $inviter = auth()->user();
        if (! $inviter) {
            $this->addError('inviteEmail', 'Usuario no autenticado.');

            return;
        }

        $rateLimitKey = 'teacher-invite:'.$this->school->id.':'.$inviter->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Flux::toast(variant: 'danger', text: "Límite de invitaciones alcanzado. Espera {$seconds} segundos.");

            return;
        }
        RateLimiter::hit($rateLimitKey, 60);

        $result = Invitation::createInvitation($this->school, $inviter, [
            'email' => $email,
            'first_name' => $this->inviteFirstName,
            'last_name' => $this->inviteLastName,
            'employee_number' => $this->inviteEmployeeNumber,
            'phone' => $this->invitePhone,
            'role' => SchoolRole::PROFESOR->value,
        ]);

        Mail::to($result['invitation']->email)->send(
            new TeacherInvitationMail($result['invitation'], $result['plain_token'])
        );

        AuditLog::record(
            action: 'PROFESOR_INVITADO',
            description: "Se envió invitación al docente {$result['invitation']->full_name} al correo {$result['invitation']->email}.",
            school: $this->school,
            user: $inviter,
            newValues: [
                'email' => $result['invitation']->email,
                'role' => SchoolRole::PROFESOR->value,
                'invitation_id' => $result['invitation']->id,
            ]
        );

        $this->showInviteModal = false;
        Flux::toast(variant: 'success', text: "Invitación enviada a {$result['invitation']->email}.");
    }

    public function resendInvitation(string $invitationId): void
    {
        $inviterId = auth()->id() ?? 'guest';
        $rateLimitKey = 'teacher-resend:'.$this->school->id.':'.$inviterId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Flux::toast(variant: 'danger', text: "Límite de reenvíos alcanzado. Espera {$seconds} segundos.");

            return;
        }
        RateLimiter::hit($rateLimitKey, 60);

        $invitation = Invitation::where('id', $invitationId)
            ->where('school_id', $this->school->id)
            ->where('status', 'PENDING')
            ->first();

        if (! $invitation) {
            Flux::toast(variant: 'danger', text: 'La invitación no es válida o ya expiró.');

            return;
        }

        $plainToken = Str::random(48);
        $invitation->update([
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(3),
        ]);

        Mail::to($invitation->email)->send(
            new TeacherInvitationMail($invitation, $plainToken)
        );

        Flux::toast(variant: 'success', text: "Invitación reenviada a {$invitation->email}.");
    }

    public function revokeInvitation(string $invitationId): void
    {
        $invitation = Invitation::where('id', $invitationId)
            ->where('school_id', $this->school->id)
            ->where('status', 'PENDING')
            ->first();

        if (! $invitation) {
            Flux::toast(variant: 'danger', text: 'Invitación no encontrada.');

            return;
        }

        $invitation->update(['status' => 'REVOKED']);

        Flux::toast(variant: 'success', text: "Invitación revocada para {$invitation->email}.");
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

        $pendingInvitations = Invitation::query()
            ->where('school_id', $this->school->id)
            ->where('status', 'PENDING')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        return view('livewire.school.teachers.index', [
            'teachers' => $teachers,
            'otherTeachers' => $otherTeachers,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }
}
