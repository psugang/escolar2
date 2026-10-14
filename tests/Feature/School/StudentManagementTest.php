<?php

use App\Livewire\School\Students\Index as StudentsIndex;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolGroup;
use App\Models\SchoolYear;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->shiftMatutino = Shift::updateOrCreate(['code' => 'MATUTINO'], ['name' => 'Matutino']);
    $this->shiftVespertino = Shift::updateOrCreate(['code' => 'VESPERTINO'], ['name' => 'Vespertino']);

    $this->admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);

    $this->school = School::create(['name' => 'Secundaria Benito Juárez', 'code' => 'SEC-BJ']);

    $this->year = SchoolYear::create([
        'school_id' => $this->school->id,
        'name' => '2026-2027',
        'starts_on' => '2026-08-24',
        'ends_on' => '2027-07-09',
    ]);

    $this->group1A = SchoolGroup::create([
        'school_year_id' => $this->year->id,
        'shift_id' => $this->shiftMatutino->id,
        'grade' => 1,
        'section' => 'A',
    ]);

    $this->group1B = SchoolGroup::create([
        'school_year_id' => $this->year->id,
        'shift_id' => $this->shiftVespertino->id,
        'grade' => 1,
        'section' => 'B',
    ]);
});

test('unauthorized users cannot access student management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('school.students.index', $this->school))
        ->assertNotFound();
});

test('school admin can access students page and view students', function () {
    $student = Student::create([
        'school_id' => $this->school->id,
        'first_name' => 'Carlos',
        'last_name' => 'Santana López',
        'student_id_number' => 'MAT-001',
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $this->year->id,
        'school_group_id' => $this->group1A->id,
        'shift_id' => $this->shiftMatutino->id,
        'grade' => 1,
        'starts_on' => '2026-08-24',
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('school.students.index', $this->school));

    $response->assertOk();
    $response->assertSee('Carlos Santana López');
    $response->assertSee('MAT-001');
});

test('school admin can register a new student with initial enrollment', function () {
    Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->set('newFirstName', 'María José')
        ->set('newLastName', 'Hernández Ruiz')
        ->set('newStudentIdNumber', 'MAT-2026-100')
        ->set('newCurp', 'HERM090101MDFRZA01')
        ->set('newGroupId', $this->group1A->id)
        ->set('newStartsOn', '2026-08-24')
        ->call('createStudent')
        ->assertHasNoErrors();

    $student = Student::where('school_id', $this->school->id)
        ->where('student_id_number', 'MAT-2026-100')
        ->first();

    expect($student)->not->toBeNull()
        ->and($student->first_name)->toBe('María José')
        ->and($student->status)->toBe('ACTIVO');

    $enrollment = $student->currentEnrollment;
    expect($enrollment)->not->toBeNull()
        ->and($enrollment->school_group_id)->toBe($this->group1A->id)
        ->and($enrollment->status)->toBe('ACTIVO');
});

test('validation prevents duplicate student id number in same school', function () {
    Student::create([
        'school_id' => $this->school->id,
        'first_name' => 'Pedro',
        'last_name' => 'Ramírez',
        'student_id_number' => 'MAT-DUP',
    ]);

    Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->set('newFirstName', 'Nuevo')
        ->set('newLastName', 'Alumno')
        ->set('newStudentIdNumber', 'MAT-DUP')
        ->set('newGroupId', $this->group1A->id)
        ->set('newStartsOn', '2026-08-24')
        ->call('createStudent')
        ->assertHasErrors(['newStudentIdNumber']);
});

test('changing group closes previous enrollment and preserves history', function () {
    $student = Student::create([
        'school_id' => $this->school->id,
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
    ]);

    $firstEnrollment = StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $this->year->id,
        'school_group_id' => $this->group1A->id,
        'shift_id' => $this->shiftMatutino->id,
        'grade' => 1,
        'starts_on' => '2026-08-24',
        'status' => 'ACTIVO',
    ]);

    Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->call('openChangeGroupModal', $student->id)
        ->set('changeGroupId', $this->group1B->id)
        ->set('changeStartsOn', '2026-11-16')
        ->call('processChangeGroup')
        ->assertHasNoErrors();

    // Check previous enrollment is updated with CAMBIO_GRUPO and end date
    $firstEnrollment->refresh();
    expect($firstEnrollment->status)->toBe('CAMBIO_GRUPO')
        ->and($firstEnrollment->ends_on->format('Y-m-d'))->toBe('2026-11-16');

    // Check student has 2 total enrollments (historical preservation)
    expect($student->enrollments()->count())->toBe(2);

    // Check active enrollment is in group 1B
    $active = $student->fresh()->currentEnrollment;
    expect($active)->not->toBeNull()
        ->and($active->school_group_id)->toBe($this->group1B->id)
        ->and($active->starts_on->format('Y-m-d'))->toBe('2026-11-16')
        ->and($active->ends_on)->toBeNull()
        ->and($active->status)->toBe('ACTIVO');
});

test('student can have up to 2 guardians and cannot exceed maximum', function () {
    $student = Student::create([
        'school_id' => $this->school->id,
        'first_name' => 'Ana',
        'last_name' => 'García',
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->call('openGuardiansModal', $student->id);

    // Add first guardian
    $component
        ->set('guardianMode', 'new')
        ->set('guardianFirstName', 'Rosa')
        ->set('guardianLastName', 'García')
        ->set('guardianRelationship', 'Madre')
        ->set('guardianPhone', '3311112222')
        ->call('addGuardian')
        ->assertHasNoErrors();

    expect($student->guardians()->count())->toBe(1);

    // Add second guardian
    $component
        ->set('guardianMode', 'new')
        ->set('guardianFirstName', 'Jorge')
        ->set('guardianLastName', 'García')
        ->set('guardianRelationship', 'Padre')
        ->set('guardianPhone', '3322223333')
        ->call('addGuardian')
        ->assertHasNoErrors();

    expect($student->guardians()->count())->toBe(2);

    // Attempt to add a 3rd guardian - must fail
    $component
        ->set('guardianMode', 'new')
        ->set('guardianFirstName', 'Tercer')
        ->set('guardianLastName', 'Tutor')
        ->set('guardianRelationship', 'Abuela')
        ->call('addGuardian')
        ->assertHasErrors(['guardianRelationship']);

    expect($student->guardians()->count())->toBe(2);
});

test('guardian can be removed from student', function () {
    $student = Student::create([
        'school_id' => $this->school->id,
        'first_name' => 'Luis',
        'last_name' => 'Mendoza',
    ]);

    $guardian = Guardian::create([
        'school_id' => $this->school->id,
        'first_name' => 'Carmen',
        'last_name' => 'Mendoza',
    ]);

    StudentGuardian::create([
        'student_id' => $student->id,
        'guardian_id' => $guardian->id,
        'relationship' => 'Tía',
    ]);

    expect($student->guardians()->count())->toBe(1);

    Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->call('openGuardiansModal', $student->id)
        ->call('removeGuardian', $guardian->id)
        ->assertHasNoErrors();

    expect($student->fresh()->guardians()->count())->toBe(0);
});

test('marking student as baja closes enrollment and sets status to BAJA', function () {
    $student = Student::create([
        'school_id' => $this->school->id,
        'first_name' => 'Daniel',
        'last_name' => 'Flores',
    ]);

    $enrollment = StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $this->year->id,
        'school_group_id' => $this->group1A->id,
        'shift_id' => $this->shiftMatutino->id,
        'grade' => 1,
        'starts_on' => '2026-08-24',
        'status' => 'ACTIVO',
    ]);

    Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->call('openBajaModal', $student->id)
        ->call('processBaja')
        ->assertHasNoErrors();

    $student->refresh();
    $enrollment->refresh();

    expect($student->status)->toBe('BAJA')
        ->and($enrollment->status)->toBe('BAJA')
        ->and($enrollment->ends_on)->not->toBeNull();
});

test('can download sample csv template for student import', function () {
    Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->call('downloadSampleCsv')
        ->assertFileDownloaded('plantilla_alumnos.csv');
});

test('can import students quickly from csv file', function () {
    $csvContent = "grado,grupo,apellido_paterno,apellido_materno,nombre\n".
                  "1,A,García,López,Juan Carlos\n".
                  "2,B,Hernández,Martínez,Ana Sofía\n";

    $file = UploadedFile::fake()->createWithContent('alumnos.csv', $csvContent);

    Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->call('openImportModal')
        ->set('importYearId', $this->year->id)
        ->set('importShiftId', $this->shiftMatutino->id)
        ->set('importFile', $file)
        ->call('importCsv')
        ->assertHasNoErrors();

    $student1 = Student::where('first_name', 'Juan Carlos')->first();
    expect($student1)->not->toBeNull();
    expect($student1->last_name)->toBe('García López');
    expect($student1->school_id)->toBe($this->school->id);
    expect($student1->currentEnrollment)->not->toBeNull();
    expect($student1->currentEnrollment->grade)->toBe(1);

    $student2 = Student::where('first_name', 'Ana Sofía')->first();
    expect($student2)->not->toBeNull();
    expect($student2->last_name)->toBe('Hernández Martínez');
    expect($student2->currentEnrollment)->not->toBeNull();
    expect($student2->currentEnrollment->grade)->toBe(2);

    // Verify audit log
    $audit = AuditLog::where('school_id', $this->school->id)
        ->where('action', 'IMPORTACION_MASIVA_ALUMNOS')
        ->first();
    expect($audit)->not->toBeNull();
});
