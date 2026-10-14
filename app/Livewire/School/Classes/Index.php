<?php

namespace App\Livewire\School\Classes;

use App\Models\ClassEnrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolGroup;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Materias, Clases y Talleres')]
class Index extends Component
{
    use WithPagination;

    public School $school;

    public string $activeTab = 'classes'; // 'classes' | 'subjects'

    // Filters
    public string $search = '';

    public ?string $filterYearId = null;

    public string $filterType = 'all'; // 'all' | 'normal' | 'workshop'

    // Modal Create Subject
    public bool $showSubjectModal = false;

    public ?string $editingSubjectId = null;

    public string $subjectName = '';

    public string $subjectCode = '';

    // Modal Create Class Normal
    public bool $showCreateClassModal = false;

    public string $classSubjectId = '';

    public string $classTeacherId = '';

    public string $classGroupId = '';

    // Modal Create Workshop
    public bool $showCreateWorkshopModal = false;

    public string $workshopName = '';

    public string $workshopSubjectId = '';

    public string $workshopTeacherId = '';

    // Modal Workshop Students
    public bool $showWorkshopStudentsModal = false;

    public ?string $selectedWorkshopId = null;

    public ?SchoolClass $selectedWorkshop = null;

    public string $studentToEnrollId = '';

    // Modal Delete Class
    public bool $showDeleteClassModal = false;

    public ?string $deletingClassId = null;

    public string $deletingClassName = '';

    public function mount(School $school): void
    {
        $this->school = $school;

        $activeYear = $this->school->schoolYears()
            ->where('starts_on', '<=', today())
            ->where('ends_on', '>=', today())
            ->first()
            ?? $this->school->schoolYears()->latest('starts_on')->first();

        $this->filterYearId = $activeYear?->id;
    }

    public function openSubjectModal(?string $subjectId = null): void
    {
        $this->editingSubjectId = $subjectId;

        if ($subjectId) {
            $subj = Subject::where('school_id', $this->school->id)->findOrFail($subjectId);
            $this->subjectName = $subj->name;
            $this->subjectCode = $subj->code ?? '';
        } else {
            $this->reset(['subjectName', 'subjectCode']);
        }

        $this->resetValidation();
        $this->showSubjectModal = true;
    }

    public function saveSubject(): void
    {
        $this->validate([
            'subjectName' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subjects', 'name')
                    ->where('school_id', $this->school->id)
                    ->ignore($this->editingSubjectId),
            ],
            'subjectCode' => ['nullable', 'string', 'max:30'],
        ], [
            'subjectName.required' => 'El nombre de la materia es obligatorio.',
            'subjectName.unique' => 'Ya existe una materia con este nombre en este plantel.',
        ]);

        if ($this->editingSubjectId) {
            $subject = Subject::where('school_id', $this->school->id)->findOrFail($this->editingSubjectId);
            $subject->update([
                'name' => trim($this->subjectName),
                'code' => $this->subjectCode ? trim(strtoupper($this->subjectCode)) : null,
            ]);
            Flux::toast(variant: 'success', text: 'Materia actualizada.');
        } else {
            Subject::create([
                'school_id' => $this->school->id,
                'name' => trim($this->subjectName),
                'code' => $this->subjectCode ? trim(strtoupper($this->subjectCode)) : null,
            ]);
            Flux::toast(variant: 'success', text: 'Materia registrada.');
        }

        $this->showSubjectModal = false;
        $this->reset(['editingSubjectId', 'subjectName', 'subjectCode']);
    }

    public function openCreateClassModal(): void
    {
        if (! $this->filterYearId) {
            Flux::toast(variant: 'danger', text: 'Selecciona primero un ciclo escolar.');

            return;
        }

        $this->reset(['classSubjectId', 'classTeacherId', 'classGroupId']);
        $this->resetValidation();
        $this->showCreateClassModal = true;
    }

    public function createNormalClass(): void
    {
        $this->validate([
            'filterYearId' => ['required', 'exists:school_years,id'],
            'classSubjectId' => ['required', 'exists:subjects,id'],
            'classTeacherId' => ['required', 'exists:teachers,id'],
            'classGroupId' => ['required', 'exists:school_groups,id'],
        ], [
            'classSubjectId.required' => 'Debes seleccionar una materia.',
            'classTeacherId.required' => 'Debes asignar un profesor.',
            'classGroupId.required' => 'Debes seleccionar el grupo.',
        ]);

        $exists = SchoolClass::where('school_id', $this->school->id)
            ->where('school_year_id', $this->filterYearId)
            ->where('subject_id', $this->classSubjectId)
            ->where('school_group_id', $this->classGroupId)
            ->exists();

        if ($exists) {
            $this->addError('classGroupId', 'Ya existe una clase de esta materia para este grupo en este ciclo.');

            return;
        }

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'school_year_id' => $this->filterYearId,
            'subject_id' => $this->classSubjectId,
            'teacher_id' => $this->classTeacherId,
            'school_group_id' => $this->classGroupId,
            'is_workshop' => false,
        ]);

        $this->showCreateClassModal = false;
        Flux::toast(variant: 'success', text: "Clase {$class->display_name} creada correctamente.");
    }

    public function openCreateWorkshopModal(): void
    {
        if (! $this->filterYearId) {
            Flux::toast(variant: 'danger', text: 'Selecciona primero un ciclo escolar.');

            return;
        }

        $this->reset(['workshopName', 'workshopSubjectId', 'workshopTeacherId']);
        $this->resetValidation();
        $this->showCreateWorkshopModal = true;
    }

    public function createWorkshop(): void
    {
        $this->validate([
            'filterYearId' => ['required', 'exists:school_years,id'],
            'workshopName' => ['required', 'string', 'max:150'],
            'workshopSubjectId' => ['required', 'exists:subjects,id'],
            'workshopTeacherId' => ['required', 'exists:teachers,id'],
        ], [
            'workshopName.required' => 'El nombre del taller es obligatorio.',
            'workshopSubjectId.required' => 'Debes seleccionar la materia base.',
            'workshopTeacherId.required' => 'Debes asignar un profesor.',
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'school_year_id' => $this->filterYearId,
            'subject_id' => $this->workshopSubjectId,
            'teacher_id' => $this->workshopTeacherId,
            'school_group_id' => null,
            'is_workshop' => true,
            'name' => trim($this->workshopName),
        ]);

        $this->showCreateWorkshopModal = false;
        Flux::toast(variant: 'success', text: "Taller {$class->name} registrado exitosamente.");
    }

    public function openWorkshopStudentsModal(string $classId): void
    {
        $this->selectedWorkshopId = $classId;
        $this->selectedWorkshop = SchoolClass::with([
            'workshopStudents.currentEnrollment.schoolGroup',
        ])->findOrFail($classId);

        $this->studentToEnrollId = '';
        $this->resetValidation();
        $this->showWorkshopStudentsModal = true;
    }

    public function enrollStudentInWorkshop(): void
    {
        $this->validate([
            'studentToEnrollId' => ['required', 'exists:students,id'],
        ]);

        $workshop = SchoolClass::findOrFail($this->selectedWorkshopId);

        $alreadyEnrolled = ClassEnrollment::where('school_class_id', $workshop->id)
            ->where('student_id', $this->studentToEnrollId)
            ->exists();

        if ($alreadyEnrolled) {
            $this->addError('studentToEnrollId', 'Este alumno ya está inscrito en este taller.');

            return;
        }

        ClassEnrollment::create([
            'school_class_id' => $workshop->id,
            'student_id' => $this->studentToEnrollId,
        ]);

        $this->selectedWorkshop = $workshop->fresh(['workshopStudents.currentEnrollment.schoolGroup']);
        $this->reset('studentToEnrollId');

        Flux::toast(variant: 'success', text: 'Alumno inscrito al taller.');
    }

    public function removeStudentFromWorkshop(string $studentId): void
    {
        ClassEnrollment::where('school_class_id', $this->selectedWorkshopId)
            ->where('student_id', $studentId)
            ->delete();

        $this->selectedWorkshop = SchoolClass::with([
            'workshopStudents.currentEnrollment.schoolGroup',
        ])->findOrFail($this->selectedWorkshopId);

        Flux::toast(variant: 'success', text: 'Alumno desinscrito del taller.');
    }

    public function openDeleteClassModal(string $classId): void
    {
        $class = SchoolClass::findOrFail($classId);
        $this->deletingClassId = $class->id;
        $this->deletingClassName = $class->display_name;
        $this->showDeleteClassModal = true;
    }

    public function deleteClass(): void
    {
        $class = SchoolClass::findOrFail($this->deletingClassId);
        $class->delete();

        $this->showDeleteClassModal = false;
        $this->reset(['deletingClassId', 'deletingClassName']);

        Flux::toast(variant: 'success', text: 'Clase/taller eliminada.');
    }

    public function render(): View
    {
        $searchTerm = strtolower(trim($this->search));

        $classes = SchoolClass::query()
            ->where('school_id', $this->school->id)
            ->when($this->filterYearId, fn ($q) => $q->where('school_year_id', $this->filterYearId))
            ->when($this->filterType === 'normal', fn ($q) => $q->where('is_workshop', false))
            ->when($this->filterType === 'workshop', fn ($q) => $q->where('is_workshop', true))
            ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereLike('name', "%{$searchTerm}%")
                        ->orWhereHas('subject', fn ($sq) => $sq->whereLike('name', "%{$searchTerm}%"))
                        ->orWhereHas('teacher', fn ($tq) => $tq->whereLike('first_name', "%{$searchTerm}%")->orWhereLike('last_name', "%{$searchTerm}%"));
                });
            })
            ->with(['subject', 'teacher', 'schoolGroup.shift', 'workshopStudents'])
            ->orderBy('is_workshop')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $subjects = Subject::where('school_id', $this->school->id)
            ->withCount('classes')
            ->orderBy('name')
            ->get();

        $schoolYears = $this->school->schoolYears()->orderByDesc('starts_on')->get();

        $teachers = Teacher::whereHas('teacherSchools', fn ($q) => $q->where('school_id', $this->school->id))
            ->orderBy('last_name')
            ->get();

        $groups = $this->filterYearId
            ? SchoolGroup::with('shift')
                ->where('school_year_id', $this->filterYearId)
                ->orderBy('grade')
                ->orderBy('section')
                ->get()
            : collect();

        $allStudents = Student::where('school_id', $this->school->id)
            ->where('status', 'ACTIVO')
            ->with('currentEnrollment.schoolGroup')
            ->orderBy('last_name')
            ->get();

        return view('livewire.school.classes.index', [
            'classes' => $classes,
            'subjects' => $subjects,
            'schoolYears' => $schoolYears,
            'teachers' => $teachers,
            'groups' => $groups,
            'allStudents' => $allStudents,
        ]);
    }
}
