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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Control de Alumnos')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    #[Locked]
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

    public string $importStep = 'upload'; // 'upload' | 'preview'

    /** @var array<int, array<string, mixed>> */
    public array $previewReEnrollments = [];

    /** @var array<int, array<string, mixed>> */
    public array $previewNewStudents = [];

    /** @var array<int, string> */
    public array $previewErrors = [];

    public function mount(School $school): void
    {
        abort_if(auth()->user()?->isTeacherOnly($school), 403);

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

        $group = $this->school->schoolGroups()->with(['schoolYear', 'shift'])->findOrFail($this->newGroupId);

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
        $this->changeStudent = $this->school->students()->with(['currentEnrollment.schoolGroup.shift'])->findOrFail($studentId);
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

        $student = $this->school->students()->with('currentEnrollment')->findOrFail($this->changeStudentId);
        $newGroup = $this->school->schoolGroups()->with(['schoolYear', 'shift'])->findOrFail($this->changeGroupId);

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
        $this->guardiansStudent = $this->school->students()->with('guardians')->findOrFail($studentId);
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
        $student = $this->school->students()->with('guardians')->findOrFail($this->guardiansStudentId);

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

        $this->guardiansStudent = $this->school->students()->with('guardians')->findOrFail($this->guardiansStudentId);

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
        $student = $this->school->students()->findOrFail($studentId);
        $this->bajaStudentId = $student->id;
        $this->bajaStudentName = $student->full_name;
        $this->showBajaModal = true;
    }

    public function processBaja(): void
    {
        $student = $this->school->students()->findOrFail($this->bajaStudentId);
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
        $this->historyStudent = $this->school->students()->with([
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
            fputcsv($handle, ['grado', 'grupo', 'apellido_paterno', 'apellido_materno', 'nombre', 'curp', 'tutor_nombre', 'tutor_correo', 'tutor_telefono']);
            fputcsv($handle, ['1', 'A', 'García', 'López', 'Juan Carlos', 'GALJ010101HDFRRA01', 'Roberto García', 'roberto.garcia@ejemplo.com', '5511223344']);
            fputcsv($handle, ['2', 'B', 'Hernández', 'Martínez', 'Ana Sofía', 'HEMA020202MDFRRA02', 'Laura Martínez', 'laura.mtz@ejemplo.com', '5522334455']);
            fputcsv($handle, ['3', 'A', 'Rodríguez', 'Pérez', 'Luis Fernando', 'ROPL030303HDFRRA03', 'Carmen Pérez', 'carmen.perez@ejemplo.com', '5533445566']);

            fclose($handle);
        }, 'plantilla_alumnos.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function previewImportCsv(): void
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
            } elseif (in_array($col, ['curp', 'clave_curp'], true)) {
                $columnMap['curp'] = $index;
            } elseif (in_array($col, ['tutor_nombre', 'nombre_tutor', 'tutor'], true)) {
                $columnMap['tutor_nombre'] = $index;
            } elseif (in_array($col, ['tutor_correo', 'correo_tutor', 'email_tutor', 'tutor_email'], true)) {
                $columnMap['tutor_correo'] = $index;
            } elseif (in_array($col, ['tutor_telefono', 'telefono_tutor', 'celular_tutor'], true)) {
                $columnMap['tutor_telefono'] = $index;
            }
        }

        if (! isset($columnMap['grado'], $columnMap['grupo'], $columnMap['apellido_paterno'], $columnMap['nombre'])) {
            fclose($handle);
            $this->addError('importFile', 'Las columnas requeridas son: grado, grupo, apellido_paterno, apellido_materno, nombre');

            return;
        }

        // Load existing active students of the school for $O(1)$ in-memory matching
        $existingStudents = Student::where('school_id', $this->school->id)
            ->with(['currentEnrollment.schoolGroup.shift'])
            ->get();

        $curpMap = [];
        $nameMap = [];

        foreach ($existingStudents as $stu) {
            if (! empty($stu->curp)) {
                $curpMap[strtoupper(trim($stu->curp))] = $stu;
            }
            $normalizedName = Str::upper(Str::ascii(trim($stu->first_name.' '.$stu->last_name)));
            $nameMap[$normalizedName] = $stu;
        }

        $rowNumber = 1;
        $this->previewReEnrollments = [];
        $this->previewNewStudents = [];
        $this->previewErrors = [];

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
            $curp = isset($columnMap['curp'], $row[$columnMap['curp']]) ? strtoupper(trim((string) $row[$columnMap['curp']])) : null;
            $tutorNombre = isset($columnMap['tutor_nombre'], $row[$columnMap['tutor_nombre']]) ? trim((string) $row[$columnMap['tutor_nombre']]) : null;
            $tutorCorreo = isset($columnMap['tutor_correo'], $row[$columnMap['tutor_correo']]) ? strtolower(trim((string) $row[$columnMap['tutor_correo']])) : null;
            $tutorTelefono = isset($columnMap['tutor_telefono'], $row[$columnMap['tutor_telefono']]) ? trim((string) $row[$columnMap['tutor_telefono']]) : null;

            if (! in_array($grado, [1, 2, 3], true) || empty($grupo) || empty($paterno) || empty($nombre)) {
                $this->previewErrors[] = "Fila {$rowNumber}: Datos incompletos o grado inválido ({$grado}° '{$grupo}' - {$nombre} {$paterno}).";

                continue;
            }

            $lastName = trim("{$paterno} {$materno}");
            $normalizedRowName = Str::upper(Str::ascii(trim("{$nombre} {$lastName}")));

            // Match priority: by CURP, then by normalized full name
            $matchedStudent = null;
            if ($curp && isset($curpMap[$curp])) {
                $matchedStudent = $curpMap[$curp];
            } elseif (isset($nameMap[$normalizedRowName])) {
                $matchedStudent = $nameMap[$normalizedRowName];
            }

            if ($matchedStudent) {
                $currentGroup = $matchedStudent->currentEnrollment?->schoolGroup
                    ? $matchedStudent->currentEnrollment->schoolGroup->name_with_shift
                    : 'Sin grupo previo';

                $this->previewReEnrollments[] = [
                    'student_id' => $matchedStudent->id,
                    'name' => $matchedStudent->full_name,
                    'first_name' => $nombre,
                    'last_name' => $lastName,
                    'curp' => $curp ?: $matchedStudent->curp,
                    'current_group' => $currentGroup,
                    'target_grade' => $grado,
                    'target_section' => $grupo,
                    'tutor_nombre' => $tutorNombre ?: null,
                    'tutor_correo' => $tutorCorreo ?: null,
                    'tutor_telefono' => $tutorTelefono ?: null,
                ];
            } else {
                $this->previewNewStudents[] = [
                    'name' => "{$nombre} {$lastName}",
                    'first_name' => $nombre,
                    'last_name' => $lastName,
                    'curp' => $curp,
                    'target_grade' => $grado,
                    'target_section' => $grupo,
                    'tutor_nombre' => $tutorNombre ?: null,
                    'tutor_correo' => $tutorCorreo ?: null,
                    'tutor_telefono' => $tutorTelefono ?: null,
                ];
            }
        }

        fclose($handle);

        $this->importErrors = $this->previewErrors;
        $this->importStep = 'preview';
    }

    public function confirmImport(): void
    {
        if (empty($this->previewReEnrollments) && empty($this->previewNewStudents)) {
            $this->showImportModal = false;

            return;
        }

        $schoolYear = $this->school->schoolYears()->findOrFail($this->importYearId);
        $shift = Shift::findOrFail($this->importShiftId);

        $reCount = 0;
        $newCount = 0;

        DB::transaction(function () use ($schoolYear, $shift, &$reCount, &$newCount) {
            $groupCache = [];

            // 1. Process Re-Enrollments (Preserving Student and Guardians)
            foreach ($this->previewReEnrollments as $item) {
                $cacheKey = "{$item['target_grade']}_{$item['target_section']}";
                if (! isset($groupCache[$cacheKey])) {
                    $groupCache[$cacheKey] = SchoolGroup::firstOrCreate([
                        'school_year_id' => $schoolYear->id,
                        'grade' => $item['target_grade'],
                        'section' => $item['target_section'],
                    ], [
                        'shift_id' => $shift->id,
                    ]);
                }
                $group = $groupCache[$cacheKey];

                $student = Student::where('id', $item['student_id'])->first();
                if (! $student) {
                    continue;
                }

                // Close previous active enrollment if open
                if ($student->currentEnrollment) {
                    $student->currentEnrollment->update([
                        'ends_on' => $schoolYear->starts_on ?? today(),
                        'status' => 'COMPLETADO',
                    ]);
                }

                // If CURP provided and student had none, update curp
                if (! empty($item['curp']) && empty($student->curp)) {
                    $student->update(['curp' => $item['curp']]);
                }

                // Create new enrollment for target cycle and group
                StudentEnrollment::create([
                    'student_id' => $student->id,
                    'school_year_id' => $schoolYear->id,
                    'shift_id' => $group->shift_id,
                    'school_group_id' => $group->id,
                    'grade' => $group->grade,
                    'starts_on' => $schoolYear->starts_on ?? today(),
                    'ends_on' => null,
                    'status' => 'ACTIVO',
                ]);

                $this->syncCsvGuardian($student, $item);

                $reCount++;
            }

            // 2. Process New Students
            foreach ($this->previewNewStudents as $item) {
                $cacheKey = "{$item['target_grade']}_{$item['target_section']}";
                if (! isset($groupCache[$cacheKey])) {
                    $groupCache[$cacheKey] = SchoolGroup::firstOrCreate([
                        'school_year_id' => $schoolYear->id,
                        'grade' => $item['target_grade'],
                        'section' => $item['target_section'],
                    ], [
                        'shift_id' => $shift->id,
                    ]);
                }
                $group = $groupCache[$cacheKey];

                $student = Student::create([
                    'school_id' => $this->school->id,
                    'first_name' => $item['first_name'],
                    'last_name' => $item['last_name'],
                    'curp' => $item['curp'] ?: null,
                    'status' => 'ACTIVO',
                ]);

                StudentEnrollment::create([
                    'student_id' => $student->id,
                    'school_year_id' => $schoolYear->id,
                    'shift_id' => $group->shift_id,
                    'school_group_id' => $group->id,
                    'grade' => $group->grade,
                    'starts_on' => $schoolYear->starts_on ?? today(),
                    'ends_on' => null,
                    'status' => 'ACTIVO',
                ]);

                $this->syncCsvGuardian($student, $item);

                $newCount++;
            }
        });

        $totalImported = $reCount + $newCount;
        $this->importedCount = $totalImported;

        if ($totalImported > 0) {
            AuditLog::record(
                action: 'IMPORTACION_MASIVA_ALUMNOS',
                description: "Importación masiva: {$reCount} re-inscripciones y {$newCount} nuevos alumnos en ciclo {$schoolYear->name}.",
                school: $this->school,
                newValues: [
                    'total_importados' => $totalImported,
                    'reinscripciones' => $reCount,
                    'nuevos' => $newCount,
                    'ciclo' => $schoolYear->name,
                ]
            );

            Flux::toast(variant: 'success', text: "Importación exitosa: {$reCount} re-inscripciones y {$newCount} nuevos alumnos.");
        }

        $this->showImportModal = false;
        $this->reset(['importFile', 'importErrors', 'previewReEnrollments', 'previewNewStudents', 'previewErrors', 'importStep']);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function syncCsvGuardian(Student $student, array $item): void
    {
        $tutorNombre = isset($item['tutor_nombre']) ? trim((string) $item['tutor_nombre']) : '';
        if ($tutorNombre === '') {
            return;
        }

        $tutorCorreo = ! empty($item['tutor_correo']) ? strtolower(trim((string) $item['tutor_correo'])) : null;
        $tutorTelefono = ! empty($item['tutor_telefono']) ? trim((string) $item['tutor_telefono']) : null;

        $normalizedTargetName = Str::upper(Str::ascii($tutorNombre));

        $existingGuardians = $student->guardians()->get();

        foreach ($existingGuardians as $existingGuardian) {
            $emailMatches = $tutorCorreo && strtolower((string) $existingGuardian->email) === $tutorCorreo;
            $nameMatches = Str::upper(Str::ascii(trim($existingGuardian->full_name))) === $normalizedTargetName;

            if ($emailMatches || $nameMatches) {
                $updates = [];
                if ($tutorCorreo && empty($existingGuardian->email)) {
                    $updates['email'] = $tutorCorreo;
                }
                if ($tutorTelefono && empty($existingGuardian->phone)) {
                    $updates['phone'] = $tutorTelefono;
                }
                if (! empty($updates)) {
                    $existingGuardian->update($updates);
                }

                return;
            }
        }

        if ($existingGuardians->count() >= 2) {
            return;
        }

        $parts = preg_split('/\s+/', $tutorNombre) ?: [$tutorNombre];
        if (count($parts) === 1) {
            $firstName = $parts[0];
            $lastName = '';
        } elseif (count($parts) === 2) {
            $firstName = $parts[0];
            $lastName = $parts[1];
        } else {
            $lastName = implode(' ', array_slice($parts, -2));
            $firstName = implode(' ', array_slice($parts, 0, -2));
        }

        $guardian = null;
        if ($tutorCorreo) {
            $guardian = Guardian::where('school_id', $this->school->id)
                ->where('email', $tutorCorreo)
                ->first();
        }

        if (! $guardian) {
            $guardian = Guardian::create([
                'school_id' => $this->school->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $tutorCorreo,
                'phone' => $tutorTelefono,
            ]);
        }

        StudentGuardian::firstOrCreate([
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
        ], [
            'relationship' => 'Tutor',
        ]);
    }

    public function importCsv(): void
    {
        $this->previewImportCsv();

        if (empty($this->importErrors)) {
            $this->confirmImport();
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
