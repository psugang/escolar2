<?php

namespace App\Livewire\School\Audit;

use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Registro de Auditoría')]
class Index extends Component
{
    use WithPagination;

    #[Locked]
    public School $school;

    public string $search = '';

    public string $filterAction = '';

    public string $filterUserId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    // Modal detail
    public bool $showDetailModal = false;

    public ?AuditLog $selectedLog = null;

    public function mount(School $school): void
    {
        abort_if(auth()->user()?->isTeacherOnly($school), 403);

        $this->school = $school;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAction(): void
    {
        $this->resetPage();
    }

    public function updatingFilterUserId(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterAction', 'filterUserId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function viewDetails(string $logId): void
    {
        $this->selectedLog = $this->school->auditLogs()->with(['user', 'school'])->findOrFail($logId);
        $this->showDetailModal = true;
    }

    public function render(): View
    {
        $searchTerm = strtolower(trim($this->search));

        $logs = AuditLog::query()
            ->where('school_id', $this->school->id)
            ->when($this->filterAction !== '', fn ($q) => $q->where('action', $this->filterAction))
            ->when($this->filterUserId !== '', fn ($q) => $q->where('user_id', $this->filterUserId))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($searchTerm !== '', function ($q) use ($searchTerm) {
                $q->where(function ($sub) use ($searchTerm) {
                    $sub->whereLike('description', "%{$searchTerm}%")
                        ->orWhereLike('action', "%{$searchTerm}%")
                        ->orWhereLike('ip_address', "%{$searchTerm}%");
                });
            })
            ->with(['user'])
            ->latest('created_at')
            ->paginate(20);

        $actions = AuditLog::where('school_id', $this->school->id)
            ->distinct()
            ->pluck('action')
            ->sort()
            ->values();

        $users = User::whereHas('schoolMemberships', fn ($q) => $q->where('school_id', $this->school->id))
            ->orderBy('name')
            ->get();

        return view('livewire.school.audit.index', [
            'logs' => $logs,
            'actions' => $actions,
            'users' => $users,
        ]);
    }
}
