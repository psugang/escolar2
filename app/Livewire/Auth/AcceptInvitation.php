<?php

namespace App\Livewire\Auth;

use App\Enums\SchoolRole;
use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\SchoolMembership;
use App\Models\SchoolMembershipRole;
use App\Models\Teacher;
use App\Models\TeacherSchool;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Activar Cuenta de Profesor')]
class AcceptInvitation extends Component
{
    public string $token = '';

    public ?string $invitationId = null;

    public bool $isValid = false;

    public ?string $errorMessage = null;

    public string $schoolName = '';

    public string $teacherName = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        $invitation = Invitation::findValidByToken($token);

        if (! $invitation) {
            $this->isValid = false;
            $this->errorMessage = 'Este enlace de invitación ha expirado, ya fue utilizado o es inválido. Por favor, solicita a la dirección de tu plantel un nuevo enlace.';

            return;
        }

        $this->isValid = true;
        $this->invitationId = $invitation->id;
        $this->schoolName = $invitation->school->name;
        $this->teacherName = $invitation->full_name;
        $this->email = $invitation->email;
    }

    public function accept(): void
    {
        if (! $this->isValid || ! $this->invitationId) {
            $this->addError('general', 'No se puede procesar una invitación inválida.');

            return;
        }

        $this->validate([
            'password' => ['required', 'string', Password::min(12)->letters()->numbers(), 'confirmed'],
        ], [
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe contener al menos 12 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        DB::transaction(function () {
            $invitation = Invitation::where('id', $this->invitationId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invitation->isValid()) {
                $this->isValid = false;
                $this->errorMessage = 'La invitación ya no se encuentra disponible o expiró.';

                return;
            }

            // 1. Create or find User
            $user = User::where('email', $invitation->email)->first();

            if (! $user) {
                $user = User::create([
                    'name' => $invitation->full_name,
                    'email' => $invitation->email,
                    'password' => Hash::make($this->password),
                ]);
                $user->markEmailAsVerified();
            } else {
                // If user already existed, update password and mark verified
                $user->update([
                    'password' => Hash::make($this->password),
                ]);
                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
            }

            // 2. Create or find Teacher record
            $teacher = Teacher::where('email', $invitation->email)->first();

            if (! $teacher) {
                $teacher = Teacher::create([
                    'user_id' => $user->id,
                    'first_name' => $invitation->first_name,
                    'last_name' => $invitation->last_name,
                    'employee_number' => $invitation->employee_number,
                    'phone' => $invitation->phone,
                    'email' => $invitation->email,
                ]);
            } else {
                $teacher->update([
                    'user_id' => $user->id,
                    'first_name' => $teacher->first_name ?: $invitation->first_name,
                    'last_name' => $teacher->last_name ?: $invitation->last_name,
                    'employee_number' => $teacher->employee_number ?: $invitation->employee_number,
                    'phone' => $teacher->phone ?: $invitation->phone,
                ]);
            }

            // 3. Link Teacher to School
            TeacherSchool::firstOrCreate([
                'teacher_id' => $teacher->id,
                'school_id' => $invitation->school_id,
            ]);

            // 4. Create SchoolMembership with PROFESOR role
            $membership = SchoolMembership::firstOrCreate([
                'school_id' => $invitation->school_id,
                'user_id' => $user->id,
            ]);

            SchoolMembershipRole::firstOrCreate([
                'school_membership_id' => $membership->id,
                'role' => SchoolRole::PROFESOR->value,
            ]);

            // 5. Mark invitation as accepted
            $invitation->update([
                'status' => 'ACCEPTED',
                'accepted_at' => now(),
            ]);

            // 6. Log audit event
            AuditLog::record(
                action: 'PROFESOR_INVITACION_ACEPTADA',
                description: "El profesor {$teacher->full_name} activó su cuenta con el correo {$invitation->email}.",
                school: $invitation->school,
                user: $user,
                auditable: $teacher,
                newValues: [
                    'email' => $invitation->email,
                    'school_id' => $invitation->school_id,
                ]
            );

            // 7. Authenticate user securely
            Auth::login($user);
            session()->regenerate();
        });

        if ($this->isValid) {
            $invitation = Invitation::find($this->invitationId);
            if ($invitation) {
                $this->redirect(route('school.dashboard', ['school' => $invitation->school_id]));
            }
        }
    }

    public function render(): View
    {
        return view('livewire.auth.accept-invitation');
    }
}
