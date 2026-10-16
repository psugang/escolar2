<?php

use App\Enums\SchoolRole;
use App\Livewire\School\Attendance\DailyAttendance;
use App\Livewire\School\Students\Index;
use App\Models\ClassSession;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolMembership;
use App\Models\SchoolMembershipRole;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSchool;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    app('router')->get('/test-school/{school}', fn (School $school) => response()->json(['school' => $school->id]))
        ->middleware(['web', 'auth', 'school.access']);

    app('router')->get('/test-school-role/{school}', fn (School $school) => response()->json(['status' => 'authorized']))
        ->middleware(['web', 'auth', 'school.role:DIRECTOR,ADMIN_ESCOLAR']);
});

test('user without school membership cannot access school routes', function () {
    $user = User::factory()->create();
    $school = School::create(['name' => 'Escuela A', 'code' => 'ESC-A']);

    $response = $this->actingAs($user)->get("/test-school/{$school->id}");

    $response->assertNotFound();
});

test('user with school membership can access school routes', function () {
    $user = User::factory()->create();
    $school = School::create(['name' => 'Escuela A', 'code' => 'ESC-A']);

    SchoolMembership::create([
        'school_id' => $school->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get("/test-school/{$school->id}");

    $response->assertOk();
    $response->assertJson(['school' => $school->id]);
});

test('global admin can access any school without membership', function () {
    $admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);
    $school = School::create(['name' => 'Escuela B', 'code' => 'ESC-B']);

    $response = $this->actingAs($admin)->get("/test-school/{$school->id}");

    $response->assertOk();
});

test('school member with correct role can access role-protected route', function () {
    $user = User::factory()->create();
    $school = School::create(['name' => 'Escuela C', 'code' => 'ESC-C']);

    $membership = SchoolMembership::create([
        'school_id' => $school->id,
        'user_id' => $user->id,
    ]);

    SchoolMembershipRole::create([
        'school_membership_id' => $membership->id,
        'role' => SchoolRole::DIRECTOR->value,
    ]);

    $response = $this->actingAs($user)->get("/test-school-role/{$school->id}");

    $response->assertOk();
});

test('school member without required role is forbidden', function () {
    $user = User::factory()->create();
    $school = School::create(['name' => 'Escuela D', 'code' => 'ESC-D']);

    $membership = SchoolMembership::create([
        'school_id' => $school->id,
        'user_id' => $user->id,
    ]);

    SchoolMembershipRole::create([
        'school_membership_id' => $membership->id,
        'role' => SchoolRole::PROFESOR->value,
    ]);

    $response = $this->actingAs($user)->get("/test-school-role/{$school->id}");

    $response->assertForbidden();
});

test('user from School A cannot manipulate student from School B via Livewire actions (IDOR prevention)', function () {
    $userA = User::factory()->create();
    $schoolA = School::create(['name' => 'Plantel Alpha', 'code' => 'ALPHA-01']);
    $membershipA = SchoolMembership::create(['school_id' => $schoolA->id, 'user_id' => $userA->id]);
    SchoolMembershipRole::create(['school_membership_id' => $membershipA->id, 'role' => SchoolRole::ADMIN_ESCOLAR->value]);

    $schoolB = School::create(['name' => 'Plantel Beta', 'code' => 'BETA-02']);
    $studentB = Student::create([
        'school_id' => $schoolB->id,
        'first_name' => 'Carlos',
        'last_name' => 'Vulnerabilidad',
        'status' => 'ACTIVO',
    ]);

    // Attempting to open baja modal or process baja for School B student while in School A context must return 404 Not Found
    Livewire::actingAs($userA)
        ->test(Index::class, ['school' => $schoolA])
        ->call('openBajaModal', $studentB->id)
        ->assertStatus(404);

    // Verify student B status remains unchanged
    expect($studentB->fresh()->status)->toBe('ACTIVO');
});

test('user from School A cannot delete class from School B via Livewire (IDOR prevention)', function () {
    $userA = User::factory()->create();
    $schoolA = School::create(['name' => 'Plantel A1', 'code' => 'A1-01']);
    $membershipA = SchoolMembership::create(['school_id' => $schoolA->id, 'user_id' => $userA->id]);
    SchoolMembershipRole::create(['school_membership_id' => $membershipA->id, 'role' => SchoolRole::ADMIN_ESCOLAR->value]);

    $schoolB = School::create(['name' => 'Plantel B2', 'code' => 'B2-02']);
    $yearB = SchoolYear::create(['school_id' => $schoolB->id, 'name' => '2026-2027', 'starts_on' => '2026-08-24', 'ends_on' => '2027-07-09']);
    $subjectB = Subject::create(['school_id' => $schoolB->id, 'name' => 'Química', 'code' => 'QUI-01']);
    $teacherB = Teacher::create(['first_name' => 'Alfonso', 'last_name' => 'Caso']);
    TeacherSchool::create(['teacher_id' => $teacherB->id, 'school_id' => $schoolB->id]);

    $classB = SchoolClass::create([
        'school_id' => $schoolB->id,
        'school_year_id' => $yearB->id,
        'subject_id' => $subjectB->id,
        'teacher_id' => $teacherB->id,
    ]);

    Livewire::actingAs($userA)
        ->test(App\Livewire\School\Classes\Index::class, ['school' => $schoolA])
        ->call('openDeleteClassModal', $classB->id)
        ->assertStatus(404);

    expect(SchoolClass::find($classB->id))->not->toBeNull();
});

test('user from School A cannot select session from School B in DailyAttendance (IDOR prevention)', function () {
    $userA = User::factory()->create();
    $schoolA = School::create(['name' => 'Plantel A3', 'code' => 'A3-01']);
    $membershipA = SchoolMembership::create(['school_id' => $schoolA->id, 'user_id' => $userA->id]);
    SchoolMembershipRole::create(['school_membership_id' => $membershipA->id, 'role' => SchoolRole::ADMIN_ESCOLAR->value]);

    $schoolB = School::create(['name' => 'Plantel B3', 'code' => 'B3-02']);
    $yearB = SchoolYear::create(['school_id' => $schoolB->id, 'name' => '2026-2027', 'starts_on' => '2026-08-24', 'ends_on' => '2027-07-09']);
    $subjectB = Subject::create(['school_id' => $schoolB->id, 'name' => 'Física', 'code' => 'FIS-01']);
    $teacherB = Teacher::create(['first_name' => 'Manuel', 'last_name' => 'Peimbert']);
    TeacherSchool::create(['teacher_id' => $teacherB->id, 'school_id' => $schoolB->id]);

    $classB = SchoolClass::create([
        'school_id' => $schoolB->id,
        'school_year_id' => $yearB->id,
        'subject_id' => $subjectB->id,
        'teacher_id' => $teacherB->id,
    ]);

    $sessionB = ClassSession::create([
        'school_class_id' => $classB->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '08:50',
        'original_teacher_id' => $teacherB->id,
    ]);

    Livewire::actingAs($userA)
        ->test(DailyAttendance::class, ['school' => $schoolA])
        ->call('selectSession', $sessionB->id)
        ->assertStatus(404);
});

test('user with exclusive PROFESOR role receives 403 Forbidden when accessing administrative URLs directly', function () {
    $teacherUser = User::factory()->create();
    $school = School::create(['name' => 'Plantel Docente', 'code' => 'DOC-01']);

    $membership = SchoolMembership::create([
        'school_id' => $school->id,
        'user_id' => $teacherUser->id,
    ]);

    SchoolMembershipRole::create([
        'school_membership_id' => $membership->id,
        'role' => SchoolRole::PROFESOR->value,
    ]);

    $this->actingAs($teacherUser)
        ->get(route('school.audit.index', $school))
        ->assertForbidden();

    $this->actingAs($teacherUser)
        ->get(route('school.teachers.index', $school))
        ->assertForbidden();

    $this->actingAs($teacherUser)
        ->get(route('school.years.index', $school))
        ->assertForbidden();

    $this->actingAs($teacherUser)
        ->get(route('school.students.index', $school))
        ->assertForbidden();

    $this->actingAs($teacherUser)
        ->get(route('school.classes.index', $school))
        ->assertForbidden();
});
