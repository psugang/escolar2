<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AttendanceJustification;
use App\Models\School;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttendanceJustificationDocumentController extends Controller
{
    public function show(School $school, AttendanceJustification $justification): Response
    {
        $justificationSchoolId = $justification->attendance?->classSession?->schoolClass?->school_id;

        abort_unless($justificationSchoolId === $school->id, 403);

        abort_unless(
            $justification->document_path && Storage::disk('justifications')->exists($justification->document_path),
            404
        );

        return Storage::disk('justifications')->response(
            $justification->document_path,
            $justification->document_name
        );
    }
}
