<?php

namespace App\Livewire\School\SchoolYears;

use App\Models\School;
use App\Models\SchoolGroup;
use App\Models\SchoolYear;
use App\Models\Shift;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ciclos y Grupos Escolares')]
class Index extends Component
{
    public School $school;

    // Selected School Year
    public ?string $selectedYearId = null;

    // Filters for groups
    public ?string $filterShiftId = null;

    public ?int $filterGrade = null;

    // Modal Create Year
    public bool $showCreateYearModal = false;

    public string $createYearName = '';

    public string $createStartsOn = '';

    public string $createEndsOn = '';

    // Modal Edit Year
    public bool $showEditYearModal = false;

    public ?string $editingYearId = null;

    public string $editYearName = '';

    public string $editStartsOn = '';

    public string $editEndsOn = '';

    // Modal Create Group
    public bool $showCreateGroupModal = false;

    public ?int $createGroupGrade = 1;

    public string $createGroupSection = 'A';

    public ?string $createGroupShiftId = null;

    // Modal Quick Batch Create
    public bool $showBatchModal = false;

    public string $batchShiftId = '';

    public string $batchMaxSection = 'B'; // generates A, B by default

    // Modal Delete Group
    public bool $showDeleteGroupModal = false;

    public ?string $deletingGroupId = null;

    public string $deletingGroupName = '';

    public function mount(School $school): void
    {
        $this->school = $school;

        $activeYear = $this->school->schoolYears()
            ->where('starts_on', '<=', today())
            ->where('ends_on', '>=', today())
            ->first()
            ?? $this->school->schoolYears()->latest('starts_on')->first();

        $this->selectedYearId = $activeYear?->id;

        $defaultShift = Shift::first();
        if ($defaultShift) {
            $this->createGroupShiftId = $defaultShift->id;
            $this->batchShiftId = $defaultShift->id;
        }
    }

    public function selectYear(string $yearId): void
    {
        $this->selectedYearId = $yearId;
    }

    public function openCreateYearModal(): void
    {
        $this->reset(['createYearName', 'createStartsOn', 'createEndsOn']);
        $this->resetValidation();
        $this->showCreateYearModal = true;
    }

    public function createSchoolYear(): void
    {
        $this->validate([
            'createYearName' => [
                'required',
                'string',
                'max:30',
                Rule::unique('school_years', 'name')->where('school_id', $this->school->id),
            ],
            'createStartsOn' => ['required', 'date'],
            'createEndsOn' => ['required', 'date', 'after:createStartsOn'],
        ], [
            'createYearName.required' => 'El nombre del ciclo es obligatorio.',
            'createYearName.unique' => 'Ya existe un ciclo escolar con este nombre en este plantel.',
            'createStartsOn.required' => 'La fecha de inicio es obligatoria.',
            'createEndsOn.required' => 'La fecha de fin es obligatoria.',
            'createEndsOn.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ]);

        $year = SchoolYear::create([
            'school_id' => $this->school->id,
            'name' => trim($this->createYearName),
            'starts_on' => $this->createStartsOn,
            'ends_on' => $this->createEndsOn,
        ]);

        $this->selectedYearId = $year->id;
        $this->showCreateYearModal = false;
        $this->reset(['createYearName', 'createStartsOn', 'createEndsOn']);

        Flux::toast(variant: 'success', text: "Ciclo escolar {$year->name} registrado exitosamente.");
    }

    public function openEditYearModal(string $yearId): void
    {
        $year = SchoolYear::where('school_id', $this->school->id)->findOrFail($yearId);

        $this->editingYearId = $year->id;
        $this->editYearName = $year->name;
        $this->editStartsOn = $year->starts_on->format('Y-m-d');
        $this->editEndsOn = $year->ends_on->format('Y-m-d');

        $this->resetValidation();
        $this->showEditYearModal = true;
    }

    public function updateSchoolYear(): void
    {
        $year = SchoolYear::where('school_id', $this->school->id)->findOrFail($this->editingYearId);

        $this->validate([
            'editYearName' => [
                'required',
                'string',
                'max:30',
                Rule::unique('school_years', 'name')
                    ->where('school_id', $this->school->id)
                    ->ignore($year->id),
            ],
            'editStartsOn' => ['required', 'date'],
            'editEndsOn' => ['required', 'date', 'after:editStartsOn'],
        ], [
            'editYearName.required' => 'El nombre del ciclo es obligatorio.',
            'editYearName.unique' => 'Ya existe un ciclo con este nombre en la escuela.',
            'editEndsOn.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ]);

        $year->update([
            'name' => trim($this->editYearName),
            'starts_on' => $this->editStartsOn,
            'ends_on' => $this->editEndsOn,
        ]);

        $this->showEditYearModal = false;
        $this->reset(['editingYearId', 'editYearName', 'editStartsOn', 'editEndsOn']);

        Flux::toast(variant: 'success', text: 'Ciclo escolar actualizado.');
    }

    public function openCreateGroupModal(): void
    {
        if (! $this->selectedYearId) {
            Flux::toast(variant: 'danger', text: 'Selecciona primero un ciclo escolar.');

            return;
        }

        $this->createGroupGrade = 1;
        $this->createGroupSection = 'A';
        $this->resetValidation();
        $this->showCreateGroupModal = true;
    }

    public function createGroup(): void
    {
        $this->validate([
            'selectedYearId' => ['required', 'exists:school_years,id'],
            'createGroupShiftId' => ['required', 'exists:shifts,id'],
            'createGroupGrade' => ['required', 'integer', 'between:1,3'],
            'createGroupSection' => [
                'required',
                'string',
                'size:1',
                Rule::unique('school_groups', 'section')
                    ->where('school_year_id', $this->selectedYearId)
                    ->where('shift_id', $this->createGroupShiftId)
                    ->where('grade', $this->createGroupGrade),
            ],
        ], [
            'createGroupGrade.required' => 'El grado es obligatorio.',
            'createGroupSection.required' => 'El grupo/sección es obligatorio.',
            'createGroupSection.unique' => 'Este grupo ya existe en el ciclo y turno seleccionados.',
        ]);

        $sectionUpper = strtoupper($this->createGroupSection);

        $group = SchoolGroup::create([
            'school_year_id' => $this->selectedYearId,
            'shift_id' => $this->createGroupShiftId,
            'grade' => $this->createGroupGrade,
            'section' => $sectionUpper,
        ]);

        $this->showCreateGroupModal = false;
        $this->reset(['createGroupGrade', 'createGroupSection']);

        Flux::toast(variant: 'success', text: "Grupo {$group->grade}°{$group->section} creado correctamente.");
    }

    public function openBatchModal(): void
    {
        if (! $this->selectedYearId) {
            Flux::toast(variant: 'danger', text: 'Selecciona primero un ciclo escolar.');

            return;
        }

        $this->resetValidation();
        $this->showBatchModal = true;
    }

    public function generateStandardGroups(): void
    {
        $this->validate([
            'batchShiftId' => ['required', 'exists:shifts,id'],
            'batchMaxSection' => ['required', 'in:A,B,C,D,E,F'],
        ]);

        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
        $maxIndex = array_search($this->batchMaxSection, $letters);
        $sectionsToCreate = array_slice($letters, 0, $maxIndex + 1);

        $createdCount = 0;

        foreach ([1, 2, 3] as $grade) {
            foreach ($sectionsToCreate as $section) {
                $exists = SchoolGroup::where('school_year_id', $this->selectedYearId)
                    ->where('shift_id', $this->batchShiftId)
                    ->where('grade', $grade)
                    ->where('section', $section)
                    ->exists();

                if (! $exists) {
                    SchoolGroup::create([
                        'school_year_id' => $this->selectedYearId,
                        'shift_id' => $this->batchShiftId,
                        'grade' => $grade,
                        'section' => $section,
                    ]);
                    $createdCount++;
                }
            }
        }

        $this->showBatchModal = false;

        Flux::toast(variant: 'success', text: "Se generaron {$createdCount} grupos automáticamente.");
    }

    public function openDeleteGroupModal(string $groupId): void
    {
        $group = SchoolGroup::with('shift')->findOrFail($groupId);
        $this->deletingGroupId = $group->id;
        $this->deletingGroupName = "{$group->grade}°{$group->section} ({$group->shift->name})";
        $this->showDeleteGroupModal = true;
    }

    public function deleteGroup(): void
    {
        $group = SchoolGroup::findOrFail($this->deletingGroupId);

        $group->delete();

        $this->showDeleteGroupModal = false;
        $this->reset(['deletingGroupId', 'deletingGroupName']);

        Flux::toast(variant: 'success', text: 'Grupo eliminado exitosamente.');
    }

    public function render(): View
    {
        $schoolYears = $this->school->schoolYears()
            ->withCount('schoolGroups')
            ->orderByDesc('starts_on')
            ->get();

        $selectedYear = $this->selectedYearId
            ? $schoolYears->firstWhere('id', $this->selectedYearId)
            : null;

        $groups = $selectedYear
            ? $selectedYear->schoolGroups()
                ->with('shift')
                ->when($this->filterShiftId, fn ($q) => $q->where('shift_id', $this->filterShiftId))
                ->when($this->filterGrade, fn ($q) => $q->where('grade', $this->filterGrade))
                ->orderBy('grade')
                ->orderBy('section')
                ->get()
            : collect();

        $shifts = Shift::orderBy('name')->get();

        return view('livewire.school.school-years.index', [
            'schoolYears' => $schoolYears,
            'selectedYear' => $selectedYear,
            'groups' => $groups,
            'shifts' => $shifts,
        ]);
    }
}
