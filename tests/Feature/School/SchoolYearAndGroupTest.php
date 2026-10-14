<?php

use App\Enums\SchoolRole;
use App\Livewire\School\SchoolYears\Index as SchoolYearsIndex;
use App\Models\School;
use App\Models\SchoolGroup;
use App\Models\SchoolMembership;
use App\Models\SchoolMembershipRole;
use App\Models\SchoolYear;
use App\Models\Shift;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Shift::updateOrCreate(['code' => 'MATUTINO'], ['name' => 'Matutino']);
    Shift::updateOrCreate(['code' => 'VESPERTINO'], ['name' => 'Vespertino']);
});

test('unauthorized users cannot access school dashboard or cycles', function () {
    $user = User::factory()->create();
    $school = School::create(['name' => 'Secundaria Privada', 'code' => 'SEC-PRIV']);

    $this->actingAs($user)->get(route('school.dashboard', $school))->assertNotFound();
    $this->actingAs($user)->get(route('school.years.index', $school))->assertNotFound();
});

test('school admin can view school dashboard', function () {
    $user = User::factory()->create();
    $school = School::create(['name' => 'Secundaria No. 1', 'code' => 'SEC-01']);

    $membership = SchoolMembership::create([
        'school_id' => $school->id,
        'user_id' => $user->id,
    ]);

    SchoolMembershipRole::create([
        'school_membership_id' => $membership->id,
        'role' => SchoolRole::ADMIN_ESCOLAR->value,
    ]);

    $response = $this->actingAs($user)->get(route('school.dashboard', $school));

    $response->assertOk();
    $response->assertSee('Secundaria No. 1');
    $response->assertSee('SEC-01');
});

test('school admin can create a new school year', function () {
    $user = User::factory()->create();
    $school = School::create(['name' => 'Secundaria No. 2', 'code' => 'SEC-02']);

    $membership = SchoolMembership::create([
        'school_id' => $school->id,
        'user_id' => $user->id,
    ]);

    SchoolMembershipRole::create([
        'school_membership_id' => $membership->id,
        'role' => SchoolRole::ADMIN_ESCOLAR->value,
    ]);

    Livewire::actingAs($user)
        ->test(SchoolYearsIndex::class, ['school' => $school])
        ->set('createYearName', '2026-2027')
        ->set('createStartsOn', '2026-08-24')
        ->set('createEndsOn', '2027-07-09')
        ->call('createSchoolYear')
        ->assertHasNoErrors();

    expect(SchoolYear::where('school_id', $school->id)->where('name', '2026-2027')->exists())->toBeTrue();
});

test('school year creation validates dates and uniqueness within school', function () {
    $admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);
    $school = School::create(['name' => 'Secundaria No. 3', 'code' => 'SEC-03']);

    SchoolYear::create([
        'school_id' => $school->id,
        'name' => '2025-2026',
        'starts_on' => '2025-08-25',
        'ends_on' => '2026-07-10',
    ]);

    Livewire::actingAs($admin)
        ->test(SchoolYearsIndex::class, ['school' => $school])
        ->set('createYearName', '2025-2026') // duplicate name in same school
        ->set('createStartsOn', '2026-08-24')
        ->set('createEndsOn', '2026-05-01') // end before start
        ->call('createSchoolYear')
        ->assertHasErrors(['createYearName', 'createEndsOn']);
});

test('school admin can create individual group', function () {
    $admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);
    $school = School::create(['name' => 'Secundaria No. 4', 'code' => 'SEC-04']);
    $shift = Shift::where('code', 'MATUTINO')->firstOrFail();

    $year = SchoolYear::create([
        'school_id' => $school->id,
        'name' => '2026-2027',
        'starts_on' => '2026-08-24',
        'ends_on' => '2027-07-09',
    ]);

    Livewire::actingAs($admin)
        ->test(SchoolYearsIndex::class, ['school' => $school])
        ->set('selectedYearId', $year->id)
        ->set('createGroupShiftId', $shift->id)
        ->set('createGroupGrade', 3)
        ->set('createGroupSection', 'A')
        ->call('createGroup')
        ->assertHasNoErrors();

    expect(SchoolGroup::where('school_year_id', $year->id)
        ->where('grade', 3)
        ->where('section', 'A')
        ->where('shift_id', $shift->id)
        ->exists())->toBeTrue();
});

test('validation prevents duplicate group in same cycle, grade, section and shift', function () {
    $admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);
    $school = School::create(['name' => 'Secundaria No. 5', 'code' => 'SEC-05']);
    $shift = Shift::where('code', 'MATUTINO')->firstOrFail();

    $year = SchoolYear::create([
        'school_id' => $school->id,
        'name' => '2026-2027',
        'starts_on' => '2026-08-24',
        'ends_on' => '2027-07-09',
    ]);

    SchoolGroup::create([
        'school_year_id' => $year->id,
        'shift_id' => $shift->id,
        'grade' => 1,
        'section' => 'A',
    ]);

    Livewire::actingAs($admin)
        ->test(SchoolYearsIndex::class, ['school' => $school])
        ->set('selectedYearId', $year->id)
        ->set('createGroupShiftId', $shift->id)
        ->set('createGroupGrade', 1)
        ->set('createGroupSection', 'A')
        ->call('createGroup')
        ->assertHasErrors(['createGroupSection']);
});

test('batch generation creates standard groups across 1st, 2nd, and 3rd grades', function () {
    $admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);
    $school = School::create(['name' => 'Secundaria No. 6', 'code' => 'SEC-06']);
    $shift = Shift::where('code', 'MATUTINO')->firstOrFail();

    $year = SchoolYear::create([
        'school_id' => $school->id,
        'name' => '2026-2027',
        'starts_on' => '2026-08-24',
        'ends_on' => '2027-07-09',
    ]);

    Livewire::actingAs($admin)
        ->test(SchoolYearsIndex::class, ['school' => $school])
        ->set('selectedYearId', $year->id)
        ->set('batchShiftId', $shift->id)
        ->set('batchMaxSection', 'B') // generates 1A, 1B, 2A, 2B, 3A, 3B = 6 groups
        ->call('generateStandardGroups')
        ->assertHasNoErrors();

    expect(SchoolGroup::where('school_year_id', $year->id)->count())->toBe(6);
});

test('school admin can delete a group', function () {
    $admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);
    $school = School::create(['name' => 'Secundaria No. 7', 'code' => 'SEC-07']);
    $shift = Shift::where('code', 'MATUTINO')->firstOrFail();

    $year = SchoolYear::create([
        'school_id' => $school->id,
        'name' => '2026-2027',
        'starts_on' => '2026-08-24',
        'ends_on' => '2027-07-09',
    ]);

    $group = SchoolGroup::create([
        'school_year_id' => $year->id,
        'shift_id' => $shift->id,
        'grade' => 2,
        'section' => 'C',
    ]);

    Livewire::actingAs($admin)
        ->test(SchoolYearsIndex::class, ['school' => $school])
        ->call('openDeleteGroupModal', $group->id)
        ->call('deleteGroup')
        ->assertHasNoErrors();

    expect(SchoolGroup::find($group->id))->toBeNull();
});
