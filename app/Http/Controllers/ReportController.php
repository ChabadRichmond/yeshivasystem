<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\ClassCancellation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Policies\AttendancePolicy;
use Carbon\Carbon;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view attendance', only: ['index', 'attendance', 'attendanceStats']),
        ];
    }

    /**
     * Reports dashboard - shows available report types
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Attendance report view
     */
    public function attendance(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $classId = $request->filled('class_id') ? (int) $request->get('class_id') : null;
        $sort = $request->get('sort', 'name'); // name, name_desc, percentage_asc, percentage_desc

        $classes = SchoolClass::active()->orderBy('name')->get();

        // Apply teacher scoping - teachers only see their own classes in reports
        $user = auth()->user();
        $allowedClassIds = AttendancePolicy::getReportableClassIds($user);

        // Filter class dropdown for teachers
        if ($allowedClassIds !== null) {
            $classes = $classes->whereIn('id', $allowedClassIds);
        }

        // Get cancelled sessions as a lookup set for fast filtering (performance optimization)
        $cancelledSessionKeys = ClassCancellation::whereBetween('cancelled_date', [$startDate, $endDate])
            ->select('school_class_id', 'cancelled_date')
            ->get()
            ->map(fn($c) => $c->school_class_id . '-' . $c->cancelled_date->format('Y-m-d'))
            ->flip()
            ->toArray();

        $query = Attendance::with(['student.academicGrade', 'schoolClass.schedules'])
            ->whereBetween('date', [$startDate, $endDate]);

        // Scope query to allowed classes
        if ($allowedClassIds !== null) {
            $query->whereIn('school_class_id', $allowedClassIds);
        }

        if ($classId) {
            // Verify teacher has access to this class
            if ($allowedClassIds !== null && !in_array($classId, $allowedClassIds)) {
                abort(403, 'You do not have access to reports for this class.');
            }
            $query->where('school_class_id', $classId);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        // Filter out cancelled sessions in PHP (much faster than whereNotExists subquery)
        if (!empty($cancelledSessionKeys)) {
            $attendances = $attendances->reject(function ($a) use ($cancelledSessionKeys) {
                $key = $a->school_class_id . '-' . $a->date->format('Y-m-d');
                return isset($cancelledSessionKeys[$key]);
            });
        }

        // Get students - scoped by class if selected, or all active students
        // For teachers: filter to only their primary students
        $accessibleStudentIds = null;
        if ($user && $user->hasRole('Teacher')) {
            $accessibleStudentIds = $user->getPrimaryStudentIds();
        }

        if ($classId) {
            $studentsQuery = Student::whereHas('classes', function ($q) use ($classId) {
                $q->where('school_classes.id', $classId);
            })
            ->where('enrollment_status', 'active');

            if ($accessibleStudentIds !== null) {
                $studentsQuery->whereIn('id', $accessibleStudentIds);
            }

            $students = $studentsQuery
                ->with('academicGrade')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        } else {
            $studentsQuery = Student::where('enrollment_status', 'active');

            if ($accessibleStudentIds !== null) {
                $studentsQuery->whereIn('id', $accessibleStudentIds);
            }

            $students = $studentsQuery
                ->with('academicGrade')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        // Filter attendance records to only show primary students for teachers
        if ($accessibleStudentIds !== null) {
            $attendances = $attendances->whereIn('student_id', $accessibleStudentIds);
        }

        // Calculate total minutes missed for each student (using day-specific durations)
        $studentMinutesMissed = [];
        $attendancesByStudent = $attendances->groupBy('student_id');

        foreach ($students as $student) {
            $studentAttendances = $attendancesByStudent->get($student->id, collect());
            $totalMinutesMissed = 0;

            foreach ($studentAttendances as $a) {
                // Get duration for this specific attendance record (day-specific or custom override)
                $duration = $this->getClassDurationForAttendance($a);

                if ($a->status === 'absent_excused') {
                    // Excused absences don't count as missed time
                    continue;
                } elseif ($a->status === 'late_excused') {
                    // Late excused doesn't count as missed time (per item 14)
                    continue;
                } elseif (str_starts_with($a->status, 'absent')) {
                    // Regular absent - count full class duration
                    $totalMinutesMissed += $duration;
                } elseif (str_starts_with($a->status, 'late')) {
                    // Regular late - count late minutes
                    $totalMinutesMissed += ($a->minutes_late ?? 0);
                }
            }

            $studentMinutesMissed[$student->id] = $totalMinutesMissed;
        }

        // Pre-calculate student stats to avoid calculations in view (performance optimization)
        $studentStats = [];
        foreach ($students as $student) {
            $studentAttendances = $attendancesByStudent->get($student->id, collect());

            $present = $studentAttendances->where('status', 'present')->count();
            $late = $studentAttendances->whereIn('status', ['late', 'late_excused', 'late_unexcused'])->count();
            $absent = $studentAttendances->whereIn('status', ['absent', 'absent_excused', 'absent_unexcused'])->count();
            $excused = $studentAttendances->whereIn('status', ['late_excused', 'absent_excused'])->count();
            $leftEarly = $studentAttendances->where('left_early', true)->count();

            // Calculate time-based percentage
            $totalPossible = 0;
            $totalAttended = 0;

            foreach ($studentAttendances as $a) {
                // Get duration for this specific attendance record (day-specific or custom override)
                $duration = $this->getClassDurationForAttendance($a);

                if ($a->status === 'absent_excused') {
                    continue;
                } elseif ($a->status === 'late_excused') {
                    $lateMinutes = $a->minutes_late ?? 0;
                    $adjustedDuration = max(0, $duration - $lateMinutes);
                    $totalPossible += $adjustedDuration;
                    $totalAttended += $adjustedDuration;
                } elseif (str_starts_with($a->status, 'absent')) {
                    $totalPossible += $duration;
                } elseif (str_starts_with($a->status, 'late')) {
                    $totalPossible += $duration;
                    $totalAttended += max(0, $duration - ($a->minutes_late ?? 0));
                } else {
                    $totalPossible += $duration;
                    $totalAttended += $duration;
                }
            }

            $rate = $totalPossible > 0 ? round(($totalAttended / $totalPossible) * 100) : 0;

            $studentStats[$student->id] = [
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'excused' => $excused,
                'left_early' => $leftEarly,
                'rate' => $rate,
            ];
        }

        // Statistics
        $statsQuery = Attendance::whereBetween('date', [$startDate, $endDate]);
        if ($allowedClassIds !== null) {
            $statsQuery->whereIn('school_class_id', $allowedClassIds);
        }
        if ($classId) {
            $statsQuery->where('school_class_id', $classId);
        }

        $stats = [
            'present' => (clone $statsQuery)->where('status', 'present')->count(),
            'late' => (clone $statsQuery)->whereIn('status', ['late_excused', 'late_unexcused'])->count(),
            'absent' => (clone $statsQuery)->whereIn('status', ['absent_excused', 'absent_unexcused'])->count(),
        ];

        // Apply sorting
        $students = $students->sortBy(function ($student) use ($sort, $studentStats) {
            switch ($sort) {
                case 'name_desc':
                    return $student->last_name;
                case 'percentage_asc':
                    return $studentStats[$student->id]['rate'] ?? 0;
                case 'percentage_desc':
                    return -($studentStats[$student->id]['rate'] ?? 0);
                default: // 'name'
                    return $student->last_name;
            }
        });

        // Reverse for descending name sort
        if ($sort === 'name_desc') {
            $students = $students->reverse();
        }

        return view('reports.attendance', compact(
            'attendances', 'stats', 'startDate', 'endDate', 'classes', 'classId', 'students', 'studentMinutesMissed', 'studentStats', 'sort'
        ));
    }

    /**
     * Stats-based attendance report with time percentages
     */
    public function attendanceStats(Request $request)
    {
        $period = $request->get('period', 'month'); // day, week, month, custom
        $studentId = $request->filled('student_id') ? (int) $request->get('student_id') : null;
        $classId = $request->filled('class_id') ? (int) $request->get('class_id') : null;
        $sort = $request->get('sort', 'name'); // name, percentage_asc, percentage_desc
        $viewMode = $request->get('view', 'summary'); // summary or history

        // Calculate date range based on period
        switch ($period) {
            case 'day':
                $startDate = Carbon::parse($request->get('date', now()->format('Y-m-d')));
                $endDate = $startDate->copy();
                break;
            case 'week':
                // Week starts on Sunday - always calculate current week, ignore start_date param
                $startDate = now()->startOfWeek(Carbon::SUNDAY);
                $endDate = now()->endOfWeek(Carbon::SATURDAY);
                break;
            case 'month':
                $startDate = Carbon::parse($request->get('start_date', now()->startOfMonth()->format('Y-m-d')));
                $endDate = $startDate->copy()->endOfMonth();
                break;
            case 'custom':
                $startDate = Carbon::parse($request->get('start_date', now()->startOfMonth()->format('Y-m-d')));
                $endDate = Carbon::parse($request->get('end_date', now()->format('Y-m-d')));
                break;
            default:
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
        }

        $classes = SchoolClass::active()->orderBy('display_order')->orderBy('name')->get();

        // Get cancelled sessions as a lookup set for fast filtering (performance optimization)
        $cancelledSessionKeys = ClassCancellation::whereBetween('cancelled_date', [$startDate, $endDate])
            ->select('school_class_id', 'cancelled_date')
            ->get()
            ->map(fn($c) => $c->school_class_id . '-' . $c->cancelled_date->format('Y-m-d'))
            ->flip()
            ->toArray();

        // Filter students by primary teacher access for teachers
        $user = auth()->user();
        $studentsQuery = Student::where('enrollment_status', 'active');
        if ($user && $user->hasRole('Teacher')) {
            $accessibleStudentIds = $user->getPrimaryStudentIds();
            if (!empty($accessibleStudentIds)) {
                $studentsQuery->whereIn('id', $accessibleStudentIds);
            }
        }
        $students = $studentsQuery->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Build stats data
        $statsData = [];
        $historyData = null;
        $dates = [];
        $studentPermissions = collect();

        if ($studentId) {
            // Single student report - show all classes
            $student = Student::find($studentId);
            if ($student) {
                $statsData = $this->getStudentStats($studentId, $startDate, $endDate, $cancelledSessionKeys);

                // Get daily history for student (grid view like the image)
                $historyData = $this->getStudentDailyHistory($studentId, $startDate, $endDate);
                $dates = $this->getDateRange($startDate, $endDate);

                // Get student permissions that overlap with the date range (for class-based checking in view)
                $studentPermissions = \App\Models\StudentPermission::where('student_id', $studentId)
                    ->overlapsDateRange($startDate, $endDate)
                    ->with(['firstExcusedClass', 'lastExcusedClass'])
                    ->get();
            }
        } elseif ($classId) {
            // Single class report - show all students in that class (optimized with bulk loading)
            $classStudents = Student::whereHas('classes', fn($q) => $q->where('school_classes.id', $classId))
                ->where('enrollment_status', 'active')
                ->orderBy('last_name')
                ->get();

            // Bulk load ALL attendance data for this class
            $allAttendancesRaw = Attendance::where('school_class_id', $classId)
                ->whereIn('student_id', $classStudents->pluck('id'))
                ->whereBetween('date', [$startDate, $endDate])
                ->with('schoolClass.schedules')
                ->get();

            // Filter out cancelled sessions in PHP (much faster than whereNotExists subquery)
            if (!empty($cancelledSessionKeys)) {
                $allAttendancesRaw = $allAttendancesRaw->reject(function ($a) use ($cancelledSessionKeys) {
                    $key = $a->school_class_id . '-' . $a->date->format('Y-m-d');
                    return isset($cancelledSessionKeys[$key]);
                });
            }

            $allAttendances = $allAttendancesRaw->groupBy('student_id');

            // Get class duration once
            $classDuration = $this->getClassDurationsMap([$classId])[$classId] ?? 60;

            // Process each student from cached data
            foreach ($classStudents as $student) {
                $studentAttendances = $allAttendances->get($student->id, collect());
                $statsData[] = $this->calculateStatsFromAttendances($student, $studentAttendances, $classDuration);
            }
        } else {
            // All students summary - show all students even if no attendance (per item 16) (optimized with bulk loading)
            $studentIds = $students->pluck('id');

            // Bulk load ALL attendance data
            $allAttendancesRaw = Attendance::whereIn('student_id', $studentIds)
                ->whereBetween('date', [$startDate, $endDate])
                ->with('schoolClass.schedules')
                ->get();

            // Filter out cancelled sessions in PHP (much faster than whereNotExists subquery)
            if (!empty($cancelledSessionKeys)) {
                $allAttendancesRaw = $allAttendancesRaw->reject(function ($a) use ($cancelledSessionKeys) {
                    $key = $a->school_class_id . '-' . $a->date->format('Y-m-d');
                    return isset($cancelledSessionKeys[$key]);
                });
            }

            $allAttendances = $allAttendancesRaw->groupBy('student_id');

            // Get all class durations once
            $classDurations = $this->getClassDurationsMap();

            // Process each student from cached data
            foreach ($students as $student) {
                $studentAttendances = $allAttendances->get($student->id, collect());
                $statsData[] = $this->calculateOverallStatsFromAttendances($student, $studentAttendances, $classDurations);
            }
        }

        // Apply sorting for all-students and class views
        if (!$studentId && is_array($statsData) && count($statsData) > 0) {
            usort($statsData, function ($a, $b) use ($sort) {
                switch ($sort) {
                    case 'percentage_asc':
                        $pctA = $a['percentage'] ?? $a['stats']['percentage'] ?? 0;
                        $pctB = $b['percentage'] ?? $b['stats']['percentage'] ?? 0;
                        return $pctA <=> $pctB;
                    case 'percentage_desc':
                        $pctA = $a['percentage'] ?? $a['stats']['percentage'] ?? 0;
                        $pctB = $b['percentage'] ?? $b['stats']['percentage'] ?? 0;
                        return $pctB <=> $pctA;
                    default: // name
                        return ($a['student']->last_name ?? '') <=> ($b['student']->last_name ?? '');
                }
            });
        }

        return view('reports.attendance-stats', compact(
            'period', 'startDate', 'endDate', 'classes', 'students',
            'studentId', 'classId', 'statsData', 'sort', 'viewMode', 'historyData', 'dates',
            'cancelledSessionKeys', 'studentPermissions'
        ));
    }

    /**
     * Get daily attendance history for a student (grid format) - optimized with bulk loading
     */
    private function getStudentDailyHistory($studentId, $startDate, $endDate)
    {
        $student = Student::find($studentId);
        $classes = $student->classes()->with('schedules')->orderBy('display_order')->get();

        // Bulk load ALL attendance data for this student (optimized)
        $allAttendances = Attendance::where('student_id', $studentId)
            ->whereIn('school_class_id', $classes->pluck('id'))
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('school_class_id');

        $history = [];

        foreach ($classes as $class) {
            $attendances = $allAttendances->get($class->id, collect())
                ->keyBy(fn($a) => $a->date->format('Y-m-d'));

            // Calculate totals for this class
            $present = $attendances->filter(fn($a) => str_starts_with($a->status, 'present'))->count();
            $late = $attendances->filter(fn($a) => str_starts_with($a->status, 'late'))->count();
            $absent = $attendances->filter(fn($a) => str_starts_with($a->status, 'absent'))->count();

            $history[] = [
                'class' => $class,
                'attendances' => $attendances,
                'totals' => [
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                ],
            ];
        }

        return $history;
    }

    /**
     * Get array of dates for the range
     */
    private function getDateRange($startDate, $endDate)
    {
        $dates = [];
        $current = $endDate->copy();
        while ($current >= $startDate) {
            $dates[] = $current->copy();
            $current->subDay();
        }
        return $dates;
    }

    private function getStudentStats($studentId, $startDate, $endDate, array $cancelledSessionKeys = [])
    {
        $student = Student::find($studentId);
        $classes = $student->classes()->get();

        // Bulk load ALL attendance data for this student (optimized)
        $allAttendancesRaw = Attendance::where('student_id', $studentId)
            ->whereIn('school_class_id', $classes->pluck('id'))
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schoolClass.schedules')
            ->get();

        // Filter out cancelled sessions (same as all-students view)
        if (!empty($cancelledSessionKeys)) {
            $allAttendancesRaw = $allAttendancesRaw->reject(function ($a) use ($cancelledSessionKeys) {
                $key = $a->school_class_id . '-' . $a->date->format('Y-m-d');
                return isset($cancelledSessionKeys[$key]);
            });
        }

        $allAttendances = $allAttendancesRaw;
        $attendancesByClass = $allAttendances->groupBy('school_class_id');

        // Get class durations once
        $classDurations = $this->getClassDurationsMap($classes->pluck('id')->toArray());

        $classStats = [];
        foreach ($classes as $class) {
            $attendances = $attendancesByClass->get($class->id, collect());
            $duration = $classDurations[$class->id] ?? 60;

            $classStats[] = $this->calculateTimeBasedStatsFromAttendances($class, $attendances, $duration);
        }

        // Calculate aggregate overall percentage (same method as all-students view)
        // This avoids rounding errors from weighted averaging pre-rounded percentages
        $totalPossible = 0;
        $totalAttended = 0;

        foreach ($allAttendances as $a) {
            $duration = $this->getClassDurationForAttendance($a);

            if ($a->status === 'absent_excused') {
                continue;
            } elseif ($a->status === 'late_excused') {
                $lateMinutes = $a->minutes_late ?? 0;
                $adjustedDuration = max(0, $duration - $lateMinutes);
                $totalPossible += $adjustedDuration;
                $totalAttended += $adjustedDuration;
            } elseif (str_starts_with($a->status, 'absent')) {
                $totalPossible += $duration;
            } elseif (str_starts_with($a->status, 'late')) {
                $totalPossible += $duration;
                $totalAttended += max(0, $duration - ($a->minutes_late ?? 0));
            } else {
                $totalPossible += $duration;
                $totalAttended += $duration;
            }
        }

        $overallPercentage = $totalPossible > 0 ? round(($totalAttended / $totalPossible) * 100, 1) : 0;

        return [
            'student' => $student,
            'classes' => $classStats,
            'overall_percentage' => $overallPercentage,
        ];
    }

    private function getStudentClassStats($studentId, $classId, $startDate, $endDate)
    {
        $student = Student::find($studentId);
        $class = SchoolClass::find($classId);
        $stats = $this->calculateTimeBasedStats($studentId, $classId, $startDate, $endDate, $class);

        return [
            'student' => $student,
            'stats' => $stats,
        ];
    }

    private function getStudentOverallStats($studentId, $startDate, $endDate)
    {
        $student = Student::find($studentId);
        $attendances = Attendance::where('student_id', $studentId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schoolClass.schedules')
            ->get();

        // Show student even if no attendance records (per item 16)
        if ($attendances->isEmpty()) {
            return [
                'student' => $student,
                'total_sessions' => 0,
                'present' => 0,
                'late' => 0,
                'absent' => 0,
                'percentage' => 0, // Show 0% instead of hiding student
            ];
        }

        $totalPossible = 0;
        $totalAttended = 0;
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;

        foreach ($attendances as $a) {
            $duration = $this->getClassDuration($a->schoolClass);

            // Handle excused statuses differently
            if ($a->status === 'absent_excused') {
                // Excused absences: Don't count at all - exclude from calculation entirely
                continue;
            } elseif ($a->status === 'late_excused') {
                // Late excused: Subtract late minutes from class duration for calculation
                $lateMinutes = $a->minutes_late ?? 0;
                $adjustedDuration = max(0, $duration - $lateMinutes);
                $totalPossible += $adjustedDuration;
                $totalAttended += $adjustedDuration; // They attended the remaining time
                $lateCount++;
            } elseif (str_starts_with($a->status, 'absent')) {
                // Regular absent (unexcused)
                $totalPossible += $duration;
                $absentCount++;
            } elseif (str_starts_with($a->status, 'late')) {
                // Regular late (unexcused)
                $totalPossible += $duration;
                $lateCount++;
                $totalAttended += max(0, $duration - ($a->minutes_late ?? 0));
            } else {
                // Present
                $totalPossible += $duration;
                $presentCount++;
                $totalAttended += $duration;
            }
        }

        return [
            'student' => $student,
            'total_sessions' => $attendances->count(),
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'percentage' => $totalPossible > 0 ? round(($totalAttended / $totalPossible) * 100, 1) : 0,
        ];
    }

    private function calculateTimeBasedStats($studentId, $classId, $startDate, $endDate, $class)
    {
        $attendances = Attendance::where('student_id', $studentId)
            ->where('school_class_id', $classId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $duration = $this->getClassDuration($class);
        $totalPossible = 0; // Don't pre-calculate, will add per attendance
        $totalAttended = 0;
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        $totalMinutesLate = 0;
        $totalMinutesMissed = 0; // Includes both late minutes and full absent class time

        foreach ($attendances as $a) {
            // Handle excused statuses differently
            if ($a->status === 'absent_excused') {
                // Excused absences: Don't count at all - exclude from calculation entirely
                continue;
            } elseif ($a->status === 'late_excused') {
                // Late excused: Subtract late minutes from class duration for calculation
                $lateMinutes = $a->minutes_late ?? 0;
                $adjustedDuration = max(0, $duration - $lateMinutes);
                $totalPossible += $adjustedDuration;
                $totalAttended += $adjustedDuration; // They attended the remaining time
                $lateCount++;
                $totalMinutesLate += $lateMinutes;
            } elseif (str_starts_with($a->status, 'absent')) {
                // Regular absent (unexcused)
                $totalPossible += $duration;
                $absentCount++;
                $totalMinutesMissed += $duration;
            } elseif (str_starts_with($a->status, 'late')) {
                // Regular late (unexcused)
                $totalPossible += $duration;
                $lateCount++;
                $lateMinutes = $a->minutes_late ?? 0;
                $totalMinutesLate += $lateMinutes;
                $totalMinutesMissed += $lateMinutes;
                $totalAttended += max(0, $duration - $lateMinutes);
            } else {
                // Present
                $totalPossible += $duration;
                $presentCount++;
                $totalAttended += $duration;
            }
        }

        return [
            'class' => $class,
            'class_name' => $class->name,
            'duration_minutes' => $duration,
            'total_sessions' => $attendances->count(),
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'total_minutes_late' => $totalMinutesLate,
            'total_minutes_missed' => $totalMinutesMissed, // New field: late + absent time
            'percentage' => $totalPossible > 0 ? round(($totalAttended / $totalPossible) * 100, 1) : 0,
        ];
    }

    /**
     * Export attendance data to CSV
     */
    public function exportAttendance(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $classId = $request->filled('class_id') ? (int) $request->get('class_id') : null;

        $query = Attendance::with(['student', 'schoolClass'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('school_class_id');

        if ($classId) {
            $query->where('school_class_id', $classId);
        }

        $attendances = $query->get();

        $filename = 'attendance_' . $startDate . '_to_' . $endDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($attendances) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for proper Hebrew character display in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($file, [
                'Date',
                'Class/Session',
                'Student Last Name',
                'Student First Name',
                'Status',
                'Minutes Late',
                'Excused',
                'Left Early',
                'Note'
            ]);

            foreach ($attendances as $a) {
                $status = match (true) {
                    $a->status === 'present' => 'Present',
                    str_starts_with($a->status, 'late') => 'Late',
                    str_starts_with($a->status, 'absent') => 'Absent',
                    default => $a->status,
                };

                $excused = str_contains($a->status, 'excused') ? 'Yes' : 'No';

                fputcsv($file, [
                    $a->date->format('Y-m-d'),
                    $a->schoolClass?->name ?? '',
                    $a->student?->last_name ?? '',
                    $a->student?->first_name ?? '',
                    $status,
                    $a->minutes_late ?? '',
                    $excused,
                    $a->left_early ? 'Yes' : 'No',
                    $a->note ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getClassDuration($class)
    {
        if (!$class) {
            return 60;
        }

        try {
            // Use pre-loaded schedules if available, otherwise query
            $schedule = $class->relationLoaded('schedules')
                ? $class->schedules->first()
                : $class->schedules()->first();

            if ($schedule && $schedule->start_time && $schedule->end_time) {
                $start = Carbon::parse($schedule->start_time);
                $end = Carbon::parse($schedule->end_time);
                $duration = abs($end->diffInMinutes($start, false));
                if ($duration > 0) {
                    return $duration;
                }
            }

            // Fallback to default 60 minutes
            return 60;
        } catch (\Exception $e) {
            return 60;
        }
    }

    /**
     * Get class durations for multiple classes in a single query.
     * Returns array keyed by class_id with duration in minutes.
     * Cached for 1 hour to improve performance.
     *
     * @param array|null $classIds Optional array of class IDs to filter by
     * @return array Array keyed by class_id with duration values
     */
    private function getClassDurationsMap(?array $classIds = null): array
    {
        $cacheKey = 'class_durations_' . md5(json_encode($classIds ?? 'all'));

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($classIds) {
            $query = \Illuminate\Support\Facades\DB::table('school_classes as c')
                ->leftJoin('class_schedules as cs', function($join) {
                    $join->on('c.id', '=', 'cs.school_class_id')
                         ->where('cs.is_active', true);
                })
                ->select('c.id as class_id')
                ->selectRaw('COALESCE(
                    AVG(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)),
                    60
                ) as duration');

            if ($classIds !== null && count($classIds) > 0) {
                $query->whereIn('c.id', $classIds);
            }

            return $query->groupBy('c.id')
                ->pluck('duration', 'class_id')
                ->map(fn($d) => (int)$d)
                ->toArray();
        });
    }

    /**
     * Get the class duration for a specific attendance record.
     * Priority order:
     * 1. Use class_start_time and class_end_time from the attendance record (date-specific override)
     * 2. Look up day-of-week specific schedule from class_schedules
     * 3. Fallback to 60 minutes
     *
     * @param \App\Models\Attendance $attendance The attendance record
     * @return int Duration in minutes
     */
    private function getClassDurationForAttendance($attendance): int
    {
        // Priority 1: Check if attendance has custom start/end times
        if ($attendance->class_start_time && $attendance->class_end_time) {
            try {
                $start = \Carbon\Carbon::parse($attendance->class_start_time);
                $end = \Carbon\Carbon::parse($attendance->class_end_time);
                $duration = $end->diffInMinutes($start, false);
                if ($duration > 0) {
                    return $duration;
                }
            } catch (\Exception $e) {
                // Fall through to next method
            }
        }

        // Priority 2: Use eager-loaded schedules from schoolClass relationship (no extra query)
        if ($attendance->school_class_id && $attendance->date) {
            $dayOfWeek = $attendance->date->dayOfWeek; // 0=Sunday, 1=Monday, etc.
            $schedule = null;

            // Try to use pre-loaded relationship first (fast path)
            if ($attendance->relationLoaded('schoolClass') && $attendance->schoolClass && $attendance->schoolClass->relationLoaded('schedules')) {
                $schedule = $attendance->schoolClass->schedules
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->first();
            } else {
                // Fallback to database query if relationship not loaded (ensures correct data)
                $schedule = \App\Models\ClassSchedule::where('school_class_id', $attendance->school_class_id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->first();
            }

            if ($schedule && $schedule->start_time && $schedule->end_time) {
                try {
                    $start = \Carbon\Carbon::parse($schedule->start_time);
                    $end = \Carbon\Carbon::parse($schedule->end_time);
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

    /**
     * Calculate stats from pre-loaded attendance data (single class).
     * Replaces getStudentClassStats to avoid per-student queries.
     *
     * @param Student $student The student object
     * @param \Illuminate\Support\Collection $attendances Pre-loaded attendance records
     * @param int $classDuration Duration of the class in minutes
     * @return array Stats array matching view expectations
     */
    private function calculateStatsFromAttendances($student, $attendances, $classDuration)
    {
        $totalPossible = 0;
        $totalAttended = 0;
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        $totalMinutesLate = 0;
        $totalMinutesMissed = 0;

        foreach ($attendances as $a) {
            // Get duration for this specific attendance record (day-specific or custom override)
            $duration = $this->getClassDurationForAttendance($a);

            if ($a->status === 'absent_excused') {
                // Excused absences don't count toward minutes missed
                continue;
            } elseif ($a->status === 'late_excused') {
                $lateMinutes = $a->minutes_late ?? 0;
                $adjustedDuration = max(0, $duration - $lateMinutes);
                $totalPossible += $adjustedDuration;
                $totalAttended += $adjustedDuration;
                $lateCount++;
                $totalMinutesLate += $lateMinutes;
                // Excused lates don't count toward minutes missed
            } elseif (str_starts_with($a->status, 'absent')) {
                $totalPossible += $duration;
                $absentCount++;
                // Unexcused absent = full class duration missed
                $totalMinutesMissed += $duration;
            } elseif (str_starts_with($a->status, 'late')) {
                $totalPossible += $duration;
                $lateCount++;
                $lateMinutes = $a->minutes_late ?? 0;
                $totalMinutesLate += $lateMinutes;
                $totalAttended += max(0, $duration - $lateMinutes);
                // Unexcused late = late minutes missed
                $totalMinutesMissed += $lateMinutes;
            } else {
                $totalPossible += $duration;
                $presentCount++;
                $totalAttended += $duration;
            }
        }

        return [
            'student' => $student,
            'stats' => [
                'total_sessions' => $attendances->count(),
                'present' => $presentCount,
                'late' => $lateCount,
                'absent' => $absentCount,
                'total_minutes_late' => $totalMinutesLate,
                'total_minutes_missed' => $totalMinutesMissed,
                'percentage' => $totalPossible > 0 ? round(($totalAttended / $totalPossible) * 100, 1) : 0,
            ],
        ];
    }

    /**
     * Calculate overall stats from pre-loaded attendance data (all classes).
     * Replaces getStudentOverallStats to avoid per-student queries.
     *
     * @param Student $student The student object
     * @param \Illuminate\Support\Collection $attendances Pre-loaded attendance records
     * @param array $classDurations Array of class durations keyed by class_id
     * @return array Stats array matching view expectations
     */
    private function calculateOverallStatsFromAttendances($student, $attendances, $classDurations)
    {
        if ($attendances->isEmpty()) {
            return [
                'student' => $student,
                'total_sessions' => 0,
                'present' => 0,
                'late' => 0,
                'absent' => 0,
                'total_minutes_missed' => 0,
                'percentage' => 0,
            ];
        }

        $totalPossible = 0;
        $totalAttended = 0;
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        $totalMinutesMissed = 0;

        foreach ($attendances as $a) {
            // Get duration for this specific attendance record (day-specific or custom override)
            $duration = $this->getClassDurationForAttendance($a);

            if ($a->status === 'absent_excused') {
                // Excused absences don't count toward minutes missed
                continue;
            } elseif ($a->status === 'late_excused') {
                $lateMinutes = $a->minutes_late ?? 0;
                $adjustedDuration = max(0, $duration - $lateMinutes);
                $totalPossible += $adjustedDuration;
                $totalAttended += $adjustedDuration;
                $lateCount++;
                // Excused lates don't count toward minutes missed
            } elseif (str_starts_with($a->status, 'absent')) {
                $totalPossible += $duration;
                $absentCount++;
                // Unexcused absent = full class duration missed
                $totalMinutesMissed += $duration;
            } elseif (str_starts_with($a->status, 'late')) {
                $totalPossible += $duration;
                $lateCount++;
                $lateMinutes = $a->minutes_late ?? 0;
                $totalAttended += max(0, $duration - $lateMinutes);
                // Unexcused late = late minutes missed
                $totalMinutesMissed += $lateMinutes;
            } else {
                $totalPossible += $duration;
                $presentCount++;
                $totalAttended += $duration;
            }
        }

        return [
            'student' => $student,
            'total_sessions' => $attendances->count(),
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'total_minutes_missed' => $totalMinutesMissed,
            'percentage' => $totalPossible > 0 ? round(($totalAttended / $totalPossible) * 100, 1) : 0,
        ];
    }

    /**
     * Calculate time-based stats from pre-loaded attendance data.
     * Replaces calculateTimeBasedStats to avoid per-class queries.
     *
     * @param SchoolClass $class The class object
     * @param \Illuminate\Support\Collection $attendances Pre-loaded attendance records
     * @param int $duration Duration of the class in minutes
     * @return array Stats array matching view expectations
     */
    private function calculateTimeBasedStatsFromAttendances($class, $attendances, $duration)
    {
        $totalPossible = 0;
        $totalAttended = 0;
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        $totalMinutesLate = 0;
        $totalMinutesMissed = 0;

        foreach ($attendances as $a) {
            // Get duration for this specific attendance record (day-specific or custom override)
            $actualDuration = $this->getClassDurationForAttendance($a);

            if ($a->status === 'absent_excused') {
                continue;
            } elseif ($a->status === 'late_excused') {
                $lateMinutes = $a->minutes_late ?? 0;
                $adjustedDuration = max(0, $actualDuration - $lateMinutes);
                $totalPossible += $adjustedDuration;
                $totalAttended += $adjustedDuration;
                $lateCount++;
                $totalMinutesLate += $lateMinutes;
            } elseif (str_starts_with($a->status, 'absent')) {
                $totalPossible += $actualDuration;
                $absentCount++;
                $totalMinutesMissed += $actualDuration;
            } elseif (str_starts_with($a->status, 'late')) {
                $totalPossible += $actualDuration;
                $lateCount++;
                $lateMinutes = $a->minutes_late ?? 0;
                $totalMinutesLate += $lateMinutes;
                $totalMinutesMissed += $lateMinutes;
                $totalAttended += max(0, $actualDuration - $lateMinutes);
            } else {
                $totalPossible += $actualDuration;
                $presentCount++;
                $totalAttended += $actualDuration;
            }
        }

        return [
            'class' => $class,
            'class_name' => $class->name,
            'duration_minutes' => $duration,
            'total_sessions' => $attendances->count(),
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'total_minutes_late' => $totalMinutesLate,
            'total_minutes_missed' => $totalMinutesMissed,
            'percentage' => $totalPossible > 0 ? round(($totalAttended / $totalPossible) * 100, 1) : 0,
        ];
    }
}
