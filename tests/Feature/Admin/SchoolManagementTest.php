<?php

use App\Enums\SchoolRole;
use App\Livewire\Admin\Schools\Index as SchoolIndex;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\SchoolMembershipRole;
use App\Models\SchoolYear;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from admin schools page to login', function () {
    $response = $this->get(route('admin.schools.index'));

    $response->assertRedirect(route('login'));
});

test('regular users cannot access admin schools page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.schools.index'));

    $response->assertForbidden();
});

test('global admin can access admin schools page and view schools', function () {
    $admin = User::factory()->create([
        'global_role' => 'ADMIN_GLOBAL',
    ]);

    School::create([
        'name' => 'Escuela Secundaria Benito Juárez',
        'code' => 'SEC-001',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.schools.index'));

    $response->assertOk();
    $response->assertSee('Escuela Secundaria Benito Juárez');
    $response->assertSee('SEC-001');
});

test('global admin can create a new school via livewire', function () {
    $admin = User::factory()->create([
        'global_role' => 'ADMIN_GLOBAL',
    ]);

    Livewire::actingAs($admin)
        ->test(SchoolIndex::class)
        ->set('createName', 'Secundaria Federal No. 5')
        ->set('createCode', 'FED-005')
        ->call('createSchool')
        ->assertHasNoErrors();

    expect(School::where('name', 'Secundaria Federal No. 5')->where('code', 'FED-005')->exists())->toBeTrue();
});

test('school creation validates required name and unique code', function () {
    $admin = User::factory()->create([
        'global_role' => 'ADMIN_GLOBAL',
    ]);

    School::create([
        'name' => 'Escuela Existente',
        'code' => 'DUPLICADO',
    ]);

    Livewire::actingAs($admin)
        ->test(SchoolIndex::class)
        ->set('createName', '')
        ->set('createCode', 'DUPLICADO')
        ->call('createSchool')
        ->assertHasErrors(['createName', 'createCode']);
});

test('global admin can update an existing school', function () {
    $admin = User::factory()->create([
        'global_role' => 'ADMIN_GLOBAL',
    ]);

    $school = School::create([
        'name' => 'Nombre Antiguo',
        'code' => 'OLD-01',
    ]);

    Livewire::actingAs($admin)
        ->test(SchoolIndex::class)
        ->call('openEditModal', $school->id)
        ->assertSet('editName', 'Nombre Antiguo')
        ->assertSet('editCode', 'OLD-01')
        ->set('editName', 'Nombre Actualizado')
        ->set('editCode', 'NEW-01')
        ->call('updateSchool')
        ->assertHasNoErrors();

    $school->refresh();
    expect($school->name)->toBe('Nombre Actualizado')
        ->and($school->code)->toBe('NEW-01');
});

test('global admin can assign an existing user as school admin', function () {
    $admin = User::factory()->create([
        'global_role' => 'ADMIN_GLOBAL',
    ]);

    $school = School::create(['name' => 'Secundaria 10', 'code' => 'SEC-10']);
    $director = User::factory()->create(['email' => 'director@secundaria10.edu']);

    Livewire::actingAs($admin)
        ->test(SchoolIndex::class)
        ->call('openAdminsModal', $school->id)
        ->set('adminEmail', 'director@secundaria10.edu')
        ->call('assignExistingAdmin')
        ->assertHasNoErrors();

    expect($director->hasSchoolRole($school, SchoolRole::ADMIN_ESCOLAR))->toBeTrue();
});

test('global admin can register and assign a new user as school admin', function () {
    $admin = User::factory()->create([
        'global_role' => 'ADMIN_GLOBAL',
    ]);

    $school = School::create(['name' => 'Secundaria 20', 'code' => 'SEC-20']);

    Livewire::actingAs($admin)
        ->test(SchoolIndex::class)
        ->call('openAdminsModal', $school->id)
        ->set('newAdminName', 'Profe Roberto Gómez')
        ->set('newAdminEmail', 'roberto@secundaria20.edu')
        ->set('newAdminPassword', 'PasswordSegura2026!')
        ->call('registerAndAssignNewAdmin')
        ->assertHasNoErrors();

    $newUser = User::where('email', 'roberto@secundaria20.edu')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->hasSchoolRole($school, SchoolRole::ADMIN_ESCOLAR))->toBeTrue();
});

test('global admin can revoke school admin role', function () {
    $admin = User::factory()->create([
        'global_role' => 'ADMIN_GLOBAL',
    ]);

    $school = School::create(['name' => 'Secundaria 30', 'code' => 'SEC-30']);
    $user = User::factory()->create();

    $membership = SchoolMembership::create([
        'school_id' => $school->id,
        'user_id' => $user->id,
    ]);

    SchoolMembershipRole::create([
        'school_membership_id' => $membership->id,
        'role' => SchoolRole::ADMIN_ESCOLAR->value,
    ]);

    expect($user->hasSchoolRole($school, SchoolRole::ADMIN_ESCOLAR))->toBeTrue();

    Livewire::actingAs($admin)
        ->test(SchoolIndex::class)
        ->call('revokeAdminRole', $membership->id)
        ->assertHasNoErrors();

    expect($user->fresh()->hasSchoolRole($school, SchoolRole::ADMIN_ESCOLAR))->toBeFalse();
});

test('global admin can delete a school without school years', function () {
    $admin = User::factory()->create([
        'global_role' => 'ADMIN_GLOBAL',
    ]);

    $school = School::create(['name' => 'Secundaria Vacia', 'code' => 'EMPTY-1']);

    Livewire::actingAs($admin)
        ->test(SchoolIndex::class)
        ->call('openDeleteModal', $school->id)
        ->call('deleteSchool')
        ->assertHasNoErrors();

    expect(School::find($school->id))->toBeNull();
});

test('global admin cannot delete a school with school years', function () {
    $admin = User::factory()->create([
        'global_role' => 'ADMIN_GLOBAL',
    ]);

    $school = School::create(['name' => 'Secundaria Con Ciclos', 'code' => 'CYC-1']);

    SchoolYear::create([
        'school_id' => $school->id,
        'name' => '2026-2027',
        'starts_on' => '2026-08-24',
        'ends_on' => '2027-07-09',
    ]);

    Livewire::actingAs($admin)
        ->test(SchoolIndex::class)
        ->call('openDeleteModal', $school->id)
        ->call('deleteSchool');

    expect(School::find($school->id))->not->toBeNull();
});
