<?php

namespace App\Livewire\School;

use App\Enums\SchoolRole;
use App\Models\ClassSession;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Panel de Control Escolar')]
class Dashboard extends Component
{
    #[Locked]
    public School $school;

    public function mount(School $school): void
    {
        $this->school = $school;
    }

    public function render(): View
    {
        $activeYear = $this->school->schoolYears()
            ->where('starts_on', '<=', today())
            ->where('ends_on', '>=', today())
            ->first()
            ?? $this->school->schoolYears()->latest('starts_on')->first();

        $totalGroups = $activeYear
            ? $activeYear->schoolGroups()->count()
            : 0;

        $groupsByGrade = $activeYear
            ? $activeYear->schoolGroups()
                ->with('shift')
                ->orderBy('grade')
                ->orderBy('section')
                ->get()
                ->groupBy('grade')
            : collect();

        $totalYears = $this->school->schoolYears()->count();
        $totalMembers = $this->school->memberships()->count();

        $user = auth()->user();
        $teacher = $user ? Teacher::where('user_id', $user->id)->first() : null;
        $hasTeacherRole = $user && ($user->hasSchoolRole($this->school, SchoolRole::PROFESOR) || $teacher !== null);

        $todaySessions = collect();
        if ($teacher && $hasTeacherRole) {
            $todaySessions = ClassSession::whereDate('date', today())
                ->where(function ($q) use ($teacher) {
                    $q->where('original_teacher_id', $teacher->id)
                        ->orWhere('substitute_teacher_id', $teacher->id);
                })
                ->whereHas('schoolClass', fn ($q) => $q->where('school_id', $this->school->id))
                ->with([
                    'schoolClass.subject',
                    'schoolClass.schoolGroup.shift',
                    'attendances',
                ])
                ->orderBy('start_time')
                ->get();
        }

        return view('livewire.school.dashboard', [
            'activeYear' => $activeYear,
            'totalGroups' => $totalGroups,
            'groupsByGrade' => $groupsByGrade,
            'totalYears' => $totalYears,
            'totalMembers' => $totalMembers,
            'hasTeacherRole' => $hasTeacherRole,
            'todaySessions' => $todaySessions,
            'isTeacherOnly' => $user ? $user->isTeacherOnly($this->school) : false,
        ]);
    }
}
