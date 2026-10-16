<?php

use App\Console\Commands\SendTestEmailCommand;
use App\Enums\SchoolRole;
use App\Livewire\Auth\AcceptInvitation;
use App\Livewire\School\Teachers\Index as TeachersIndex;
use App\Mail\TeacherInvitationMail;
use App\Mail\TestEmail;
use App\Models\Invitation;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\SchoolMembershipRole;
use App\Models\Shift;
use App\Models\Teacher;
use App\Models\TeacherSchool;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->shift = Shift::updateOrCreate(['code' => 'MATUTINO'], ['name' => 'Matutino']);

    $this->admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);

    $this->schoolA = School::create(['name' => 'Secundaria Alfa', 'code' => 'SEC-ALFA']);
    $this->schoolB = School::create(['name' => 'Secundaria Beta', 'code' => 'SEC-BETA']);

    $this->schoolAdmin = User::factory()->create();
    $membership = SchoolMembership::create([
        'school_id' => $this->schoolA->id,
        'user_id' => $this->schoolAdmin->id,
    ]);
    SchoolMembershipRole::create([
        'school_membership_id' => $membership->id,
        'role' => SchoolRole::ADMIN_ESCOLAR->value,
    ]);
});

test('school admin can invite a teacher and transactional email is dispatched', function () {
    Mail::fake();

    Livewire::actingAs($this->schoolAdmin)
        ->test(TeachersIndex::class, ['school' => $this->schoolA])
        ->set('inviteFirstName', 'Sor Juana')
        ->set('inviteLastName', 'Inés de la Cruz')
        ->set('inviteEmail', 'sorjuana@escuela.edu')
        ->set('inviteEmployeeNumber', 'PROF-200')
        ->set('invitePhone', '5551234567')
        ->call('sendInvitation')
        ->assertHasNoErrors();

    $invitation = Invitation::where('email', 'sorjuana@escuela.edu')->first();
    expect($invitation)->not->toBeNull()
        ->and($invitation->first_name)->toBe('Sor Juana')
        ->and($invitation->last_name)->toBe('Inés de la Cruz')
        ->and($invitation->school_id)->toBe($this->schoolA->id)
        ->and($invitation->status)->toBe('PENDING')
        ->and($invitation->token_hash)->not->toBeNull();

    Mail::assertQueued(TeacherInvitationMail::class, function ($mail) {
        return $mail->hasTo('sorjuana@escuela.edu');
    });
});

test('cannot invite teacher who is already assigned to the school', function () {
    Mail::fake();

    $teacher = Teacher::create([
        'first_name' => 'Mario',
        'last_name' => 'Molina',
        'email' => 'mario.molina@escuela.edu',
    ]);

    TeacherSchool::create([
        'teacher_id' => $teacher->id,
        'school_id' => $this->schoolA->id,
    ]);

    Livewire::actingAs($this->schoolAdmin)
        ->test(TeachersIndex::class, ['school' => $this->schoolA])
        ->set('inviteFirstName', 'Mario')
        ->set('inviteLastName', 'Molina')
        ->set('inviteEmail', 'mario.molina@escuela.edu')
        ->call('sendInvitation')
        ->assertHasErrors(['inviteEmail']);
});

test('school admin can resend and revoke a pending invitation', function () {
    Mail::fake();

    $result = Invitation::createInvitation($this->schoolA, $this->schoolAdmin, [
        'email' => 'resend@escuela.edu',
        'first_name' => 'Alfonso',
        'last_name' => 'Reyes',
        'role' => SchoolRole::PROFESOR->value,
    ]);

    $invitation = $result['invitation'];
    $originalTokenHash = $invitation->token_hash;

    // Resend
    Livewire::actingAs($this->schoolAdmin)
        ->test(TeachersIndex::class, ['school' => $this->schoolA])
        ->call('resendInvitation', $invitation->id);

    $invitation->refresh();
    expect($invitation->token_hash)->not->toBe($originalTokenHash);
    Mail::assertQueued(TeacherInvitationMail::class);

    // Revoke
    Livewire::actingAs($this->schoolAdmin)
        ->test(TeachersIndex::class, ['school' => $this->schoolA])
        ->call('revokeInvitation', $invitation->id);

    $invitation->refresh();
    expect($invitation->status)->toBe('REVOKED');
});

test('teacher can accept invitation, set password and activate account', function () {
    $result = Invitation::createInvitation($this->schoolA, $this->schoolAdmin, [
        'email' => 'nuevo.docente@escuela.edu',
        'first_name' => 'Jaime',
        'last_name' => 'Sabines',
        'employee_number' => 'DOC-88',
        'phone' => '9611234567',
        'role' => SchoolRole::PROFESOR->value,
    ]);

    $plainToken = $result['plain_token'];

    Livewire::test(AcceptInvitation::class, ['token' => $plainToken])
        ->set('password', 'NuevaClaveSegura2026!')
        ->set('password_confirmation', 'NuevaClaveSegura2026!')
        ->call('accept')
        ->assertHasNoErrors()
        ->assertRedirect(route('school.dashboard', $this->schoolA));

    // Verify User
    $user = User::where('email', 'nuevo.docente@escuela.edu')->first();
    expect($user)->not->toBeNull()
        ->and(Hash::check('NuevaClaveSegura2026!', $user->password))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->hasSchoolRole($this->schoolA, SchoolRole::PROFESOR))->toBeTrue();

    // Verify Teacher
    $teacher = Teacher::where('user_id', $user->id)->first();
    expect($teacher)->not->toBeNull()
        ->and($teacher->first_name)->toBe('Jaime')
        ->and($teacher->last_name)->toBe('Sabines')
        ->and($teacher->employee_number)->toBe('DOC-88')
        ->and($teacher->schools()->where('schools.id', $this->schoolA->id)->exists())->toBeTrue();

    // Verify Invitation Status
    $invitation = Invitation::where('email', 'nuevo.docente@escuela.edu')->first();
    expect($invitation->status)->toBe('ACCEPTED')
        ->and($invitation->accepted_at)->not->toBeNull();
});

test('expired or revoked invitation cannot be accepted', function () {
    $expired = Invitation::createInvitation($this->schoolA, $this->schoolAdmin, [
        'email' => 'expirado@escuela.edu',
        'first_name' => 'Juan',
        'last_name' => 'Rulfo',
    ]);
    $expired['invitation']->update(['expires_at' => now()->subDay()]);

    Livewire::test(AcceptInvitation::class, ['token' => $expired['plain_token']])
        ->assertSet('isValid', false)
        ->call('accept')
        ->assertHasErrors(['general']);

    $revoked = Invitation::createInvitation($this->schoolA, $this->schoolAdmin, [
        'email' => 'revocado@escuela.edu',
        'first_name' => 'Carlos',
        'last_name' => 'Fuentes',
    ]);
    $revoked['invitation']->update(['status' => 'REVOKED']);

    Livewire::test(AcceptInvitation::class, ['token' => $revoked['plain_token']])
        ->assertSet('isValid', false)
        ->call('accept')
        ->assertHasErrors(['general']);
});

test('mail test command sends test email safely', function () {
    Mail::fake();

    $this->artisan(SendTestEmailCommand::class, ['recipient' => 'test@example.com'])
        ->expectsOutputToContain('El mensaje de prueba fue enviado y aceptado por el transporte')
        ->assertSuccessful();

    Mail::assertSent(TestEmail::class, function ($mail) {
        return $mail->hasTo('test@example.com');
    });
});
