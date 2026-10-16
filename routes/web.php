<?php

use App\Http\Controllers\School\AttendanceJustificationDocumentController;
use App\Livewire\Admin\Schools\Index as SchoolIndex;
use App\Livewire\Auth\AcceptInvitation;
use App\Livewire\School\Attendance\DailyAttendance;
use App\Livewire\School\Attendance\Justifications as AttendanceJustifications;
use App\Livewire\School\Audit\Index as AuditIndex;
use App\Livewire\School\Classes\Index as ClassesIndex;
use App\Livewire\School\Dashboard as SchoolDashboard;
use App\Livewire\School\Reports\Index as ReportsIndex;
use App\Livewire\School\SchoolYears\Index as SchoolYearsIndex;
use App\Livewire\School\Students\Index as StudentsIndex;
use App\Livewire\School\Teachers\Index as TeachersIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/invitacion/{token}', AcceptInvitation::class)
    ->middleware('throttle:invitations.accept')
    ->name('invitations.accept');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();
        if ($user && ! $user->isGlobalAdmin()) {
            $memberships = $user->schoolMemberships()->with('school')->get();
            if ($memberships->count() === 1 && $memberships->first()->school) {
                return redirect()->route('school.dashboard', $memberships->first()->school);
            }
        }

        return view('dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified', 'global.admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/escuelas', SchoolIndex::class)
            ->name('schools.index');
    });

Route::middleware(['auth', 'verified', 'school.access'])
    ->prefix('escuela/{school}')
    ->name('school.')
    ->group(function () {
        Route::get('/dashboard', SchoolDashboard::class)->name('dashboard');
        Route::get('/asistencia', DailyAttendance::class)->name('attendance.daily');
        Route::get('/justificantes', AttendanceJustifications::class)->name('attendance.justifications');
        Route::get('/justificantes/{justification}/documento', [AttendanceJustificationDocumentController::class, 'show'])->name('justifications.document');

        Route::middleware('school.role:ADMIN_ESCOLAR,DIRECTOR,CONTROL_ESCOLAR')->group(function () {
            Route::get('/ciclos', SchoolYearsIndex::class)->name('years.index');
            Route::get('/alumnos', StudentsIndex::class)->name('students.index');
            Route::get('/profesores', TeachersIndex::class)->name('teachers.index');
            Route::get('/clases', ClassesIndex::class)->name('classes.index');
            Route::get('/reportes', ReportsIndex::class)->name('reports.index');
            Route::get('/auditoria', AuditIndex::class)->name('audit.index');
        });
    });

require __DIR__.'/settings.php';
