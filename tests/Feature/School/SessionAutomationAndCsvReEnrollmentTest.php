<?php

use App\Livewire\School\SchoolYears\Index as SchoolYearsIndex;
use App\Livewire\School\Students\Index as StudentsIndex;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolGroup;
use App\Models\SchoolSuspension;
use App\Models\SchoolYear;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGuardian;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ClassSessionGenerator;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->shift = Shift::updateOrCreate(['code' => 'MATUTINO'], ['name' => 'Matutino']);
    $this->admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);

    $this->school = School::create(['name' => 'Secundaria Técnica 10', 'code' => 'SEC-TEC-10']);

    $this->year2025 = SchoolYear::create([
        'school_id' => $this->school->id,
        'name' => '2025-2026',
        'starts_on' => '2025-08-25',
        'ends_on' => '2026-07-10',
    ]);

    $this->year2026 = SchoolYear::create([
        'school_id' => $this->school->id,
        'name' => '2026-2027',
        'starts_on' => '2026-08-24', // Monday
        'ends_on' => '2026-09-04',   // Friday (2 full weeks for test)
    ]);

    $this->group1A = SchoolGroup::create([
        'school_year_id' => $this->year2025->id,
        'shift_id' => $this->shift->id,
        'grade' => 1,
        'section' => 'A',
    ]);

    $this->group2A = SchoolGroup::create([
        'school_year_id' => $this->year2026->id,
        'shift_id' => $this->shift->id,
        'grade' => 2,
        'section' => 'A',
    ]);

    $this->subject = Subject::create([
        'school_id' => $this->school->id,
        'name' => 'Matemáticas',
        'code' => 'MAT-2',
        'grade' => 2,
    ]);

    $this->teacher = Teacher::create([
        'school_id' => $this->school->id,
        'first_name' => 'Mario',
        'last_name' => 'Molina',
        'email' => 'mario.molina@escuela.test',
    ]);

    $this->class = SchoolClass::create([
        'school_id' => $this->school->id,
        'school_year_id' => $this->year2026->id,
        'school_group_id' => $this->group2A->id,
        'subject_id' => $this->subject->id,
        'teacher_id' => $this->teacher->id,
        'status' => 'ACTIVA',
    ]);
});

test('class session generator creates sessions for scheduled days excluding weekends and suspensions', function () {
    // Class scheduled on Mondays (1) and Wednesdays (3)
    ClassSchedule::create([
        'school_class_id' => $this->class->id,
        'day_of_week' => 1, // Monday
        'start_time' => '08:00',
        'end_time' => '08:50',
        'effective_from' => '2026-08-24',
    ]);

    ClassSchedule::create([
        'school_class_id' => $this->class->id,
        'day_of_week' => 3, // Wednesday
        'start_time' => '08:00',
        'end_time' => '08:50',
        'effective_from' => '2026-08-24',
    ]);

    // Period: 2026-08-24 to 2026-09-04
    // Week 1: Mon 2026-08-24, Wed 2026-08-26
    // Week 2: Mon 2026-08-31, Wed 2026-09-02
    // Expected: 4 sessions

    // Register a suspension on Wednesday 2026-08-26
    SchoolSuspension::create([
        'school_id' => $this->school->id,
        'school_year_id' => $this->year2026->id,
        'date' => '2026-08-26',
        'reason' => 'Consejo Técnico Extraordinario',
    ]);

    $generator = new ClassSessionGenerator;
    $count = $generator->generateForClass($this->class);

    // 4 expected minus 1 suspended = 3
    expect($count)->toBe(3);
    expect($this->class->sessions()->count())->toBe(3);

    $dates = $this->class->sessions()->pluck('date')->map(fn ($d) => $d->format('Y-m-d'))->toArray();
    expect($dates)->toContain('2026-08-24');
    expect($dates)->not->toContain('2026-08-26'); // Suspended date excluded
    expect($dates)->toContain('2026-08-31');
    expect($dates)->toContain('2026-09-02');

    // Test idempotency: re-running does not duplicate sessions
    $countAgain = $generator->generateForClass($this->class);
    expect($countAgain)->toBe(0);
    expect($this->class->sessions()->count())->toBe(3);
});

test('registering a suspension deletes existing scheduled sessions without attendances', function () {
    // Create an active session on 2026-08-24
    $session = ClassSession::create([
        'school_class_id' => $this->class->id,
        'date' => '2026-08-24',
        'start_time' => '08:00',
        'end_time' => '08:50',
        'original_teacher_id' => $this->teacher->id,
        'status' => 'PROGRAMADA',
    ]);

    expect(ClassSession::where('id', $session->id)->exists())->toBeTrue();

    // School admin registers a suspension for 2026-08-24 via SchoolYears component
    Livewire::actingAs($this->admin)
        ->test(SchoolYearsIndex::class, ['school' => $this->school])
        ->call('openSuspensionModal', $this->year2026->id)
        ->set('newSuspensionDate', '2026-08-24')
        ->set('newSuspensionReason', 'Feriado Oficial')
        ->call('addSuspension')
        ->assertHasNoErrors();

    // Session must have been deleted automatically
    expect(ClassSession::where('id', $session->id)->exists())->toBeFalse();
});

test('csv importer detects re-enrollments, preserves student and guardians, and closes previous enrollment', function () {
    // 1. Create an existing student with previous year enrollment and guardian
    $existingStudent = Student::create([
        'school_id' => $this->school->id,
        'first_name' => 'Carlos',
        'last_name' => 'Ramírez Nava',
        'curp' => 'RANC080101HDFR01',
        'status' => 'ACTIVO',
    ]);

    $prevEnrollment = StudentEnrollment::create([
        'student_id' => $existingStudent->id,
        'school_year_id' => $this->year2025->id,
        'school_group_id' => $this->group1A->id,
        'shift_id' => $this->shift->id,
        'grade' => 1,
        'starts_on' => '2025-08-25',
        'status' => 'ACTIVO',
    ]);

    $guardian = Guardian::create([
        'school_id' => $this->school->id,
        'first_name' => 'Laura',
        'last_name' => 'Nava',
        'phone' => '5511223344',
    ]);

    StudentGuardian::create([
        'student_id' => $existingStudent->id,
        'guardian_id' => $guardian->id,
        'relationship' => 'Madre',
    ]);

    // 2. Prepare CSV containing the existing student (now advancing to 2°A) and a new student
    $csvContent = "grado,grupo,apellido_paterno,apellido_materno,nombre,curp\n".
                  "2,A,Ramírez,Nava,Carlos,RANC080101HDFR01\n".
                  "1,B,Torres,Vargas,Valeria,\n";

    $file = UploadedFile::fake()->createWithContent('alumnos_reingreso.csv', $csvContent);

    // 3. Test Preview Dry-Run
    $component = Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->call('openImportModal')
        ->set('importYearId', $this->year2026->id)
        ->set('importShiftId', $this->shift->id)
        ->set('importFile', $file)
        ->call('previewImportCsv')
        ->assertHasNoErrors()
        ->assertSet('importStep', 'preview');

    expect($component->get('previewReEnrollments'))->toHaveCount(1);
    expect($component->get('previewNewStudents'))->toHaveCount(1);

    // 4. Confirm Import
    $component->call('confirmImport')->assertHasNoErrors();

    // 5. Verify Student was not duplicated
    expect(Student::where('curp', 'RANC080101HDFR01')->count())->toBe(1);

    // 6. Verify previous enrollment closed with COMPLETADO
    $prevEnrollment->refresh();
    expect($prevEnrollment->status)->toBe('COMPLETADO');
    expect($prevEnrollment->ends_on)->not->toBeNull();

    // 7. Verify new enrollment created for year 2026 in group 2A
    $newEnrollment = $existingStudent->fresh()->currentEnrollment;
    expect($newEnrollment)->not->toBeNull();
    expect($newEnrollment->school_year_id)->toBe($this->year2026->id);
    expect($newEnrollment->grade)->toBe(2);
    expect($newEnrollment->schoolGroup->section)->toBe('A');

    // 8. Verify guardians remain attached
    expect($existingStudent->guardians()->count())->toBe(1);
    expect($existingStudent->guardians->first()->id)->toBe($guardian->id);

    // 9. Verify new student was created
    $newStudent = Student::where('first_name', 'Valeria')->first();
    expect($newStudent)->not->toBeNull();
    expect($newStudent->last_name)->toBe('Torres Vargas');
    expect($newStudent->currentEnrollment)->not->toBeNull();
    expect($newStudent->currentEnrollment->grade)->toBe(1);
    expect($newStudent->currentEnrollment->schoolGroup->section)->toBe('B');
});

test('csv importer links optional guardian columns without duplicating existing guardians', function () {
    $existingStudent = Student::create([
        'school_id' => $this->school->id,
        'first_name' => 'Diego',
        'last_name' => 'Luna',
        'curp' => 'LUND090101HDFR01',
        'status' => 'ACTIVO',
    ]);

    StudentEnrollment::create([
        'student_id' => $existingStudent->id,
        'school_year_id' => $this->year2025->id,
        'school_group_id' => $this->group1A->id,
        'shift_id' => $this->shift->id,
        'grade' => 1,
        'starts_on' => '2025-08-25',
        'status' => 'ACTIVO',
    ]);

    $existingGuardian = Guardian::create([
        'school_id' => $this->school->id,
        'first_name' => 'Alejandro',
        'last_name' => 'Luna',
        'email' => 'alejandro.luna@ejemplo.com',
    ]);

    StudentGuardian::create([
        'student_id' => $existingStudent->id,
        'guardian_id' => $existingGuardian->id,
        'relationship' => 'Padre',
    ]);

    $csvContent = "grado,grupo,apellido_paterno,apellido_materno,nombre,curp,tutor_nombre,tutor_correo,tutor_telefono\n".
                  "2,A,Luna,,Diego,LUND090101HDFR01,Alejandro Luna,alejandro.luna@ejemplo.com,5599887766\n".
                  "1,A,Salma,Hayek,Valentina,,Sami Hayek,sami.hayek@ejemplo.com,5544332211\n";

    $file = UploadedFile::fake()->createWithContent('alumnos_tutores.csv', $csvContent);

    Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->call('openImportModal')
        ->set('importYearId', $this->year2026->id)
        ->set('importShiftId', $this->shift->id)
        ->set('importFile', $file)
        ->call('previewImportCsv')
        ->assertHasNoErrors()
        ->call('confirmImport')
        ->assertHasNoErrors();

    // Existing student should still have 1 guardian (not duplicated), and missing phone updated
    expect($existingStudent->guardians()->count())->toBe(1);
    expect($existingGuardian->fresh()->phone)->toBe('5599887766');

    // New student should have the new guardian created and linked
    $newStudent = Student::where('first_name', 'Valentina')->first();
    expect($newStudent)->not->toBeNull();
    expect($newStudent->guardians()->count())->toBe(1);

    $newGuardian = $newStudent->guardians()->first();
    expect($newGuardian->full_name)->toBe('Sami Hayek');
    expect($newGuardian->email)->toBe('sami.hayek@ejemplo.com');
    expect($newGuardian->phone)->toBe('5544332211');
});
