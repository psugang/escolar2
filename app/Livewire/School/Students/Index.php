<?php

namespace App\Livewire\School\Students;

use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolGroup;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGuardian;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Control de Alumnos')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public School $school;

    // Filters
    public string $search = '';

    public ?string $filterYearId = null;

    public ?string $filterGroupId = null;

    public string $filterStatus = 'ACTIVO'; // ACTIVO | BAJA | ALL

    // Modal Create Student
    public bool $showCreateModal = false;

    public string $newFirstName = '';

    public string $newLastName = '';

    public string $newStudentIdNumber = '';

    public string $newCurp = '';

    public string $newBirthDate = '';

    public string $newGender = '';

    public string $newGroupId = '';

    public string $newStartsOn = '';

    // Modal Change Group
    public bool $showChangeGroupModal = false;

    public ?string $changeStudentId = null;

    public ?Student $changeStudent = null;

    public string $changeGroupId = '';

    public string $changeStartsOn = '';

    // Modal Guardians
    public bool $showGuardiansModal = false;

    public ?string $guardiansStudentId = null;

    public ?Student $guardiansStudent = null;

    public string $guardianMode = 'new'; // 'new' | 'existing'

    public string $guardianFirstName = '';

    public string $guardianLastName = '';

    public string $guardianRelationship = 'Padre';

    public string $guardianPhone = '';

    public string $guardianEmail = '';

    public string $existingGuardianId = '';

    // Modal Baja
    public bool $showBajaModal = false;

    public ?string $bajaStudentId = null;

    public string $bajaStudentName = '';

    // Modal History
    public bool $showHistoryModal = false;

    public ?Student $historyStudent = null;

    // Modal CSV Import
    public bool $showImportModal = false;

    public ?UploadedFile $importFile = null;

    public ?string $importYearId = null;

    public ?string $importShiftId = null;

    /** @var array<int, string> */
    public array $importErrors = [];

    public int $importedCount = 0;

    public function mount(School $school): void
    {
        $this->school = $school;

        $activeYear = $this->school->schoolYears()
            ->where('starts_on', '<=', today())
            ->where('ends_on', '>=', today())
            ->first()
            ?? $this->school->schoolYears()->latest('starts_on')->first();

        $this->filterYearId = $activeYear?->id;
        $this->newStartsOn = today()->format('Y-m-d');
        $this->changeStartsOn = today()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterYearId(): void
    {
        $this->reset('filterGroupId');
        $this->resetPage();
    }

    public function updatingFilterGroupId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'newFirstName',
            'newLastName',
            'newStudentIdNumber',
            'newCurp',
            'newBirthDate',
            'newGender',
            'newGroupId',
        ]);
        $this->newStartsOn = today()->format('Y-m-d');
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function createStudent(): void
    {
        $this->validate([
            'newFirstName' => ['required', 'string', 'max:100'],
            'newLastName' => ['required', 'string', 'max:100'],
            'newStudentIdNumber' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('students', 'student_id_number')->where('school_id', $this->school->id),
            ],
            'newCurp' => ['nullable', 'string', 'size:18'],
            'newBirthDate' => ['nullable', 'date'],
            'newGender' => ['nullable', 'in:M,F,X'],
            'newGroupId' => ['required', 'exists:school_groups,id'],
            'newStartsOn' => ['required', 'date'],
        ], [
            'newFirstName.required' => 'El nombre es obligatorio.',
            'newLastName.required' => 'Los apellidos son obligatorios.',
            'newStudentIdNumber.unique' => 'Ya existe un alumno con esta matrícula en este plantel.',
            'newCurp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'newGroupId.required' => 'Debes seleccionar el grupo de inscripción inicial.',
            'newStartsOn.required' => 'La fecha de inicio de inscripción es obligatoria.',
        ]);

        $group = SchoolGroup::with(['schoolYear', 'shift'])->findOrFail($this->newGroupId);

        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => trim($this->newFirstName),
            'last_name' => trim($this->newLastName),
            'student_id_number' => $this->newStudentIdNumber ? trim($this->newStudentIdNumber) : null,
            'curp' => $this->newCurp ? strtoupper(trim($this->newCurp)) : null,
            'birth_date' => $this->newBirthDate ?: null,
            'gender' => $this->newGender ?: null,
            'status' => 'ACTIVO',
        ]);

        StudentEnrollment::create([
            'student_id' => $student->id,
            'school_year_id' => $group->school_year_id,
            'school_group_id' => $group->id,
            'shift_id' => $group->shift_id,
            'grade' => $group->grade,
            'starts_on' => $this->newStartsOn,
            'ends_on' => null,
            'status' => 'ACTIVO',
        ]);

        AuditLog::record(
            action: 'ALUMNO_CREADO',
            description: "Alumno inscrito: {$student->full_name}",
            school: $this->school,
            auditable: $student,
            newValues: ['name' => $student->full_name, 'curp' => $student->curp]
        );

        $this->showCreateModal = false;

        Flux::toast(variant: 'success', text: "Alumno {$student->full_name} inscrito exitosamente.");
    }

    public function openChangeGroupModal(string $studentId): void
    {
        $this->changeStudentId = $studentId;
        $this->changeStudent = Student::with(['currentEnrollment.schoolGroup.shift'])->findOrFail($studentId);
        $this->changeGroupId = '';
        $this->changeStartsOn = today()->format('Y-m-d');
        $this->resetValidation();
        $this->showChangeGroupModal = true;
    }

    public function processChangeGroup(): void
    {
        $this->validate([
            'changeGroupId' => ['required', 'exists:school_groups,id'],
            'changeStartsOn' => ['required', 'date'],
        ], [
            'changeGroupId.required' => 'Debes seleccionar el nuevo grupo.',
            'changeStartsOn.required' => 'La fecha del cambio es obligatoria.',
        ]);

        $student = Student::with('currentEnrollment')->findOrFail($this->changeStudentId);
        $newGroup = SchoolGroup::with(['schoolYear', 'shift'])->findOrFail($this->changeGroupId);

        // Close previous enrollment
        if ($student->currentEnrollment) {
            $student->currentEnrollment->update([
                'status' => 'CAMBIO_GRUPO',
                'ends_on' => $this->changeStartsOn,
            ]);
        }

        // Create new enrollment
        StudentEnrollment::create([
            'student_id' => $student->id,
            'school_year_id' => $newGroup->school_year_id,
            'school_group_id' => $newGroup->id,
            'shift_id' => $newGroup->shift_id,
            'grade' => $newGroup->grade,
            'starts_on' => $this->changeStartsOn,
            'ends_on' => null,
            'status' => 'ACTIVO',
        ]);

        AuditLog::record(
            action: 'CAMBIO_GRUPO',
            description: "Cambio de grupo para {$student->full_name} al grupo {$newGroup->grade}° '{$newGroup->section}'",
            school: $this->school,
            auditable: $student,
            newValues: ['group_id' => $newGroup->id, 'effective_date' => $this->changeStartsOn]
        );

        $this->showChangeGroupModal = false;
        $this->reset(['changeStudentId', 'changeStudent', 'changeGroupId']);

        Flux::toast(variant: 'success', text: "Cambio de grupo registrado para {$student->full_name}.");
    }

    public function openGuardiansModal(string $studentId): void
    {
        $this->guardiansStudentId = $studentId;
        $this->guardiansStudent = Student::with('guardians')->findOrFail($studentId);
        $this->reset([
            'guardianFirstName',
            'guardianLastName',
            'guardianPhone',
            'guardianEmail',
            'existingGuardianId',
        ]);
        $this->guardianRelationship = 'Padre';
        $this->guardianMode = 'new';
        $this->resetValidation();
        $this->showGuardiansModal = true;
    }

    public function addGuardian(): void
    {
        $student = Student::with('guardians')->findOrFail($this->guardiansStudentId);

        if ($student->guardians->count() >= 2) {
            $this->addError('guardianRelationship', 'Un alumno sólo puede tener hasta 2 tutores asignados.');

            return;
        }

        if ($this->guardianMode === 'existing') {
            $this->validate([
                'existingGuardianId' => ['required', 'exists:guardians,id'],
                'guardianRelationship' => ['required', 'string', 'max:50'],
            ]);

            StudentGuardian::firstOrCreate([
                'student_id' => $student->id,
                'guardian_id' => $this->existingGuardianId,
            ], [
                'relationship' => trim($this->guardianRelationship),
            ]);
        } else {
            $this->validate([
                'guardianFirstName' => ['required', 'string', 'max:100'],
                'guardianLastName' => ['required', 'string', 'max:100'],
                'guardianRelationship' => ['required', 'string', 'max:50'],
                'guardianPhone' => ['nullable', 'string', 'max:30'],
                'guardianEmail' => ['nullable', 'email', 'max:150'],
            ], [
                'guardianFirstName.required' => 'El nombre del tutor es obligatorio.',
                'guardianLastName.required' => 'Los apellidos del tutor son obligatorios.',
            ]);

            $guardian = Guardian::create([
                'school_id' => $this->school->id,
                'first_name' => trim($this->guardianFirstName),
                'last_name' => trim($this->guardianLastName),
                'phone' => $this->guardianPhone ? trim($this->guardianPhone) : null,
                'email' => $this->guardianEmail ? trim($this->guardianEmail) : null,
            ]);

            StudentGuardian::create([
                'student_id' => $student->id,
                'guardian_id' => $guardian->id,
                'relationship' => trim($this->guardianRelationship),
            ]);
        }

        AuditLog::record(
            action: 'TUTOR_VINCULADO',
            description: "Tutor vinculado al alumno {$student->full_name}",
            school: $this->school,
            auditable: $student
        );

        $this->guardiansStudent = $student->fresh(['guardians']);
        $this->reset([
            'guardianFirstName',
            'guardianLastName',
            'guardianPhone',
            'guardianEmail',
            'existingGuardianId',
        ]);

        Flux::toast(variant: 'success', text: 'Tutor asignado correctamente.');
    }

    public function removeGuardian(string $guardianId): void
    {
        StudentGuardian::where('student_id', $this->guardiansStudentId)
            ->where('guardian_id', $guardianId)
            ->delete();

        $this->guardiansStudent = Student::with('guardians')->findOrFail($this->guardiansStudentId);

        AuditLog::record(
            action: 'TUTOR_DESVINCULADO',
            description: "Tutor desvinculado del alumno {$this->guardiansStudent->full_name}",
            school: $this->school,
            auditable: $this->guardiansStudent
        );

        Flux::toast(variant: 'success', text: 'Tutor desvinculado.');
    }

    public function openBajaModal(string $studentId): void
    {
        $student = Student::findOrFail($studentId);
        $this->bajaStudentId = $student->id;
        $this->bajaStudentName = $student->full_name;
        $this->showBajaModal = true;
    }

    public function processBaja(): void
    {
        $student = Student::findOrFail($this->bajaStudentId);
        $student->markAsBaja();

        AuditLog::record(
            action: 'ALUMNO_BAJA',
            description: "Baja de alumno registrada: {$student->full_name}",
            school: $this->school,
            auditable: $student
        );

        $this->showBajaModal = false;
        $this->reset(['bajaStudentId', 'bajaStudentName']);

        Flux::toast(variant: 'success', text: "El alumno {$student->full_name} ha causado baja escolar.");
    }

    public function openHistoryModal(string $studentId): void
    {
        $this->historyStudent = Student::with([
            'enrollments.schoolYear',
            'enrollments.schoolGroup.shift',
        ])->findOrFail($studentId);

        $this->showHistoryModal = true;
    }

    public function openImportModal(): void
    {
        $activeYear = $this->school->schoolYears()
            ->where('starts_on', '<=', today())
            ->where('ends_on', '>=', today())
            ->first()
            ?? $this->school->schoolYears()->latest('starts_on')->first();

        $this->importYearId = $this->filterYearId ?? $activeYear?->id;
        $this->importShiftId = Shift::first()?->id;
        $this->importFile = null;
        $this->importErrors = [];
        $this->importedCount = 0;
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function downloadSampleCsv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['grado', 'grupo', 'apellido_paterno', 'apellido_materno', 'nombre']);
            fputcsv($handle, ['1', 'A', 'García', 'López', 'Juan Carlos']);
            fputcsv($handle, ['2', 'B', 'Hernández', 'Martínez', 'Ana Sofía']);
            fputcsv($handle, ['3', 'A', 'Rodríguez', 'Pérez', 'Luis Fernando']);

            fclose($handle);
        }, 'plantilla_alumnos.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importCsv(): void
    {
        $this->validate([
            'importYearId' => ['required', 'exists:school_years,id'],
            'importShiftId' => ['required', 'exists:shifts,id'],
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ], [
            'importYearId.required' => 'Debes seleccionar el ciclo escolar para la inscripción.',
            'importShiftId.required' => 'Debes seleccionar el turno para los grupos.',
            'importFile.required' => 'Debes seleccionar un archivo CSV.',
            'importFile.mimes' => 'El archivo debe ser de formato CSV.',
            'importFile.max' => 'El archivo no debe exceder 5 MB.',
        ]);

        if (! $this->importFile) {
            return;
        }

        $schoolYear = $this->school->schoolYears()->findOrFail($this->importYearId);
        $shift = Shift::findOrFail($this->importShiftId);

        $path = $this->importFile->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            $this->addError('importFile', 'No se pudo leer el archivo cargado.');

            return;
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            $this->addError('importFile', 'El archivo CSV está vacío.');

            return;
        }

        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';

        $header = str_getcsv($firstLine, $delimiter);
        $header = array_map(function ($col) {
            $col = strtolower(trim((string) $col));

            return str_replace(['á', 'é', 'í', 'ó', 'ú', ' '], ['a', 'e', 'i', 'o', 'u', '_'], $col);
        }, $header);

        $columnMap = [];
        foreach ($header as $index => $col) {
            if (in_array($col, ['grado', 'grado_ano', 'grado_escolar'], true)) {
                $columnMap['grado'] = $index;
            } elseif (in_array($col, ['grupo', 'seccion'], true)) {
                $columnMap['grupo'] = $index;
            } elseif (in_array($col, ['apellido_paterno', 'paterno', 'primer_apellido'], true)) {
                $columnMap['apellido_paterno'] = $index;
            } elseif (in_array($col, ['apellido_materno', 'materno', 'segundo_apellido'], true)) {
                $columnMap['apellido_materno'] = $index;
            } elseif (in_array($col, ['nombre', 'nombres', 'primer_nombre'], true)) {
                $columnMap['nombre'] = $index;
            }
        }

        if (! isset($columnMap['grado'], $columnMap['grupo'], $columnMap['apellido_paterno'], $columnMap['nombre'])) {
            fclose($handle);
            $this->addError('importFile', 'Las columnas requeridas son: grado, grupo, apellido_paterno, apellido_materno, nombre');

            return;
        }

        $rowNumber = 1;
        $imported = 0;
        $errors = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if (empty(array_filter($row))) {
                continue;
            }

            $grado = isset($row[$columnMap['grado']]) ? (int) trim((string) $row[$columnMap['grado']]) : null;
            $grupo = isset($row[$columnMap['grupo']]) ? strtoupper(trim((string) $row[$columnMap['grupo']])) : '';
            $paterno = isset($row[$columnMap['apellido_paterno']]) ? trim((string) $row[$columnMap['apellido_paterno']]) : '';
            $materno = isset($columnMap['apellido_materno'], $row[$columnMap['apellido_materno']]) ? trim((string) $row[$columnMap['apellido_materno']]) : '';
            $nombre = isset($row[$columnMap['nombre']]) ? trim((string) $row[$columnMap['nombre']]) : '';

            if (! in_array($grado, [1, 2, 3], true) || empty($grupo) || empty($paterno) || empty($nombre)) {
                $errors[] = "Fila {$rowNumber}: Datos incompletos o grado inválido ({$grado}° '{$grupo}' - {$nombre} {$paterno}).";

                continue;
            }

            $group = SchoolGroup::firstOrCreate([
                'school_year_id' => $schoolYear->id,
                'grade' => $grado,
                'section' => $grupo,
            ], [
                'shift_id' => $shift->id,
            ]);

            $lastName = trim("{$paterno} {$materno}");

            $student = Student::create([
                'school_id' => $this->school->id,
                'first_name' => $nombre,
                'last_name' => $lastName,
                'status' => 'ACTIVO',
            ]);

            StudentEnrollment::create([
                'student_id' => $student->id,
                'school_year_id' => $schoolYear->id,
                'shift_id' => $group->shift_id,
                'school_group_id' => $group->id,
                'grade' => $group->grade,
                'starts_on' => $schoolYear->starts_on ?? today(),
                'status' => 'ACTIVO',
            ]);

            $imported++;
        }

        fclose($handle);

        $this->importedCount = $imported;
        $this->importErrors = $errors;

        if ($imported > 0) {
            AuditLog::record(
                action: 'IMPORTACION_MASIVA_ALUMNOS',
                description: "Importación masiva: se registraron {$imported} alumnos desde archivo CSV.",
                school: $this->school,
                newValues: ['total_importados' => $imported, 'ciclo' => $schoolYear->name]
            );

            Flux::toast(variant: 'success', text: "Se importaron exitosamente {$imported} alumnos.");
        }

        if (empty($errors)) {
            $this->showImportModal = false;
            $this->reset(['importFile', 'importErrors']);
        }
    }

    public function render(): View
    {
        $searchTerm = strtolower(trim($this->search));

        $students = Student::query()
            ->where('school_id', $this->school->id)
            ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereLike('first_name', "%{$searchTerm}%")
                        ->orWhereLike('last_name', "%{$searchTerm}%")
                        ->orWhereLike('student_id_number', "%{$searchTerm}%")
                        ->orWhereLike('curp', "%{$searchTerm}%");
                });
            })
            ->when($this->filterStatus !== 'ALL', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterGroupId, function ($query) {
                $query->whereHas('currentEnrollment', function ($q) {
                    $q->where('school_group_id', $this->filterGroupId);
                });
            })
            ->when($this->filterYearId && ! $this->filterGroupId, function ($query) {
                $query->whereHas('currentEnrollment', function ($q) {
                    $q->where('school_year_id', $this->filterYearId);
                });
            })
            ->with([
                'currentEnrollment.schoolGroup.shift',
                'currentEnrollment.schoolYear',
                'guardians',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        $schoolYears = $this->school->schoolYears()->orderByDesc('starts_on')->get();

        $groups = $this->filterYearId
            ? SchoolGroup::with('shift')
                ->where('school_year_id', $this->filterYearId)
                ->orderBy('grade')
                ->orderBy('section')
                ->get()
            : collect();

        $allSchoolGroups = SchoolGroup::with(['schoolYear', 'shift'])
            ->whereHas('schoolYear', fn ($q) => $q->where('school_id', $this->school->id))
            ->orderByDesc('school_year_id')
            ->orderBy('grade')
            ->orderBy('section')
            ->get();

        $existingGuardians = Guardian::where('school_id', $this->school->id)
            ->orderBy('last_name')
            ->get();

        $shifts = Shift::orderBy('name')->get();

        return view('livewire.school.students.index', [
            'students' => $students,
            'schoolYears' => $schoolYears,
            'groups' => $groups,
            'allSchoolGroups' => $allSchoolGroups,
            'existingGuardians' => $existingGuardians,
            'shifts' => $shifts,
        ]);
    }
}
