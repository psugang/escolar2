<?php

use App\Enums\SchoolRole;
use App\Livewire\School\Attendance\DailyAttendance;
use App\Livewire\School\Audit\Index as AuditIndex;
use App\Livewire\School\Reports\Index as ReportsIndex;
use App\Livewire\School\Students\Index as StudentsIndex;
use App\Mail\StudentAbsenceNotificationMail;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\ClassSession;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolGroup;
use App\Models\SchoolYear;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGuardian;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSchool;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->shift = Shift::updateOrCreate(['code' => 'MATUTINO'], ['name' => 'Matutino']);

    $this->admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);

    $this->school = School::create(['name' => 'Secundaria Benito Juárez', 'code' => 'SEC-BJ']);

    $this->schoolYear = SchoolYear::create([
        'school_id' => $this->school->id,
        'name' => '2026-2027',
        'starts_on' => '2026-08-24',
        'ends_on' => '2027-07-09',
    ]);

    $this->groupA = SchoolGroup::create([
        'school_year_id' => $this->schoolYear->id,
        'shift_id' => $this->shift->id,
        'grade' => 2,
        'section' => 'A',
    ]);

    $this->groupB = SchoolGroup::create([
        'school_year_id' => $this->schoolYear->id,
        'shift_id' => $this->shift->id,
        'grade' => 2,
        'section' => 'B',
    ]);

    $this->subject = Subject::create([
        'school_id' => $this->school->id,
        'name' => 'Historia Universal',
        'code' => 'HIST-2',
    ]);

    $this->teacherUser = User::factory()->create(['name' => 'Prof. Ramón']);
    $this->teacher = Teacher::create([
        'first_name' => 'Ramón',
        'last_name' => 'López Velarde',
        'email' => 'ramon@example.com',
        'user_id' => $this->teacherUser->id,
    ]);
    TeacherSchool::create([
        'teacher_id' => $this->teacher->id,
        'school_id' => $this->school->id,
        'employee_number' => 'DOC-050',
    ]);
    $this->school->memberships()->create([
        'user_id' => $this->teacherUser->id,
        'role' => SchoolRole::PROFESOR->value,
    ]);

    $this->class = SchoolClass::create([
        'school_id' => $this->school->id,
        'school_year_id' => $this->schoolYear->id,
        'subject_id' => $this->subject->id,
        'teacher_id' => $this->teacher->id,
        'school_group_id' => $this->groupA->id,
        'is_workshop' => false,
    ]);

    // Student with Guardian
    $this->student = Student::create([
        'school_id' => $this->school->id,
        'first_name' => 'Gabriel',
        'last_name' => 'García Márquez',
        'student_id_number' => 'ALU-100',
        'status' => 'ACTIVO',
    ]);

    StudentEnrollment::create([
        'student_id' => $this->student->id,
        'school_year_id' => $this->schoolYear->id,
        'shift_id' => $this->shift->id,
        'school_group_id' => $this->groupA->id,
        'grade' => 2,
        'starts_on' => '2026-08-24',
        'status' => 'ACTIVO',
    ]);

    $this->guardian = Guardian::create([
        'school_id' => $this->school->id,
        'first_name' => 'Luisa',
        'last_name' => 'Santiaga Márquez',
        'email' => 'luisa.santiaga@example.com',
    ]);

    StudentGuardian::create([
        'student_id' => $this->student->id,
        'guardian_id' => $this->guardian->id,
        'relationship' => 'Madre',
    ]);
});

test('taking attendance with absent student sends email notification to guardian and records audit log', function () {
    Mail::fake();

    $session = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '07:00',
        'end_time' => '07:50',
        'status' => 'PROGRAMADA',
    ]);

    Livewire::actingAs($this->teacherUser)
        ->test(DailyAttendance::class, ['school' => $this->school])
        ->call('selectSession', $session->id)
        ->set("attendanceStates.{$this->student->id}", 'AUSENTE')
        ->set('sendGuardianNotifications', true)
        ->call('saveAttendance');

    Mail::assertQueued(StudentAbsenceNotificationMail::class, function ($mail) {
        return $mail->hasTo('luisa.santiaga@example.com') &&
               $mail->student->id === $this->student->id;
    });

    $noticeLog = AuditLog::where('school_id', $this->school->id)
        ->where('action', 'AVISO_AUSENCIA_ENVIADO')
        ->first();

    expect($noticeLog)->not->toBeNull();
    expect($noticeLog->description)->toContain('luisa.santiaga@example.com');
});

test('student actions generate appropriate audit logs', function () {
    // 1. Create student
    Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->set('newFirstName', 'Mario')
        ->set('newLastName', 'Benedetti Farrugia')
        ->set('newGroupId', $this->groupA->id)
        ->set('newStartsOn', '2026-08-24')
        ->call('createStudent');

    $student = Student::where('first_name', 'Mario')->first();
    expect($student)->not->toBeNull();

    $createLog = AuditLog::where('school_id', $this->school->id)
        ->where('action', 'ALUMNO_CREADO')
        ->where('auditable_id', $student->id)
        ->first();
    expect($createLog)->not->toBeNull();

    // 2. Change group
    Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->call('openChangeGroupModal', $student->id)
        ->set('changeGroupId', $this->groupB->id)
        ->set('changeStartsOn', today()->format('Y-m-d'))
        ->call('processChangeGroup');

    $changeLog = AuditLog::where('school_id', $this->school->id)
        ->where('action', 'CAMBIO_GRUPO')
        ->where('auditable_id', $student->id)
        ->first();
    expect($changeLog)->not->toBeNull();

    // 3. Baja
    Livewire::actingAs($this->admin)
        ->test(StudentsIndex::class, ['school' => $this->school])
        ->call('openBajaModal', $student->id)
        ->call('processBaja');

    $bajaLog = AuditLog::where('school_id', $this->school->id)
        ->where('action', 'ALUMNO_BAJA')
        ->where('auditable_id', $student->id)
        ->first();
    expect($bajaLog)->not->toBeNull();
});

test('audit log interface allows searching and viewing details', function () {
    AuditLog::record(
        action: 'TEST_ACTION',
        description: 'Evento de prueba para auditoría',
        school: $this->school,
        user: $this->admin,
        oldValues: ['status' => 'PENDING'],
        newValues: ['status' => 'APPROVED']
    );

    Livewire::actingAs($this->admin)
        ->test(AuditIndex::class, ['school' => $this->school])
        ->assertSee('TEST_ACTION')
        ->assertSee('Evento de prueba para auditoría')
        ->set('search', 'prueba')
        ->assertSee('TEST_ACTION')
        ->set('search', 'inexistente')
        ->assertDontSee('Evento de prueba para auditoría');
});

test('reports interface displays statistics and exports CSV', function () {
    $session = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '07:00',
        'end_time' => '07:50',
        'status' => 'COMPLETADA',
    ]);

    Attendance::create([
        'class_session_id' => $session->id,
        'student_id' => $this->student->id,
        'status' => 'PRESENTE',
        'recorded_by_user_id' => $this->teacherUser->id,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(ReportsIndex::class, ['school' => $this->school])
        ->assertSee('Gabriel García Márquez')
        ->assertSee('100%')
        ->set('reportType', 'GRUPOS_RESUMEN')
        ->assertSee("2° 'A'")
        ->set('reportType', 'PROFESORES_SESIONES')
        ->assertSee('Ramón López Velarde');

    // Test CSV export
    $component->call('exportCsv')
        ->assertFileDownloaded();
});
