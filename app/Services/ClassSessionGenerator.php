<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\SchoolClass;
use App\Models\SchoolSuspension;
use App\Models\SchoolYear;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ClassSessionGenerator
{
    public function generateForClass(SchoolClass $class): int
    {
        $schoolYear = $class->schoolYear;
        if (! $schoolYear) {
            return 0;
        }

        $schedules = $class->schedules;
        if ($schedules->isEmpty()) {
            return 0;
        }

        /** @var array<string, int> $suspensionDates */
        $suspensionDates = SchoolSuspension::where('school_year_id', $schoolYear->id)
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->flip()
            ->all();

        $startDate = $schoolYear->starts_on->copy();
        $endDate = $schoolYear->ends_on->copy();

        if ($startDate->isAfter($endDate)) {
            return 0;
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $sessionsToInsert = [];
        $now = now();

        foreach ($period as $date) {
            // Exclude Saturday (6) and Sunday (7)
            if ($date->isWeekend()) {
                continue;
            }

            $dateStr = $date->format('Y-m-d');
            if (isset($suspensionDates[$dateStr])) {
                continue;
            }

            $dayOfWeek = $date->dayOfWeekIso;

            $matchingSchedules = $schedules->filter(function ($sched) use ($dayOfWeek, $date) {
                if ($sched->day_of_week !== $dayOfWeek) {
                    return false;
                }
                if ($date->isBefore($sched->effective_from)) {
                    return false;
                }
                if ($sched->effective_until && $date->isAfter($sched->effective_until)) {
                    return false;
                }

                return true;
            });

            foreach ($matchingSchedules as $schedule) {
                $sessionsToInsert[] = [
                    'id' => (string) Str::uuid7(),
                    'school_class_id' => $class->id,
                    'date' => $dateStr,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'original_teacher_id' => $class->teacher_id,
                    'substitute_teacher_id' => null,
                    'status' => 'PROGRAMADA',
                    'cancellation_reason' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (empty($sessionsToInsert)) {
            return 0;
        }

        $inserted = 0;
        $chunks = array_chunk($sessionsToInsert, 500);
        foreach ($chunks as $chunk) {
            $inserted += ClassSession::insertOrIgnore($chunk);
        }

        return $inserted;
    }

    public function generateForSchoolYear(SchoolYear $schoolYear): int
    {
        $classes = SchoolClass::where('school_year_id', $schoolYear->id)
            ->with(['schedules', 'schoolYear'])
            ->get();

        $total = 0;
        foreach ($classes as $class) {
            $total += $this->generateForClass($class);
        }

        return $total;
    }
}
