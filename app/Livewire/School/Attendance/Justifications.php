<?php

namespace App\Livewire\School\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceJustification;
use App\Models\AuditLog;
use App\Models\School;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Justificantes de Inasistencia')]
class Justifications extends Component
{
    use WithFileUploads, WithPagination;

    #[Locked]
    public School $school;

    public string $search = '';

    // Modal Create Justification
    public bool $showJustifyModal = false;

    #[Locked]
    public ?string $selectedAttendanceId = null;

    #[Locked]
    public ?Attendance $selectedAttendance = null;

    public string $reason = '';

    public ?UploadedFile $document = null;

    // Modal Delete Justification
    public bool $showDeleteModal = false;

    public ?string $deletingJustificationId = null;

    public function mount(School $school): void
    {
        $this->school = $school;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openJustifyModal(string $attendanceId): void
    {
        $this->selectedAttendanceId = $attendanceId;
        $this->selectedAttendance = Attendance::whereHas('classSession.schoolClass', fn ($q) => $q->where('school_id', $this->school->id))
            ->with([
                'student',
                'classSession.schoolClass.subject',
                'classSession.schoolClass.schoolGroup',
            ])->findOrFail($attendanceId);

        $this->reason = '';
        $this->document = null;
        $this->resetValidation();
        $this->showJustifyModal = true;
    }

    public function saveJustification(): void
    {
        $this->validate([
            'reason' => ['required', 'string', 'max:500'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10MB max as required
        ], [
            'reason.required' => 'El motivo del justificante es obligatorio.',
            'document.mimes' => 'El documento debe ser PDF, JPG, JPEG o PNG.',
            'document.max' => 'El archivo no debe exceder 10 MB.',
        ]);

        $path = null;
        $name = null;

        if ($this->document) {
            $name = $this->document->getClientOriginalName();
            $path = $this->processAndStoreDocument($this->document);
        }

        $user = Auth::user();

        $justification = AttendanceJustification::create([
            'attendance_id' => $this->selectedAttendanceId,
            'reason' => trim($this->reason),
            'document_path' => $path,
            'document_name' => $name,
            'registered_by_user_id' => $user?->id,
        ]);

        AuditLog::record(
            action: 'JUSTIFICANTE_CREADO',
            description: "Justificante creado para {$this->selectedAttendance?->student?->full_name}. Motivo: {$justification->reason}",
            school: $this->school,
            auditable: $justification,
            newValues: ['reason' => $justification->reason, 'document' => $name]
        );

        $this->showJustifyModal = false;
        $this->reset(['selectedAttendanceId', 'selectedAttendance', 'reason', 'document']);

        Flux::toast(variant: 'success', text: 'Justificante registrado exitosamente.');
    }

    protected function processAndStoreDocument(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();

        // Convert raster images (png, jpg, jpeg, webp) to WebP using native GD
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) || str_starts_with($mime, 'image/')) {
            $realPath = $file->getRealPath();
            if ($realPath) {
                $contents = file_get_contents($realPath);
                if ($contents !== false) {
                    $image = @imagecreatefromstring($contents);
                    if ($image !== false) {
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);

                        ob_start();
                        imagewebp($image, null, 80);
                        $webpData = (string) ob_get_clean();
                        imagedestroy($image);

                        $path = 'justificantes/'.Str::uuid().'.webp';
                        Storage::disk('justifications')->put($path, $webpData);

                        return $path;
                    }
                }
            }
        }

        // For PDFs or fallback
        return (string) $file->store('justificantes', 'justifications');
    }

    public function openDeleteModal(string $justificationId): void
    {
        $this->deletingJustificationId = $justificationId;
        $this->showDeleteModal = true;
    }

    public function deleteJustification(): void
    {
        $justification = AttendanceJustification::whereHas('attendance.classSession.schoolClass', fn ($q) => $q->where('school_id', $this->school->id))
            ->with('attendance.student')
            ->findOrFail($this->deletingJustificationId);
        $studentName = $justification->attendance?->student?->full_name;
        $reason = $justification->reason;

        if ($justification->document_path) {
            Storage::disk('justifications')->delete($justification->document_path);
        }

        $justification->delete();

        AuditLog::record(
            action: 'JUSTIFICANTE_ELIMINADO',
            description: "Justificante eliminado para {$studentName}. Motivo previo: {$reason}",
            school: $this->school,
            oldValues: ['reason' => $reason]
        );

        $this->showDeleteModal = false;
        $this->reset('deletingJustificationId');

        Flux::toast(variant: 'success', text: 'Justificante eliminado.');
    }

    public function render(): View
    {
        $searchTerm = strtolower(trim($this->search));

        $attendances = Attendance::query()
            ->whereHas('classSession.schoolClass', fn ($q) => $q->where('school_id', $this->school->id))
            ->where('status', 'AUSENTE')
            ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->whereHas('student', function ($sq) use ($searchTerm) {
                    $sq->whereLike('first_name', "%{$searchTerm}%")
                        ->orWhereLike('last_name', "%{$searchTerm}%")
                        ->orWhereLike('student_id_number', "%{$searchTerm}%");
                });
            })
            ->with([
                'student',
                'classSession.schoolClass.subject',
                'classSession.schoolClass.schoolGroup.shift',
                'justification.registeredBy',
            ])
            ->latest('created_at')
            ->paginate(15);

        return view('livewire.school.attendance.justifications', [
            'attendances' => $attendances,
        ]);
    }
}
