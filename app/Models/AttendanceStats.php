<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AttendanceStats extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'school_class_id',
        'period_type',
        'period_start',
        'period_end',
        'total_days',
        'present_days',
        'absent_days',
        'late_days',
        'excused_days',
        'left_early_days',
        'total_minutes_late',
        'attendance_percentage',
        'last_calculated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'attendance_percentage' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    // Scopes
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('school_class_id', $classId);
    }

    public function scopeOverall($query)
    {
        return $query->whereNull('school_class_id');
    }

    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('period_type', 'yearly');
    }

    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('period_start', $start)->where('period_end', $end);
    }

    // Static calculation method - TIME-BASED attendance percentage
    public static function calculateForStudent($studentId, $classId = null, $periodType = 'monthly', $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $query = Attendance::where('student_id', $studentId)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($classId) {
            $query->where('school_class_id', $classId);
        }

        // Eager load class for duration calculation
        $attendances = $query->with('schoolClass')->get();

        // Count statuses
        $present = $attendances->filter(fn($a) => str_starts_with($a->status, 'present'))->count();
        $absent = $attendances->filter(fn($a) => str_starts_with($a->status, 'absent'))->count();
        $late = $attendances->filter(fn($a) => str_starts_with($a->status, 'late'))->count();
        $excused = $attendances->filter(fn($a) => str_contains($a->status, 'excused'))->count();
        $leftEarly = $attendances->where('left_early', true)->count();
        $totalMinutesLate = $attendances->sum('minutes_late') ?? 0;
        $totalMinutesEarly = $attendances->sum('minutes_early') ?? 0;

        // Calculate TIME-BASED attendance percentage
        // Formula: Sum(actual time attended) / Sum(total class time) * 100
        $totalPossibleMinutes = 0;
        $totalAttendedMinutes = 0;

        foreach ($attendances as $attendance) {
            // Get duration for this specific attendance record (day-specific or custom override)
            $classDuration = static::getClassDurationForAttendance($attendance);
            if ($classDuration <= 0) continue;

            // Handle excused statuses per item 14 requirement
            if ($attendance->status === 'absent_excused') {
                // Excused absences: Don't count at all - exclude from calculation entirely
                continue;
            } elseif ($attendance->status === 'late_excused') {
                // Late excused: Subtract late minutes from class duration for calculation
                $lateMinutes = $attendance->minutes_late ?? 0;
                $adjustedDuration = max(0, $classDuration - $lateMinutes);
                $totalPossibleMinutes += $adjustedDuration;
                $totalAttendedMinutes += $adjustedDuration; // They attended the remaining time
                continue;
            }

            // Regular status handling
            $totalPossibleMinutes += $classDuration;

            // Calculate attended time based on status
            if (str_starts_with($attendance->status, 'absent')) {
                // Absent = 0 minutes
                $attendedMinutes = 0;
            } elseif (str_starts_with($attendance->status, 'late')) {
                // Late = class duration - minutes late
                $attendedMinutes = max(0, $classDuration - ($attendance->minutes_late ?? 0));
            } elseif ($attendance->left_early) {
                // Left early = class duration - minutes early
                $attendedMinutes = max(0, $classDuration - ($attendance->minutes_early ?? 0));
            } else {
                // Present = full duration
                $attendedMinutes = $classDuration;
            }

            // If both late AND left early
            if (str_starts_with($attendance->status, 'late') && $attendance->left_early) {
                $attendedMinutes = max(0, $classDuration - ($attendance->minutes_late ?? 0) - ($attendance->minutes_early ?? 0));
            }

            $totalAttendedMinutes += $attendedMinutes;
        }

        $attendancePercentage = $totalPossibleMinutes > 0
            ? round(($totalAttendedMinutes / $totalPossibleMinutes) * 100, 2)
            : 0;

        // Calculate total_days excluding absent_excused (per item 14 requirement)
        $absentExcusedCount = $attendances->where('status', 'absent_excused')->count();
        $totalDays = $attendances->count() - $absentExcusedCount;

        $stats = [
            'total_days' => $totalDays,
            'present_days' => $present,
            'absent_days' => $absent,
            'late_days' => $late,
            'excused_days' => $excused,
            'left_early_days' => $leftEarly,
            'total_minutes_late' => $totalMinutesLate,
            'attendance_percentage' => $attendancePercentage,
        ];

        return static::updateOrCreate(
            [
                'student_id' => $studentId,
                'school_class_id' => $classId,
                'period_type' => $periodType,
                'period_start' => $startDate,
            ],
            array_merge($stats, [
                'period_end' => $endDate,
                'last_calculated_at' => now(),
            ])
        );
    }

    // Helper: Get class duration in minutes from schedule_time and end_time
    private static function getClassDurationMinutes($schoolClass)
    {
        if (!$schoolClass || !$schoolClass->schedule_time || !$schoolClass->end_time) {
            return 60; // Default to 60 minutes if not specified
        }

        try {
            // Extract just the time portion (handles both "07:30" and "2026-01-13 07:30:00")
            $startTime = preg_match('/(\d{1,2}:\d{2}(:\d{2})?)/', $schoolClass->schedule_time, $m1) ? $m1[1] : '00:00';
            $endTime = preg_match('/(\d{1,2}:\d{2}(:\d{2})?)/', $schoolClass->end_time, $m2) ? $m2[1] : '00:00';

            $today = Carbon::today()->format('Y-m-d');
            $start = Carbon::parse("$today $startTime");
            $end = Carbon::parse("$today $endTime");

            $duration = $end->diffInMinutes($start, false); // false = can be negative
            return $duration > 0 ? $duration : 60; // Default if negative
        } catch (\Exception $e) {
            return 60; // Default
        }
    }

    /**
     * Get the class duration for a specific attendance record.
     * Priority order:
     * 1. Use class_start_time and class_end_time from the attendance record (date-specific override)
     * 2. Look up day-of-week specific schedule from class_schedules
     * 3. Fallback to 60 minutes
     *
     * @param Attendance $attendance The attendance record
     * @return int Duration in minutes
     */
    private static function getClassDurationForAttendance($attendance)
    {
        // Priority 1: Check if attendance has custom start/end times
        if ($attendance->class_start_time && $attendance->class_end_time) {
            try {
                $start = Carbon::parse($attendance->class_start_time);
                $end = Carbon::parse($attendance->class_end_time);
                $duration = $end->diffInMinutes($start, false);
                if ($duration > 0) {
                    return $duration;
                }
            } catch (\Exception $e) {
                // Fall through to next method
            }
        }

        // Priority 2: Look up day-of-week specific schedule
        if ($attendance->school_class_id && $attendance->date) {
            $dayOfWeek = $attendance->date->dayOfWeek; // 0=Sunday, 1=Monday, etc.

            $schedule = ClassSchedule::where('school_class_id', $attendance->school_class_id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->first();

            if ($schedule && $schedule->start_time && $schedule->end_time) {
                try {
                    $start = Carbon::parse($schedule->start_time);
                    $end = Carbon::parse($schedule->end_time);
                    $duration = $start->diffInMinutes($end, false);
                    if ($duration > 0) {
                        return $duration;
                    }
                } catch (\Exception $e) {
                    // Fall through to fallback
                }
            }
        }

        // Priority 3: Fallback to 60 minutes
        return 60;
    }

    // Calculate for all students in a class
    public static function calculateForClass($classId, $periodType = 'monthly', $startDate = null, $endDate = null)
    {
        $class = SchoolClass::with('students')->find($classId);
        if (!$class) return [];

        $results = [];
        foreach ($class->students as $student) {
            $results[] = static::calculateForStudent($student->id, $classId, $periodType, $startDate, $endDate);
        }
        return $results;
    }

    // Calculate for all students school-wide (overall stats)
    public static function calculateOverall($periodType = 'monthly', $startDate = null, $endDate = null)
    {
        $students = Student::all();
        $results = [];
        foreach ($students as $student) {
            $results[] = static::calculateForStudent($student->id, null, $periodType, $startDate, $endDate);
        }
        return $results;
    }

    /**
     * Get a student's attendance breakdown by class (session)
     * Returns stats for each class the student is enrolled in
     */
    public static function getStudentBreakdownByClass($studentId, $periodType = 'monthly', $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        return static::where('student_id', $studentId)
            ->whereNotNull('school_class_id')
            ->where('period_type', $periodType)
            ->where('period_start', $startDate)
            ->with('schoolClass')
            ->orderBy('school_class_id')
            ->get()
            ->map(function ($stat) {
                return [
                    'class_id' => $stat->school_class_id,
                    'class_name' => $stat->schoolClass->name ?? 'Unknown',
                    'schedule_time' => $stat->schoolClass->schedule_time ?? '',
                    'total_days' => $stat->total_days,
                    'present_days' => $stat->present_days,
                    'late_days' => $stat->late_days,
                    'absent_days' => $stat->absent_days,
                    'excused_days' => $stat->excused_days,
                    'left_early_days' => $stat->left_early_days,
                    'total_minutes_late' => $stat->total_minutes_late,
                    'attendance_percentage' => $stat->attendance_percentage,
                ];
            });
    }

    /**
     * Get formatted report data for a student
     * Includes student info + per-class breakdown
     */
    public static function getStudentReport($studentId, $periodType = 'monthly', $startDate = null, $endDate = null)
    {
        $student = Student::find($studentId);
        if (!$student) return null;

        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $breakdown = static::getStudentBreakdownByClass($studentId, $periodType, $startDate, $endDate);
        
        // Calculate overall totals
        $totals = [
            'total_days' => $breakdown->sum('total_days'),
            'present_days' => $breakdown->sum('present_days'),
            'late_days' => $breakdown->sum('late_days'),
            'absent_days' => $breakdown->sum('absent_days'),
            'excused_days' => $breakdown->sum('excused_days'),
            'left_early_days' => $breakdown->sum('left_early_days'),
            'total_minutes_late' => $breakdown->sum('total_minutes_late'),
        ];
        $totals['overall_percentage'] = $totals['total_days'] > 0
            ? round(($totals['present_days'] + $totals['late_days']) / $totals['total_days'] * 100, 2)
            : 0;

        return [
            'student_id' => $student->id,
            'student_name' => $student->first_name . ' ' . $student->last_name,
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'classes' => $breakdown->toArray(),
            'totals' => $totals,
        ];
    }
}
