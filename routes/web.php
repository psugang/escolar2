<?php

use App\Livewire\Admin\Schools\Index as SchoolIndex;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
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
        Route::get('/ciclos', SchoolYearsIndex::class)->name('years.index');
        Route::get('/alumnos', StudentsIndex::class)->name('students.index');
        Route::get('/profesores', TeachersIndex::class)->name('teachers.index');
        Route::get('/clases', ClassesIndex::class)->name('classes.index');
        Route::get('/asistencia', DailyAttendance::class)->name('attendance.daily');
        Route::get('/justificantes', AttendanceJustifications::class)->name('attendance.justifications');
        Route::get('/reportes', ReportsIndex::class)->name('reports.index');
        Route::get('/auditoria', AuditIndex::class)->name('audit.index');
    });

require __DIR__.'/settings.php';
