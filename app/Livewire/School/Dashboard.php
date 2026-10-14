<?php

namespace App\Livewire\School;

use App\Models\School;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Panel de Control Escolar')]
class Dashboard extends Component
{
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

        return view('livewire.school.dashboard', [
            'activeYear' => $activeYear,
            'totalGroups' => $totalGroups,
            'groupsByGrade' => $groupsByGrade,
            'totalYears' => $totalYears,
            'totalMembers' => $totalMembers,
        ]);
    }
}
