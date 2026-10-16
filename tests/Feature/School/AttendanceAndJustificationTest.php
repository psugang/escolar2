<?php

use App\Enums\SchoolRole;
use App\Livewire\School\Attendance\DailyAttendance;
use App\Livewire\School\Attendance\Justifications;
use App\Mail\StudentAbsenceNotificationMail;
use App\Models\Attendance;
use App\Models\AttendanceJustification;
use App\Models\AttendanceLog;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->shift = Shift::updateOrCreate(['code' => 'MATUTINO'], ['name' => 'Matutino']);

    $this->admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);

    $this->school = School::create(['name' => 'Colegio Cuauhtémoc', 'code' => 'COL-CUAUH']);

    $this->schoolYear = SchoolYear::create([
        'school_id' => $this->school->id,
        'name' => '2026-2027',
        'starts_on' => '2026-08-24',
        'ends_on' => '2027-07-09',
    ]);

    $this->group = SchoolGroup::create([
        'school_year_id' => $this->schoolYear->id,
        'shift_id' => $this->shift->id,
        'grade' => 1,
        'section' => 'A',
    ]);

    $this->subject = Subject::create([
        'school_id' => $this->school->id,
        'name' => 'Matemáticas I',
        'code' => 'MAT-1',
    ]);

    // Teacher
    $this->teacherUser = User::factory()->create(['name' => 'Prof. Fernando']);
    $this->teacher = Teacher::create([
        'first_name' => 'Fernando',
        'last_name' => 'Valenzuela',
        'email' => 'fernando@example.com',
        'user_id' => $this->teacherUser->id,
    ]);
    TeacherSchool::create([
        'teacher_id' => $this->teacher->id,
        'school_id' => $this->school->id,
        'employee_number' => 'DOC-001',
    ]);
    $this->school->memberships()->create([
        'user_id' => $this->teacherUser->id,
        'role' => SchoolRole::PROFESOR->value,
    ]);

    // Class
    $this->class = SchoolClass::create([
        'school_id' => $this->school->id,
        'school_year_id' => $this->schoolYear->id,
        'subject_id' => $this->subject->id,
        'teacher_id' => $this->teacher->id,
        'school_group_id' => $this->group->id,
        'is_workshop' => false,
    ]);

    // Students
    $this->student1 = Student::create([
        'school_id' => $this->school->id,
        'first_name' => 'Ana',
        'last_name' => 'López',
        'curp' => 'LOPE010101HDFRRA01',
        'gender' => 'F',
        'birth_date' => '2012-05-10',
    ]);
    StudentEnrollment::create([
        'student_id' => $this->student1->id,
        'school_year_id' => $this->schoolYear->id,
        'shift_id' => $this->shift->id,
        'school_group_id' => $this->group->id,
        'grade' => 1,
        'status' => 'ACTIVO',
        'starts_on' => '2026-08-24',
    ]);

    $this->student2 = Student::create([
        'school_id' => $this->school->id,
        'first_name' => 'Carlos',
        'last_name' => 'Ramírez',
        'curp' => 'RAMC010101HDFRRA02',
        'gender' => 'M',
        'birth_date' => '2012-08-15',
    ]);
    StudentEnrollment::create([
        'student_id' => $this->student2->id,
        'school_year_id' => $this->schoolYear->id,
        'shift_id' => $this->shift->id,
        'school_group_id' => $this->group->id,
        'grade' => 1,
        'status' => 'ACTIVO',
        'starts_on' => '2026-08-24',
    ]);
});

test('unauthorized users cannot access daily attendance or justifications', function () {
    $randomUser = User::factory()->create();

    $this->actingAs($randomUser)->get(route('school.attendance.daily', $this->school))->assertNotFound();
    $this->actingAs($randomUser)->get(route('school.attendance.justifications', $this->school))->assertNotFound();
});

test('can view sessions and take daily attendance marking present, absent and retardo', function () {
    $session = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '08:50',
        'status' => 'PROGRAMADA',
    ]);

    Livewire::actingAs($this->teacherUser)
        ->test(DailyAttendance::class, ['school' => $this->school])
        ->call('selectSession', $session->id)
        ->assertSet('activeSessionId', $session->id)
        ->set("attendanceStates.{$this->student1->id}", 'PRESENTE')
        ->set("attendanceStates.{$this->student2->id}", 'AUSENTE')
        ->call('saveAttendance');

    expect($session->fresh()->status)->toBe('COMPLETADA');

    $att1 = Attendance::where('class_session_id', $session->id)->where('student_id', $this->student1->id)->first();
    expect($att1)->not->toBeNull();
    expect($att1->status)->toBe('PRESENTE');

    $att2 = Attendance::where('class_session_id', $session->id)->where('student_id', $this->student2->id)->first();
    expect($att2)->not->toBeNull();
    expect($att2->status)->toBe('AUSENTE');
});

test('cannot take attendance on a future date session', function () {
    $futureSession = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'date' => today()->addDays(5)->format('Y-m-d'),
        'start_time' => '09:00',
        'end_time' => '09:50',
        'status' => 'PROGRAMADA',
    ]);

    Livewire::actingAs($this->teacherUser)
        ->test(DailyAttendance::class, ['school' => $this->school])
        ->call('selectSession', $futureSession->id)
        ->assertSet('activeSessionId', null);

    expect($futureSession->fresh()->status)->toBe('PROGRAMADA');
    expect(Attendance::where('class_session_id', $futureSession->id)->count())->toBe(0);
});

test('modifying attendance records audit entry in attendance logs', function () {
    $session = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '08:50',
        'status' => 'PROGRAMADA',
    ]);

    $att = Attendance::create([
        'class_session_id' => $session->id,
        'student_id' => $this->student1->id,
        'status' => 'PRESENTE',
        'recorded_by_user_id' => $this->teacherUser->id,
    ]);

    Livewire::actingAs($this->teacherUser)
        ->test(DailyAttendance::class, ['school' => $this->school])
        ->call('selectSession', $session->id)
        ->set("attendanceStates.{$this->student1->id}", 'RETARDO')
        ->call('saveAttendance');

    expect($att->fresh()->status)->toBe('RETARDO');

    $log = AttendanceLog::where('attendance_id', $att->id)->first();
    expect($log)->not->toBeNull();
    expect($log->old_status)->toBe('PRESENTE');
    expect($log->new_status)->toBe('RETARDO');
    expect($log->changed_by_user_id)->toBe($this->teacherUser->id);
});

test('substitute teacher can take attendance for sessions where assigned', function () {
    $substituteUser = User::factory()->create(['name' => 'Prof. Reemplazo']);
    $substituteTeacher = Teacher::create([
        'first_name' => 'Elena',
        'last_name' => 'Garro',
        'email' => 'elena@example.com',
        'user_id' => $substituteUser->id,
    ]);
    TeacherSchool::create([
        'teacher_id' => $substituteTeacher->id,
        'school_id' => $this->school->id,
        'employee_number' => 'DOC-002',
    ]);
    $this->school->memberships()->create([
        'user_id' => $substituteUser->id,
        'role' => SchoolRole::PROFESOR->value,
    ]);

    $session = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'substitute_teacher_id' => $substituteTeacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '10:00',
        'end_time' => '10:50',
        'status' => 'PROGRAMADA',
    ]);

    Livewire::actingAs($substituteUser)
        ->test(DailyAttendance::class, ['school' => $this->school])
        ->call('selectSession', $session->id)
        ->assertSet('activeSessionId', $session->id)
        ->set("attendanceStates.{$this->student1->id}", 'PRESENTE')
        ->set("attendanceStates.{$this->student2->id}", 'PRESENTE')
        ->call('saveAttendance');

    expect($session->fresh()->status)->toBe('COMPLETADA');
    expect(Attendance::where('class_session_id', $session->id)->count())->toBe(2);
});

test('school admin can upload justification for an absent student preserving the absence record', function () {
    Storage::fake('justifications');

    $session = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '08:50',
        'status' => 'COMPLETADA',
    ]);

    $absence = Attendance::create([
        'class_session_id' => $session->id,
        'student_id' => $this->student2->id,
        'status' => 'AUSENTE',
        'recorded_by_user_id' => $this->teacherUser->id,
    ]);

    $file = UploadedFile::fake()->create('certificado_medico.pdf', 500, 'application/pdf');

    Livewire::actingAs($this->admin)
        ->test(Justifications::class, ['school' => $this->school])
        ->call('openJustifyModal', $absence->id)
        ->set('reason', 'Cita médica con especialista por bronquitis')
        ->set('document', $file)
        ->call('saveJustification');

    // Verify absence record is preserved and not deleted or altered
    expect($absence->fresh()->status)->toBe('AUSENTE');

    $justification = AttendanceJustification::where('attendance_id', $absence->id)->first();
    expect($justification)->not->toBeNull();
    expect($justification->reason)->toBe('Cita médica con especialista por bronquitis');
    expect($justification->document_name)->toBe('certificado_medico.pdf');
    expect($justification->document_path)->not->toBeNull();
    Storage::disk('justifications')->assertExists($justification->document_path);

    // Can delete justification, leaving absence intact and deleting file
    Livewire::actingAs($this->admin)
        ->test(Justifications::class, ['school' => $this->school])
        ->call('openDeleteModal', $justification->id)
        ->call('deleteJustification');

    expect(AttendanceJustification::find($justification->id))->toBeNull();
    expect($absence->fresh()->status)->toBe('AUSENTE');
    Storage::disk('justifications')->assertMissing($justification->document_path);
});

test('saving attendance queues absence mail and avoids duplicate notifications via absence_notified_at', function () {
    Mail::fake();

    $guardian = Guardian::create([
        'school_id' => $this->school->id,
        'first_name' => 'Roberto',
        'last_name' => 'López',
        'email' => 'roberto.tutor@example.com',
    ]);

    StudentGuardian::create([
        'student_id' => $this->student1->id,
        'guardian_id' => $guardian->id,
        'relationship' => 'Padre',
    ]);

    $session = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '09:00',
        'end_time' => '09:50',
        'status' => 'PROGRAMADA',
    ]);

    // Save attendance with student 1 AUSENTE
    Livewire::actingAs($this->teacherUser)
        ->test(DailyAttendance::class, ['school' => $this->school])
        ->call('selectSession', $session->id)
        ->set('attendanceStates.'.$this->student1->id, 'AUSENTE')
        ->set('attendanceStates.'.$this->student2->id, 'PRESENTE')
        ->set('sendGuardianNotifications', true)
        ->call('saveAttendance')
        ->assertHasNoErrors();

    // Verify mail was queued
    Mail::assertQueued(StudentAbsenceNotificationMail::class, function ($mail) {
        return $mail->hasTo('roberto.tutor@example.com');
    });

    // Verify absence_notified_at is populated
    $attendance = Attendance::where('class_session_id', $session->id)
        ->where('student_id', $this->student1->id)
        ->first();
    expect($attendance)->not->toBeNull();
    expect($attendance->absence_notified_at)->not->toBeNull();

    // Now edit the session again: no new emails should be queued for student 1
    Mail::fake();

    Livewire::actingAs($this->teacherUser)
        ->test(DailyAttendance::class, ['school' => $this->school])
        ->call('selectSession', $session->id)
        ->set('attendanceStates.'.$this->student1->id, 'AUSENTE')
        ->set('attendanceStates.'.$this->student2->id, 'PRESENTE')
        ->set('sendGuardianNotifications', true)
        ->call('saveAttendance')
        ->assertHasNoErrors();

    Mail::assertNothingQueued();
});

test('uploading image justification converts it to webp and stores it in private justifications disk', function () {
    Storage::fake('justifications');

    $session = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '08:50',
        'status' => 'COMPLETADA',
    ]);

    $absence = Attendance::create([
        'class_session_id' => $session->id,
        'student_id' => $this->student2->id,
        'status' => 'AUSENTE',
        'recorded_by_user_id' => $this->teacherUser->id,
    ]);

    $file = UploadedFile::fake()->image('receta_medica.png', 400, 300);

    Livewire::actingAs($this->admin)
        ->test(Justifications::class, ['school' => $this->school])
        ->call('openJustifyModal', $absence->id)
        ->set('reason', 'Reposo médico')
        ->set('document', $file)
        ->call('saveJustification');

    $justification = AttendanceJustification::where('attendance_id', $absence->id)->first();
    expect($justification)->not->toBeNull();
    expect($justification->document_path)->toEndWith('.webp');
    Storage::disk('justifications')->assertExists($justification->document_path);
});

test('justification document download enforces school tenant isolation', function () {
    Storage::fake('justifications');

    $session = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '08:50',
        'status' => 'COMPLETADA',
    ]);

    $absence = Attendance::create([
        'class_session_id' => $session->id,
        'student_id' => $this->student2->id,
        'status' => 'AUSENTE',
        'recorded_by_user_id' => $this->teacherUser->id,
    ]);

    $file = UploadedFile::fake()->create('certificado.pdf', 100, 'application/pdf');

    Livewire::actingAs($this->admin)
        ->test(Justifications::class, ['school' => $this->school])
        ->call('openJustifyModal', $absence->id)
        ->set('reason', 'Justificante oficial')
        ->set('document', $file)
        ->call('saveJustification');

    $justification = AttendanceJustification::where('attendance_id', $absence->id)->first();

    // 1. Authorized school admin can download the document
    $this->actingAs($this->admin)
        ->get(route('school.justifications.document', [$this->school, $justification]))
        ->assertOk();

    // 2. User from another school receives 403 or 404
    $otherSchool = School::create(['name' => 'Escuela Secundaria 2', 'code' => 'SEC-002']);
    $otherUser = User::factory()->create();
    $otherSchool->memberships()->create([
        'user_id' => $otherUser->id,
        'role' => SchoolRole::DIRECTOR->value,
    ]);

    // Accessing this school's document directly by unauthorized user -> 404 (due to school access middleware)
    $this->actingAs($otherUser)
        ->get(route('school.justifications.document', [$this->school, $justification]))
        ->assertNotFound();

    // Accessing through other school URL attempting ID tampering -> 403
    $this->actingAs($otherUser)
        ->get(route('school.justifications.document', [$otherSchool, $justification]))
        ->assertForbidden();
});

test('teacher-only user only sees own or substituted sessions while admin sees all school sessions', function () {
    $otherTeacherUser = User::factory()->create(['name' => 'Otro Profesor']);
    $otherTeacher = Teacher::create([
        'first_name' => 'Octavio',
        'last_name' => 'Paz',
        'email' => 'octavio@example.com',
        'user_id' => $otherTeacherUser->id,
    ]);
    TeacherSchool::create([
        'teacher_id' => $otherTeacher->id,
        'school_id' => $this->school->id,
        'employee_number' => 'DOC-099',
    ]);
    $otherMembership = $this->school->memberships()->create([
        'user_id' => $otherTeacherUser->id,
        'role' => SchoolRole::PROFESOR->value,
    ]);
    $otherMembership->roles()->create(['role' => SchoolRole::PROFESOR->value]);

    // Ensure teacherUser also has SchoolMembershipRole record
    $teacherMembership = $this->school->memberships()->where('user_id', $this->teacherUser->id)->first();
    $teacherMembership?->roles()->firstOrCreate(['role' => SchoolRole::PROFESOR->value]);

    $otherSubject = Subject::create([
        'school_id' => $this->school->id,
        'name' => 'Literatura Universal',
        'code' => 'LIT-01',
    ]);

    $otherClass = SchoolClass::create([
        'school_id' => $this->school->id,
        'school_year_id' => $this->schoolYear->id,
        'subject_id' => $otherSubject->id,
        'teacher_id' => $otherTeacher->id,
        'school_group_id' => $this->group->id,
        'is_workshop' => false,
    ]);

    $ownSession = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '08:50',
        'status' => 'PROGRAMADA',
    ]);

    $otherSession = ClassSession::create([
        'school_class_id' => $otherClass->id,
        'original_teacher_id' => $otherTeacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '09:00',
        'end_time' => '09:50',
        'status' => 'PROGRAMADA',
    ]);

    // Teacher only sees their own session, not the other teacher's session
    Livewire::actingAs($this->teacherUser)
        ->test(DailyAttendance::class, ['school' => $this->school])
        ->assertSee($this->subject->name)
        ->assertDontSee('Literatura Universal')
        ->call('selectSession', $otherSession->id)
        ->assertStatus(403);

    // Admin sees both sessions
    Livewire::actingAs($this->admin)
        ->test(DailyAttendance::class, ['school' => $this->school])
        ->assertSee($this->subject->name)
        ->assertSee('Literatura Universal');
});

test('openConfirmModal saves attendance immediately when 0 students are absent and opens modal when at least 1 is absent', function () {
    $session = ClassSession::create([
        'school_class_id' => $this->class->id,
        'original_teacher_id' => $this->teacher->id,
        'date' => today()->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '08:50',
        'status' => 'PROGRAMADA',
    ]);

    // Case 1: All present or retardo (0 absent) -> saves directly without modal
    Livewire::actingAs($this->teacherUser)
        ->test(DailyAttendance::class, ['school' => $this->school])
        ->call('selectSession', $session->id)
        ->set("attendanceStates.{$this->student1->id}", 'PRESENTE')
        ->set("attendanceStates.{$this->student2->id}", 'RETARDO')
        ->call('openConfirmModal')
        ->assertSet('showConfirmModal', false)
        ->assertSet('activeSessionId', null);

    expect($session->fresh()->status)->toBe('COMPLETADA');

    // Case 2: At least 1 absent -> opens confirmation modal
    Livewire::actingAs($this->teacherUser)
        ->test(DailyAttendance::class, ['school' => $this->school])
        ->call('selectSession', $session->id)
        ->set("attendanceStates.{$this->student2->id}", 'AUSENTE')
        ->call('openConfirmModal')
        ->assertSet('showConfirmModal', true);
});
