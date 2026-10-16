<?php

namespace App\Livewire\School\Reports;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\School;
use App\Models\SchoolGroup;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Reportes y Estadísticas')]
class Index extends Component
{
    #[Locked]
    public School $school;

    // Report configuration
    public string $reportType = 'ALUMNOS_ASISTENCIA'; // ALUMNOS_ASISTENCIA, GRUPOS_RESUMEN, PROFESORES_SESIONES

    public ?string $schoolYearId = null;

    public ?string $groupId = null;

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(School $school): void
    {
        abort_if(auth()->user()?->isTeacherOnly($school), 403);

        $this->school = $school;

        $activeYear = $this->school->schoolYears()
            ->where('starts_on', '<=', today())
            ->where('ends_on', '>=', today())
            ->first()
            ?? $this->school->schoolYears()->latest('starts_on')->first();

        $this->schoolYearId = $activeYear?->id;
        $this->dateFrom = today()->subMonth()->format('Y-m-d');
        $this->dateTo = today()->format('Y-m-d');
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = "reporte-{$this->reportType}-".now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Microsoft Excel compatibility with accents
            fwrite($handle, "\xEF\xBB\xBF");

            if ($this->reportType === 'ALUMNOS_ASISTENCIA') {
                fputcsv($handle, ['Matrícula', 'Nombre Completo', 'Grupo', 'Presentes', 'Ausencias', 'Retardos', 'Justificados', '% Asistencia']);

                $data = $this->getStudentsReportData();
                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row['student_id_number'] ?? 'S/N',
                        $row['name'],
                        $row['group'],
                        $row['present'],
                        $row['absent'],
                        $row['retardo'],
                        $row['justified'],
                        $row['percentage'].'%',
                    ]);
                }
            } elseif ($this->reportType === 'GRUPOS_RESUMEN') {
                fputcsv($handle, ['Grado y Grupo', 'Turno', 'Alumnos Inscritos', 'Sesiones Impartidas', 'Total Asistencias', 'Total Faltas', 'Total Retardos', '% Asistencia Grupal']);

                $data = $this->getGroupsReportData();
                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row['name'],
                        $row['shift'],
                        $row['students_count'],
                        $row['sessions_count'],
                        $row['total_present'],
                        $row['total_absent'],
                        $row['total_retardo'],
                        $row['percentage'].'%',
                    ]);
                }
            } else {
                fputcsv($handle, ['N° Empleado', 'Profesor', 'Materias / Clases', 'Sesiones Programadas', 'Sesiones Impartidas', 'Sesiones Canceladas', 'Sustituciones Impartidas']);

                $data = $this->getTeachersReportData();
                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row['employee_number'] ?? 'S/N',
                        $row['name'],
                        $row['classes_count'],
                        $row['scheduled_sessions'],
                        $row['completed_sessions'],
                        $row['cancelled_sessions'],
                        $row['substitute_sessions'],
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getStudentsReportData(): array
    {
        $dateFilter = function ($q) {
            if ($this->dateFrom) {
                $q->whereDate('date', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $q->whereDate('date', '<=', $this->dateTo);
            }
        };

        $students = Student::where('school_id', $this->school->id)
            ->whereHas('enrollments', function ($q) {
                if ($this->schoolYearId) {
                    $q->where('school_year_id', $this->schoolYearId);
                }
                if ($this->groupId) {
                    $q->where('school_group_id', $this->groupId);
                }
            })
            ->withCount([
                'attendances as present_count' => fn ($q) => $q->where('status', 'PRESENTE')->whereHas('classSession', $dateFilter),
                'attendances as absent_count' => fn ($q) => $q->where('status', 'AUSENTE')->whereHas('classSession', $dateFilter),
                'attendances as retardo_count' => fn ($q) => $q->where('status', 'RETARDO')->whereHas('classSession', $dateFilter),
                'attendances as justified_count' => fn ($q) => $q->where('status', 'AUSENTE')->whereHas('justification')->whereHas('classSession', $dateFilter),
            ])
            ->with(['currentEnrollment.schoolGroup.shift'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $results = [];

        foreach ($students as $student) {
            $present = (int) $student->present_count;
            $absent = (int) $student->absent_count;
            $retardo = (int) $student->retardo_count;
            $justified = (int) $student->justified_count;
            $total = $present + $absent + $retardo;

            $percentage = $total > 0 ? round((($present + ($retardo * 0.5)) / $total) * 100, 1) : 100.0;

            $groupName = $student->currentEnrollment && $student->currentEnrollment->schoolGroup
                ? $student->currentEnrollment->schoolGroup->name_with_shift
                : 'Sin grupo';

            $results[] = [
                'id' => $student->id,
                'student_id_number' => $student->student_id_number,
                'name' => $student->full_name,
                'group' => $groupName,
                'present' => $present,
                'absent' => $absent,
                'retardo' => $retardo,
                'justified' => $justified,
                'percentage' => $percentage,
            ];
        }

        return $results;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getGroupsReportData(): array
    {
        $groups = SchoolGroup::whereHas('schoolYear', fn ($q) => $q->where('school_id', $this->school->id))
            ->when($this->schoolYearId, fn ($q) => $q->where('school_year_id', $this->schoolYearId))
            ->when($this->groupId, fn ($q) => $q->where('id', $this->groupId))
            ->with('shift')
            ->withCount(['enrollments' => fn ($q) => $q->where('status', 'ACTIVO')])
            ->orderBy('grade')
            ->orderBy('section')
            ->get();

        if ($groups->isEmpty()) {
            return [];
        }

        $groupIds = $groups->pluck('id');

        $sessionCounts = ClassSession::join('school_classes', 'class_sessions.school_class_id', '=', 'school_classes.id')
            ->whereIn('school_classes.school_group_id', $groupIds)
            ->when($this->dateFrom, fn ($q) => $q->whereDate('class_sessions.date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('class_sessions.date', '<=', $this->dateTo))
            ->select('school_classes.school_group_id', DB::raw('COUNT(class_sessions.id) as sessions_count'))
            ->groupBy('school_classes.school_group_id')
            ->pluck('sessions_count', 'school_classes.school_group_id');

        $attendanceStats = Attendance::join('class_sessions', 'attendances.class_session_id', '=', 'class_sessions.id')
            ->join('school_classes', 'class_sessions.school_class_id', '=', 'school_classes.id')
            ->whereIn('school_classes.school_group_id', $groupIds)
            ->when($this->dateFrom, fn ($q) => $q->whereDate('class_sessions.date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('class_sessions.date', '<=', $this->dateTo))
            ->select(
                'school_classes.school_group_id',
                DB::raw("SUM(CASE WHEN attendances.status = 'PRESENTE' THEN 1 ELSE 0 END) as total_present"),
                DB::raw("SUM(CASE WHEN attendances.status = 'AUSENTE' THEN 1 ELSE 0 END) as total_absent"),
                DB::raw("SUM(CASE WHEN attendances.status = 'RETARDO' THEN 1 ELSE 0 END) as total_retardo")
            )
            ->groupBy('school_classes.school_group_id')
            ->get()
            ->keyBy('school_group_id');

        $results = [];

        foreach ($groups as $group) {
            $stats = $attendanceStats->get($group->id);
            $totalPresent = (int) ($stats->total_present ?? 0);
            $totalAbsent = (int) ($stats->total_absent ?? 0);
            $totalRetardo = (int) ($stats->total_retardo ?? 0);
            $grandTotal = $totalPresent + $totalAbsent + $totalRetardo;

            $percentage = $grandTotal > 0 ? round((($totalPresent + ($totalRetardo * 0.5)) / $grandTotal) * 100, 1) : 100.0;

            $results[] = [
                'id' => $group->id,
                'name' => "{$group->grade}° '{$group->section}'",
                'shift' => $group->shift ? $group->shift->name : 'N/A',
                'students_count' => (int) $group->enrollments_count,
                'sessions_count' => (int) ($sessionCounts->get($group->id) ?? 0),
                'total_present' => $totalPresent,
                'total_absent' => $totalAbsent,
                'total_retardo' => $totalRetardo,
                'percentage' => $percentage,
            ];
        }

        return $results;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getTeachersReportData(): array
    {
        $teachers = Teacher::whereHas('schools', fn ($q) => $q->where('schools.id', $this->school->id))
            ->with(['classes' => fn ($q) => $q->where('school_id', $this->school->id)])
            ->orderBy('last_name')
            ->get();

        $results = [];

        foreach ($teachers as $teacher) {
            $empNumber = $teacher->employee_number;

            $originalSessions = ClassSession::where('original_teacher_id', $teacher->id)
                ->whereHas('schoolClass', fn ($q) => $q->where('school_id', $this->school->id))
                ->when($this->dateFrom, fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
                ->get();

            $substituteSessions = ClassSession::where('substitute_teacher_id', $teacher->id)
                ->whereHas('schoolClass', fn ($q) => $q->where('school_id', $this->school->id))
                ->when($this->dateFrom, fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
                ->get();

            $scheduled = $originalSessions->count();
            $completed = $originalSessions->where('status', 'COMPLETADA')->count();
            $cancelled = $originalSessions->where('status', 'CANCELADA')->count();

            $results[] = [
                'id' => $teacher->id,
                'employee_number' => $empNumber,
                'name' => $teacher->full_name,
                'classes_count' => $teacher->classes->count(),
                'scheduled_sessions' => $scheduled,
                'completed_sessions' => $completed,
                'cancelled_sessions' => $cancelled,
                'substitute_sessions' => $substituteSessions->count(),
            ];
        }

        return $results;
    }

    public function render(): View
    {
        $schoolYears = $this->school->schoolYears()->orderByDesc('starts_on')->get();

        $groups = SchoolGroup::whereHas('schoolYear', fn ($q) => $q->where('school_id', $this->school->id))
            ->when($this->schoolYearId, fn ($q) => $q->where('school_year_id', $this->schoolYearId))
            ->with('shift')
            ->orderBy('grade')
            ->orderBy('section')
            ->get();

        $reportData = match ($this->reportType) {
            'GRUPOS_RESUMEN' => $this->getGroupsReportData(),
            'PROFESORES_SESIONES' => $this->getTeachersReportData(),
            default => $this->getStudentsReportData(),
        };

        return view('livewire.school.reports.index', [
            'schoolYears' => $schoolYears,
            'groups' => $groups,
            'reportData' => $reportData,
        ]);
    }
}
