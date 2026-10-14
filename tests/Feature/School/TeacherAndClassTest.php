<?php

use App\Enums\SchoolRole;
use App\Livewire\School\Classes\Index as ClassesIndex;
use App\Livewire\School\Teachers\Index as TeachersIndex;
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
use Livewire\Livewire;

beforeEach(function () {
    $this->shift = Shift::updateOrCreate(['code' => 'MATUTINO'], ['name' => 'Matutino']);

    $this->admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);

    $this->schoolA = School::create(['name' => 'Secundaria Alfa', 'code' => 'SEC-ALFA']);
    $this->schoolB = School::create(['name' => 'Secundaria Beta', 'code' => 'SEC-BETA']);

    $this->yearA = SchoolYear::create([
        'school_id' => $this->schoolA->id,
        'name' => '2026-2027',
        'starts_on' => '2026-08-24',
        'ends_on' => '2027-07-09',
    ]);

    $this->group3A = SchoolGroup::create([
        'school_year_id' => $this->yearA->id,
        'shift_id' => $this->shift->id,
        'grade' => 3,
        'section' => 'A',
    ]);

    $this->group3B = SchoolGroup::create([
        'school_year_id' => $this->yearA->id,
        'shift_id' => $this->shift->id,
        'grade' => 3,
        'section' => 'B',
    ]);
});

test('unauthorized users cannot access teacher or class management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('school.teachers.index', $this->schoolA))->assertNotFound();
    $this->actingAs($user)->get(route('school.classes.index', $this->schoolA))->assertNotFound();
});

test('school admin can register a new teacher with user account', function () {
    Livewire::actingAs($this->admin)
        ->test(TeachersIndex::class, ['school' => $this->schoolA])
        ->set('newFirstName', 'Octavio')
        ->set('newLastName', 'Paz Lozano')
        ->set('newEmployeeNumber', 'PROF-101')
        ->set('newEmail', 'octavio.paz@secundaria.edu')
        ->set('createUserAccount', true)
        ->set('newPassword', 'PasswordSegura2026!')
        ->call('createTeacher')
        ->assertHasNoErrors();

    $teacher = Teacher::where('employee_number', 'PROF-101')->first();
    expect($teacher)->not->toBeNull()
        ->and($teacher->full_name)->toBe('Octavio Paz Lozano')
        ->and($teacher->schools()->where('schools.id', $this->schoolA->id)->exists())->toBeTrue()
        ->and($teacher->user_id)->not->toBeNull();

    $user = User::find($teacher->user_id);
    expect($user->hasSchoolRole($this->schoolA, SchoolRole::PROFESOR))->toBeTrue();
});

test('multi-school teacher linking allows a teacher in school A to teach in school B', function () {
    $teacher = Teacher::create([
        'first_name' => 'Mario',
        'last_name' => 'Molina',
        'employee_number' => 'PROF-QUIM',
    ]);

    TeacherSchool::create([
        'teacher_id' => $teacher->id,
        'school_id' => $this->schoolA->id,
    ]);

    expect($teacher->schools()->count())->toBe(1);

    // Now link to school B using the Livewire component in school B
    Livewire::actingAs($this->admin)
        ->test(TeachersIndex::class, ['school' => $this->schoolB])
        ->set('teacherMode', 'existing')
        ->set('existingTeacherId', $teacher->id)
        ->call('createTeacher')
        ->assertHasNoErrors();

    expect($teacher->fresh()->schools()->count())->toBe(2);
    expect($teacher->schools()->where('schools.id', $this->schoolB->id)->exists())->toBeTrue();
});

test('school admin can create subject and assign regular class to a group', function () {
    $teacher = Teacher::create([
        'first_name' => 'Sor Juana',
        'last_name' => 'Inés de la Cruz',
    ]);

    TeacherSchool::create([
        'teacher_id' => $teacher->id,
        'school_id' => $this->schoolA->id,
    ]);

    // Create Subject
    Livewire::actingAs($this->admin)
        ->test(ClassesIndex::class, ['school' => $this->schoolA])
        ->set('subjectName', 'Español y Literatura')
        ->set('subjectCode', 'ESP-3')
        ->call('saveSubject')
        ->assertHasNoErrors();

    $subject = Subject::where('school_id', $this->schoolA->id)->where('name', 'Español y Literatura')->first();
    expect($subject)->not->toBeNull();

    // Create Normal Class
    Livewire::actingAs($this->admin)
        ->test(ClassesIndex::class, ['school' => $this->schoolA])
        ->set('filterYearId', $this->yearA->id)
        ->set('classSubjectId', $subject->id)
        ->set('classTeacherId', $teacher->id)
        ->set('classGroupId', $this->group3A->id)
        ->call('createNormalClass')
        ->assertHasNoErrors();

    $class = SchoolClass::where('school_id', $this->schoolA->id)
        ->where('subject_id', $subject->id)
        ->where('school_group_id', $this->group3A->id)
        ->first();

    expect($class)->not->toBeNull()
        ->and($class->is_workshop)->toBeFalse()
        ->and($class->display_name)->toContain('Español y Literatura');
});

test('school admin can create workshop and enroll students from multiple groups', function () {
    $teacher = Teacher::create([
        'first_name' => 'Guillermo',
        'last_name' => 'González Camarena',
    ]);

    TeacherSchool::create([
        'teacher_id' => $teacher->id,
        'school_id' => $this->schoolA->id,
    ]);

    $subject = Subject::create([
        'school_id' => $this->schoolA->id,
        'name' => 'Tecnología e Informática',
    ]);

    // Create 2 students in different groups
    $student1 = Student::create(['school_id' => $this->schoolA->id, 'first_name' => 'Juan', 'last_name' => 'Pérez 3A']);
    StudentEnrollment::create([
        'student_id' => $student1->id,
        'school_year_id' => $this->yearA->id,
        'school_group_id' => $this->group3A->id,
        'shift_id' => $this->shift->id,
        'grade' => 3,
        'starts_on' => '2026-08-24',
    ]);

    $student2 = Student::create(['school_id' => $this->schoolA->id, 'first_name' => 'Ana', 'last_name' => 'López 3B']);
    StudentEnrollment::create([
        'student_id' => $student2->id,
        'school_year_id' => $this->yearA->id,
        'school_group_id' => $this->group3B->id,
        'shift_id' => $this->shift->id,
        'grade' => 3,
        'starts_on' => '2026-08-24',
    ]);

    // Create Workshop
    Livewire::actingAs($this->admin)
        ->test(ClassesIndex::class, ['school' => $this->schoolA])
        ->set('filterYearId', $this->yearA->id)
        ->set('workshopName', 'Taller de Robótica')
        ->set('workshopSubjectId', $subject->id)
        ->set('workshopTeacherId', $teacher->id)
        ->call('createWorkshop')
        ->assertHasNoErrors();

    $workshop = SchoolClass::where('school_id', $this->schoolA->id)
        ->where('name', 'Taller de Robótica')
        ->first();

    expect($workshop)->not->toBeNull()
        ->and($workshop->is_workshop)->toBeTrue();

    // Enroll students from 3A and 3B into the same workshop
    $component = Livewire::actingAs($this->admin)
        ->test(ClassesIndex::class, ['school' => $this->schoolA])
        ->call('openWorkshopStudentsModal', $workshop->id)
        ->set('studentToEnrollId', $student1->id)
        ->call('enrollStudentInWorkshop')
        ->assertHasNoErrors()
        ->set('studentToEnrollId', $student2->id)
        ->call('enrollStudentInWorkshop')
        ->assertHasNoErrors();

    expect($workshop->workshopStudents()->count())->toBe(2);

    // Unenroll student 1
    $component->call('removeStudentFromWorkshop', $student1->id);
    expect($workshop->fresh()->workshopStudents()->count())->toBe(1);
});
