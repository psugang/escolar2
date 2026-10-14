<?php

namespace App\Livewire\School\Attendance;

use App\Mail\StudentAbsenceNotificationMail;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\ClassSession;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pase de Asistencia')]
class DailyAttendance extends Component
{
    public School $school;

    public string $selectedDate = '';

    // Active session for attendance taking
    public ?string $activeSessionId = null;

    public ?ClassSession $activeSession = null;

    /**
     * Array of student attendances: [student_id => 'PRESENTE'|'AUSENTE'|'RETARDO']
     *
     * @var array<string, string>
     */
    public array $attendanceStates = [];

    // Summary modal
    public bool $showConfirmModal = false;

    public bool $sendGuardianNotifications = true;

    public function mount(School $school): void
    {
        $this->school = $school;
        $this->selectedDate = today()->format('Y-m-d');
    }

    public function selectSession(string $sessionId): void
    {
        $session = ClassSession::with([
            'schoolClass.subject',
            'schoolClass.schoolGroup.shift',
            'schoolClass.workshopStudents',
            'originalTeacher',
            'substituteTeacher',
            'attendances',
        ])->findOrFail($sessionId);

        // Disallow future session attendance taking (Section 27 rule)
        if ($session->date->isFuture()) {
            Flux::toast(variant: 'danger', text: 'No se puede registrar asistencia de una sesión futura.');

            return;
        }

        $this->activeSessionId = $session->id;
        $this->activeSession = $session;

        // Load students
        $students = $this->getSessionStudents($session);

        // Pre-fill existing attendances or default to empty
        $existing = $session->attendances->keyBy('student_id');
        $this->attendanceStates = [];

        foreach ($students as $student) {
            $this->attendanceStates[$student->id] = $existing->has($student->id)
                ? $existing[$student->id]->status
                : 'PRESENTE';
        }
    }

    public function markAllPresent(): void
    {
        foreach ($this->attendanceStates as $studentId => $state) {
            $this->attendanceStates[$studentId] = 'PRESENTE';
        }

        Flux::toast(variant: 'success', text: 'Todos los alumnos marcados como PRESENTE.');
    }

    public function openConfirmModal(): void
    {
        $this->showConfirmModal = true;
    }

    public function saveAttendance(): void
    {
        if (! $this->activeSession) {
            return;
        }

        $user = Auth::user();

        foreach ($this->attendanceStates as $studentId => $status) {
            $attendance = Attendance::where('class_session_id', $this->activeSession->id)
                ->where('student_id', $studentId)
                ->first();

            if ($attendance) {
                // If modified, record log
                if ($user) {
                    $attendance->updateStatusWithLog($status, $user, 'Actualización en lista');
                }
            } else {
                Attendance::create([
                    'class_session_id' => $this->activeSession->id,
                    'student_id' => $studentId,
                    'status' => $status,
                    'recorded_by_user_id' => $user?->id,
                ]);
            }
        }

        $this->activeSession->update(['status' => 'COMPLETADA']);

        $absentStudentIds = collect($this->attendanceStates)
            ->filter(fn ($s) => $s === 'AUSENTE')
            ->keys();

        // Audit log for attendance taking
        AuditLog::record(
            action: 'PASE_ASISTENCIA',
            description: "Pase de asistencia guardado para sesión de {$this->activeSession->schoolClass?->subject?->name} ({$this->activeSession->date->format('d/m/Y')})",
            school: $this->school,
            auditable: $this->activeSession,
            newValues: [
                'session_id' => $this->activeSession->id,
                'present' => collect($this->attendanceStates)->filter(fn ($s) => $s === 'PRESENTE')->count(),
                'absent' => $absentStudentIds->count(),
                'retardo' => collect($this->attendanceStates)->filter(fn ($s) => $s === 'RETARDO')->count(),
            ]
        );

        // Notify guardians if enabled (Section 14)
        if ($this->sendGuardianNotifications && $absentStudentIds->isNotEmpty()) {
            $absentStudents = Student::with('guardians')->whereIn('id', $absentStudentIds)->get();

            foreach ($absentStudents as $absentStudent) {
                foreach ($absentStudent->guardians as $guardian) {
                    if (! empty($guardian->email)) {
                        Mail::to($guardian->email)->send(
                            new StudentAbsenceNotificationMail(
                                student: $absentStudent,
                                guardian: $guardian,
                                session: $this->activeSession,
                                school: $this->school
                            )
                        );

                        AuditLog::record(
                            action: 'AVISO_AUSENCIA_ENVIADO',
                            description: "Notificación de inasistencia enviada a {$guardian->email} ({$guardian->full_name}) para el alumno {$absentStudent->full_name}",
                            school: $this->school,
                            auditable: $absentStudent,
                            newValues: ['guardian_email' => $guardian->email]
                        );
                    }
                }
            }
        }

        $this->showConfirmModal = false;
        $this->reset(['activeSessionId', 'activeSession', 'attendanceStates']);

        Flux::toast(variant: 'success', text: 'Pase de asistencia guardado exitosamente.');
    }

    /**
     * @return Collection<int, Student>
     */
    protected function getSessionStudents(ClassSession $session): Collection
    {
        $schoolClass = $session->schoolClass;

        if (! $schoolClass) {
            return collect();
        }

        if ($schoolClass->is_workshop) {
            return $schoolClass->workshopStudents;
        }

        if ($schoolClass->school_group_id) {
            return Student::with('guardians')->whereHas('currentEnrollment', function ($q) use ($schoolClass) {
                $q->where('school_group_id', $schoolClass->school_group_id)
                    ->where('status', 'ACTIVO');
            })->orderBy('last_name')->orderBy('first_name')->get();
        }

        return collect();
    }

    public function render(): View
    {
        $user = Auth::user();
        $isGlobalOrAdmin = $user && ($user->isGlobalAdmin() || $user->hasSchoolRole($this->school, 'ADMIN_ESCOLAR') || $user->hasSchoolRole($this->school, 'DIRECTOR'));

        // Find teacher profile for current user if applicable
        $teacher = Teacher::where('user_id', $user?->id)->first();

        $sessions = ClassSession::query()
            ->whereHas('schoolClass', fn ($q) => $q->where('school_id', $this->school->id))
            ->where('date', $this->selectedDate)
            ->when(! $isGlobalOrAdmin && $teacher, function ($q) use ($teacher) {
                $q->where(function ($sub) use ($teacher) {
                    $sub->where('original_teacher_id', $teacher->id)
                        ->orWhere('substitute_teacher_id', $teacher->id);
                });
            })
            ->with([
                'schoolClass.subject',
                'schoolClass.schoolGroup.shift',
                'originalTeacher',
                'substituteTeacher',
                'attendances',
            ])
            ->orderBy('start_time')
            ->get();

        $absentCount = collect($this->attendanceStates)->filter(fn ($s) => $s === 'AUSENTE')->count();
        $presentCount = collect($this->attendanceStates)->filter(fn ($s) => $s === 'PRESENTE')->count();
        $retardoCount = collect($this->attendanceStates)->filter(fn ($s) => $s === 'RETARDO')->count();

        $students = $this->activeSession ? $this->getSessionStudents($this->activeSession) : collect();

        return view('livewire.school.attendance.daily-attendance', [
            'sessions' => $sessions,
            'students' => $students,
            'absentCount' => $absentCount,
            'presentCount' => $presentCount,
            'retardoCount' => $retardoCount,
        ]);
    }
}
