<?php

use App\Enums\SchoolRole;
use App\Livewire\School\Attendance\DailyAttendance;
use App\Livewire\School\Attendance\Justifications;
use App\Models\Attendance;
use App\Models\AttendanceJustification;
use App\Models\AttendanceLog;
use App\Models\ClassSession;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolGroup;
use App\Models\SchoolYear;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSchool;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
    Storage::fake('public');

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
    Storage::disk('public')->assertExists($justification->document_path);

    // Can delete justification, leaving absence intact
    Livewire::actingAs($this->admin)
        ->test(Justifications::class, ['school' => $this->school])
        ->call('openDeleteModal', $justification->id)
        ->call('deleteJustification');

    expect(AttendanceJustification::find($justification->id))->toBeNull();
    expect($absence->fresh()->status)->toBe('AUSENTE');
});
