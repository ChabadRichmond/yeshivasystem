<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\ClassCancellation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Policies\AttendancePolicy;
use Carbon\Carbon;

class AttendanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view attendance', only: ['index']),
            new Middleware('permission:mark attendance|manage attendance', only: ['store', 'update']),
        ];
    }

    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $classId = $request->get('class_id');
        
        // Use app configured timezone for school operations
        $tz = config('app.timezone');
        $now = now($tz);
        
        // Use selected date's day of week (not today's) for schedule lookup
        $selectedDate = Carbon::parse($date, $tz);
        $selectedDayOfWeek = $selectedDate->dayOfWeek; // 0=Sunday, 6=Saturday
        $currentTime = $now->format('H:i:s');
        
        // Get classes - filter by permission for teachers
        $user = auth()->user();
        $classesQuery = SchoolClass::active()
            ->withCount('students')
            ->with(['schedules' => function ($query) use ($selectedDayOfWeek) {
                $query->where('day_of_week', $selectedDayOfWeek)->where('is_active', true);
            }]);

        // Filter classes for teachers - only show classes they have student assignments in
        if ($user && $user->hasRole('Teacher') && !$user->hasRole(['Super Admin', 'Admin'])) {
            $attendanceClassIds = $user->getAttendanceClassIds();
            if (!empty($attendanceClassIds)) {
                $classesQuery->whereIn('id', $attendanceClassIds);
            } else {
                // Teacher has no class assignments - show empty list
                $classesQuery->whereRaw('1 = 0');
            }
        }

        $classes = $classesQuery->get()
            ->filter(function ($class) {
                // Only include classes that have an active schedule for selected day
                return $class->schedules->isNotEmpty();
            })
            ->sortBy(function ($class) {
                // Sort by selected day's start time
                return $class->schedules->first()->start_time ?? '23:59';
            });
        
        // Auto-select current or next upcoming class if none selected
        if (!$classId && $classes->isNotEmpty()) {
            // Find the class that's currently in session or next upcoming
            $selectedClass = $classes->first(function ($class) use ($currentTime, $now, $tz) {
                $schedule = $class->schedules->first();
                if (!$schedule || !$schedule->start_time) return false;
                
                $classTime = Carbon::parse($schedule->start_time)->format('H:i:s');
                $classStart = Carbon::parse($schedule->start_time, $tz);
                
                // Use schedule end_time if set, otherwise default to 90 minutes
                if ($schedule->end_time) {
                    $classEnd = Carbon::parse($schedule->end_time, $tz);
                } else {
                    $classEnd = $classStart->copy()->addMinutes(90);
                }
                
                // Class is "current" if we're between start and end, or it's upcoming
                return $now->between($classStart, $classEnd) || $classTime >= $currentTime;
            });
            
            // If no upcoming class found, default to the first class for today
            if (!$selectedClass) {
                $selectedClass = $classes->first();
            }
            
            $classId = $selectedClass?->id;
        } else {
            // Get selected class with schedule for selected day
            $selectedClass = $classId ? SchoolClass::with(['schedules' => function ($query) use ($selectedDayOfWeek) {
                $query->where('day_of_week', $selectedDayOfWeek)->where('is_active', true);
            }])->find($classId) : null;
        }
        
        // Get students (filtered by class if selected)
        if ($classId) {
            // Get students the current user can mark attendance for in this class
            $accessibleStudentIds = [];
            $primaryStudentIds = [];
            if ($user && $user->hasRole('Teacher')) {
                $accessibleStudentIds = $user->getAttendanceAccessibleStudentIds($classId);
                $primaryStudentIds = $user->getPrimaryStudentIds();
            }

            // Build students query - filter by permission and sort with primary students first
            $studentsQuery = Student::whereHas('classes', function ($q) use ($classId) {
                $q->where('school_classes.id', $classId);
            })->where('enrollment_status', 'active');

            // For teachers: only show students they can mark attendance for
            if ($user && $user->hasRole('Teacher')) {
                if (!empty($accessibleStudentIds)) {
                    $studentsQuery->whereIn('id', $accessibleStudentIds);

                    // Use CASE to put primary students first, then attendance taker students
                    if (!empty($primaryStudentIds)) {
                        // Use parameter binding for safe SQL
                        $placeholders = implode(',', array_fill(0, count($primaryStudentIds), '?'));
                        $studentsQuery->orderByRaw(
                            "CASE WHEN students.id IN ({$placeholders}) THEN 0 ELSE 1 END",
                            $primaryStudentIds
                        )
                        ->orderBy('last_name')
                        ->orderBy('first_name');
                    } else {
                        $studentsQuery->orderBy('last_name')->orderBy('first_name');
                    }
                } else {
                    // Teacher with no access to any students in this class - show none
                    $studentsQuery->whereRaw('1 = 0');
                }
            } else {
                // Admins see all students
                $studentsQuery->orderBy('last_name')->orderBy('first_name');
            }

            $students = $studentsQuery->get();

            // Mark which students are primary vs attendance-only for the current teacher
            $students->each(function ($student) use ($primaryStudentIds) {
                $student->is_primary = in_array($student->id, $primaryStudentIds);
            });
        } else {
            $students = Student::where('enrollment_status', 'active')
                ->orderBy('last_name')
                ->get();
        }
        
        // Get existing attendance for this date/class
        $attendanceQuery = Attendance::where('date', $date);
        if ($classId) {
            $attendanceQuery->where('school_class_id', $classId);
        }
        $attendances = $attendanceQuery->get()->keyBy('student_id');
        
        // Get class start/end time from today's schedule
        $classStartTime = null;
        $classEndTime = null;
        if ($selectedClass && $selectedClass->schedules->isNotEmpty()) {
            $todaySchedule = $selectedClass->schedules->first();
            $classStartTime = $todaySchedule->start_time ? Carbon::parse($todaySchedule->start_time)->format('H:i') : null;
            $classEndTime = $todaySchedule->end_time ? Carbon::parse($todaySchedule->end_time)->format('H:i') : null;
        }
        
        return view('attendance.index', compact(
            'date', 'classId', 'classes', 'selectedClass',
            'students', 'attendances', 'classStartTime', 'classEndTime'
        ));
    }

    /**
     * Grid view of all classes for a date (Item 21)
     * Shows color-coded class cards based on attendance completion status
     */
    public function grid(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        // Calculate previous/next dates for navigation
        $previousDate = Carbon::parse($date)->subDay()->format('Y-m-d');
        $nextDate = Carbon::parse($date)->addDay()->format('Y-m-d');

        // Get Hebrew date from Hebcal API for proper UTF-8 encoding
        $carbonDate = Carbon::parse($date);
        $hebrewDate = '';

        try {
            $response = Http::timeout(5)
                ->retry(2, 100)
                ->get('https://www.hebcal.com/converter', [
                    'cfg' => 'json',
                    'gy' => $carbonDate->year,
                    'gm' => $carbonDate->month,
                    'gd' => $carbonDate->day,
                    'g2h' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['hebrew'])) {
                    $hebrewDate = $data['hebrew'];
                }
            } else {
                Log::warning('Hebcal API returned non-success status', [
                    'status' => $response->status(),
                    'date' => $date,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Hebcal API call failed', [
                'error' => $e->getMessage(),
                'date' => $date,
            ]);
            // Fallback: leave hebrewDate empty, UI will handle gracefully
        }

        // Use app configured timezone
        $tz = config('app.timezone');
        $now = now($tz);
        $selectedDate = Carbon::parse($date, $tz);
        $selectedDayOfWeek = $selectedDate->dayOfWeek;
        $currentTime = $now->format('H:i:s');
        $isToday = $selectedDate->isSameDay($now);

        // Get classes for this day of week - filtered by permission for teachers
        $user = auth()->user();
        $classesQuery = SchoolClass::active()
            ->with([
                'schedules' => function ($query) use ($selectedDayOfWeek) {
                    $query->where('day_of_week', $selectedDayOfWeek)->where('is_active', true);
                },
                'students' => function ($query) {
                    $query->where('enrollment_status', 'active');
                }
            ]);

        // Filter classes for teachers - only show classes they have student assignments in
        if ($user && $user->hasRole('Teacher') && !$user->hasRole(['Super Admin', 'Admin'])) {
            $attendanceClassIds = $user->getAttendanceClassIds();
            if (!empty($attendanceClassIds)) {
                $classesQuery->whereIn('id', $attendanceClassIds);
            } else {
                // Teacher has no class assignments - show empty list
                $classesQuery->whereRaw('1 = 0');
            }
        }

        $classes = $classesQuery->get()
            ->filter(fn($class) => $class->schedules->isNotEmpty());

        // Get all cancellations for this date
        $cancellations = ClassCancellation::where('cancelled_date', $date)
            ->pluck('reason', 'school_class_id')
            ->toArray();

        // Get ordered list of all classes for this day (sorted by start time) for permission checking
        $orderedClassIds = $classes->sortBy(function ($class) {
            return $class->schedules->first()?->start_time ?? '23:59:59';
        })->pluck('id')->map(fn($id) => (int) $id)->values()->toArray();

        // Load all permissions that cover this date
        $allStudentIds = $classes->flatMap(fn($c) => $c->students->pluck('id'))->unique()->values();
        $permissionsForDate = \App\Models\StudentPermission::whereIn('student_id', $allStudentIds)
            ->coversDate($date)
            ->with(['firstExcusedClass', 'lastExcusedClass'])
            ->get()
            ->groupBy('student_id');

        // Calculate stats for each class
        $classStats = [];
        foreach ($classes as $class) {
            $schedule = $class->schedules->first();

            // Get active students (already eager loaded) and count those NOT on permission for this class
            $classStudents = $class->students;
            $studentsOnPermissionCount = 0;
            foreach ($classStudents as $student) {
                $studentPermissions = $permissionsForDate->get($student->id, collect());
                if ($studentPermissions->isNotEmpty()) {
                    foreach ($studentPermissions as $permission) {
                        if ($permission->coversClass($date, (int)$class->id, $orderedClassIds)) {
                            $studentsOnPermissionCount++;
                            break;
                        }
                    }
                }
            }
            $totalStudents = $classStudents->count() - $studentsOnPermissionCount;

            // Check if class is cancelled for this date
            $isCancelled = isset($cancellations[$class->id]);
            $cancellationReason = $isCancelled ? $cancellations[$class->id] : null;

            // Get attendance for this class on this date
            $attendances = Attendance::where('school_class_id', $class->id)
                ->where('date', $date)
                ->get();

            $marked = $attendances->count();
            $present = $attendances->whereIn('status', ['present'])->count();
            $late = $attendances->filter(fn($a) => str_starts_with($a->status, 'late'))->count();
            $absent = $attendances->filter(fn($a) => str_starts_with($a->status, 'absent'))->count();

            // Determine status and color
            $status = $isCancelled ? 'cancelled' : 'future';
            $startTimeForSort = null;

            if ($schedule && $schedule->start_time && $schedule->end_time) {
                // Parse times with the selected date to get proper datetime objects
                // Extract just the time portion from schedule times (in case they contain dates)
                $startTimeOnly = Carbon::parse($schedule->start_time)->format('H:i:s');
                $endTimeOnly = Carbon::parse($schedule->end_time)->format('H:i:s');

                $classStart = Carbon::parse($selectedDate->format('Y-m-d') . ' ' . $startTimeOnly, $tz);
                $classEnd = Carbon::parse($selectedDate->format('Y-m-d') . ' ' . $endTimeOnly, $tz);

                // If end time is before start time, class spans midnight - add a day to end time
                if ($classEnd->lessThanOrEqualTo($classStart)) {
                    $classEnd->addDay();
                }

                $startTimeForSort = $startTimeOnly; // Keep time for sorting

                // Only calculate status if not cancelled
                if (!$isCancelled && $isToday) {
                    // Check if current - activate 10 minutes before class starts
                    $classStartEarly = $classStart->copy()->subMinutes(10);
                    if ($now->between($classStartEarly, $classEnd)) {
                        $status = 'current';
                    } elseif ($now->greaterThan($classEnd)) {
                        // Past class
                        if ($marked === 0) {
                            $status = 'not_started';
                        } elseif ($marked < $totalStudents) {
                            $status = 'partial';
                        } else {
                            $status = 'completed';
                        }
                    }
                }
            }

            if (!$isCancelled && !$isToday && $selectedDate->isPast() && $status === 'future') {
                // Past date (not today) and not already processed
                if ($marked === 0) {
                    $status = 'not_started';
                } elseif ($marked < $totalStudents) {
                    $status = 'partial';
                } else {
                    $status = 'completed';
                }
            }

            $classStats[] = [
                'class' => $class,
                'schedule_time' => $schedule ? Carbon::parse($schedule->start_time)->format('g:i A') . ' - ' . Carbon::parse($schedule->end_time)->format('g:i A') : null,
                'start_time_sort' => $startTimeForSort, // For proper time-based sorting
                'total_students' => $totalStudents,
                'marked' => $marked,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'status' => $status,
                'is_cancelled' => $isCancelled,
                'cancellation_reason' => $cancellationReason,
            ];
        }

        // Sort by actual start time (not formatted string)
        $classStats = collect($classStats)->sortBy(function ($stat) {
            return $stat['start_time_sort'] ?? '99:99:99';
        })->values();

        return view('attendance.grid', compact('date', 'previousDate', 'nextDate', 'hebrewDate', 'classStats'));
    }

    /**
     * Mark attendance for a specific class and date (locked view from grid)
     * Date and class are read-only as they come from the grid selection
     */
    public function mark(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $classId = $request->get('class_id');

        if (!$classId) {
            return redirect()->route('attendance.index')->with('error', 'Please select a class from the grid.');
        }

        $user = auth()->user();

        // Authorization: Check if teacher can access this class
        if ($user->hasRole('Teacher') && !$user->hasRole(['Super Admin', 'Admin'])) {
            if (!$user->canAccessClassForAttendance((int)$classId)) {
                return redirect()->route('attendance.index')
                    ->with('error', 'You do not have permission to access this class.');
            }
        }

        // Use app configured timezone for school operations
        $tz = config('app.timezone');
        $now = now($tz);

        // Use selected date's day of week (not today's) for schedule lookup
        $selectedDate = Carbon::parse($date, $tz);
        $selectedDayOfWeek = $selectedDate->dayOfWeek;
        $currentTime = $now->format('H:i:s');

        // Get the selected class
        $selectedClass = SchoolClass::with(['schedules' => function ($query) use ($selectedDayOfWeek) {
            $query->where('day_of_week', $selectedDayOfWeek)->where('is_active', true);
        }])->findOrFail($classId);

        // Get class start/end times (formatted as H:i for JavaScript)
        $classSchedule = $selectedClass->schedules->first();
        $classStartTime = $classSchedule && $classSchedule->start_time
            ? Carbon::parse($classSchedule->start_time)->format('H:i')
            : null;
        $classEndTime = $classSchedule && $classSchedule->end_time
            ? Carbon::parse($classSchedule->end_time)->format('H:i')
            : null;

        // Check for time override for this specific date
        $timeOverride = \App\Models\ClassTimeOverride::where('school_class_id', $classId)
            ->where('override_date', $date)
            ->first();
        if ($timeOverride) {
            if ($timeOverride->start_time) {
                $classStartTime = Carbon::parse($timeOverride->start_time)->format('H:i');
            }
            if ($timeOverride->end_time) {
                $classEndTime = Carbon::parse($timeOverride->end_time)->format('H:i');
            }
        }

        // Get students in this class - filtered by permission for teachers
        $studentsQuery = $selectedClass->students()
            ->where('enrollment_status', 'active');

        // For teachers: only show students they can mark attendance for
        if ($user->hasRole('Teacher') && !$user->hasRole(['Super Admin', 'Admin'])) {
            $accessibleStudentIds = $user->getAttendanceAccessibleStudentIds((int)$classId);
            if (!empty($accessibleStudentIds)) {
                $studentsQuery->whereIn('students.id', $accessibleStudentIds);
            } else {
                // Teacher has no students in this class - show none
                $studentsQuery->whereRaw('1 = 0');
            }
        }

        $students = $studentsQuery
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Check which students are on permission for this class session (class-based filtering)
        $permissionsForDate = \App\Models\StudentPermission::whereIn('student_id', $students->pluck('id'))
            ->coversDate($date)
            ->with(['firstExcusedClass', 'lastExcusedClass'])
            ->get()
            ->groupBy('student_id');

        // Get ordered list of all classes scheduled for this day (by start time)
        // This helps determine if a class falls between first/last excused classes
        $orderedClassIds = SchoolClass::active()
            ->whereHas('schedules', function ($q) use ($selectedDayOfWeek) {
                $q->where('day_of_week', $selectedDayOfWeek)->where('is_active', true);
            })
            ->with(['schedules' => function ($q) use ($selectedDayOfWeek) {
                $q->where('day_of_week', $selectedDayOfWeek)->where('is_active', true);
            }])
            ->get()
            ->sortBy(function ($class) {
                return $class->schedules->first()?->start_time ?? '23:59:59';
            })
            ->pluck('id')
            ->toArray();

        // Filter out students who are on permission for this class session
        $students = $students->filter(function ($student) use ($permissionsForDate, $date, $classId, $orderedClassIds) {
            $studentPermissions = $permissionsForDate->get($student->id, collect());
            if ($studentPermissions->isEmpty()) {
                return true; // Keep student - no permissions
            }
            // Check if any permission covers this specific class
            foreach ($studentPermissions as $permission) {
                if ($permission->coversClass($date, (int)$classId, $orderedClassIds)) {
                    return false; // Remove student - on permission for this class
                }
            }
            return true; // Keep student - permissions don't cover this class
        })->values();

        // For teachers: sort students with attendance taker assignments first, then primary students
        $attendanceTakerStudentIds = [];
        $primaryStudentIds = [];
        if ($user->hasRole('Teacher') && !$user->hasRole(['Super Admin', 'Admin'])) {
            $attendanceTakerStudentIds = $user->getAttendanceTakerStudentIds((int)$classId);
            $primaryStudentIds = $user->getPrimaryStudentIds();

            // Sort students: attendance taker students first (for THIS class), then primary students
            $students = $students->sortBy(function ($student) use ($attendanceTakerStudentIds) {
                // Attendance taker students for THIS class come first (0), others come second (1)
                return in_array($student->id, $attendanceTakerStudentIds) ? 0 : 1;
            })->values();
        }

        // Get existing attendance records
        $attendances = Attendance::where('school_class_id', $classId)
            ->where('date', $date)
            ->get()
            ->keyBy('student_id');

        // Check if class is cancelled for this date
        $cancellation = ClassCancellation::where('school_class_id', $classId)
            ->where('cancelled_date', $date)
            ->first();
        $isCancelled = $cancellation !== null;
        $cancellationReason = $cancellation?->reason;

        // Empty classes array for the locked view
        $classes = collect();

        return view('attendance.mark', compact(
            'date', 'classId', 'classes', 'selectedClass',
            'students', 'attendances', 'classStartTime', 'classEndTime',
            'isCancelled', 'cancellationReason', 'attendanceTakerStudentIds', 'primaryStudentIds'
        ));
    }

    public function store(Request $request)
    {
        // Check if this is an individual AJAX request (has student_id) or bulk form (has attendance array)
        if ($request->has('student_id')) {
            try {
                // Clean up request data before validation
                $data = $request->all();

                // Convert empty strings to null
                if (isset($data['class_start_time']) && $data['class_start_time'] === '') {
                    $request->merge(['class_start_time' => null]);
                }
                if (isset($data['class_end_time']) && $data['class_end_time'] === '') {
                    $request->merge(['class_end_time' => null]);
                }
                if (isset($data['absence_reason_id']) && $data['absence_reason_id'] === '') {
                    $request->merge(['absence_reason_id' => null]);
                }

                // Extract time portion from datetime strings (handles "2026-01-18 20:00:00" -> "20:00")
                if (!empty($data['class_start_time']) && strlen($data['class_start_time']) > 5) {
                    try {
                        $time = date('H:i', strtotime($data['class_start_time']));
                        $request->merge(['class_start_time' => $time]);
                    } catch (\Exception $e) {}
                }
                if (!empty($data['class_end_time']) && strlen($data['class_end_time']) > 5) {
                    try {
                        $time = date('H:i', strtotime($data['class_end_time']));
                        $request->merge(['class_end_time' => $time]);
                    } catch (\Exception $e) {}
                }

                // Individual AJAX request for single student
                $validated = $request->validate([
                    'student_id' => 'required|exists:students,id',
                    'school_class_id' => 'required|exists:school_classes,id',
                    'date' => 'required|date',
                    'status' => 'nullable|string',
                    'minutes_late' => 'nullable|integer|min:0',
                    'minutes_early' => 'nullable|integer|min:0',
                    'notes' => 'nullable|string|max:500',
                    'left_early' => 'nullable|boolean',
                    'left_early_excused' => 'nullable|boolean',
                    'update_note_only' => 'nullable|boolean',
                    'absence_reason_id' => 'nullable|exists:absence_reasons,id',
                    'class_start_time' => 'nullable|date_format:H:i',
                    'class_end_time' => 'nullable|date_format:H:i',
                ]);

                // Authorization check: Can this user mark attendance for this student in this class?
                $this->authorize('create', [Attendance::class, $validated['student_id'], $validated['school_class_id']]);

                // Use updateOrCreate to handle race conditions properly
                // Convert date to Y-m-d format to ensure consistent matching
                $dateFormatted = date('Y-m-d', strtotime($validated['date']));

                $attendance = Attendance::updateOrCreate(
                    [
                        'student_id' => $validated['student_id'],
                        'date' => $dateFormatted,
                        'school_class_id' => $validated['school_class_id'],
                    ],
                    [] // Empty array for now, we'll set values below
                );

                // Update note only mode
                if ($request->boolean('update_note_only')) {
                    $attendance->notes = $validated['notes'] ?? null;
                    $attendance->save();
                    return response()->json(['success' => true, 'message' => 'Note updated']);
                }

                // Left early mode
                if ($request->boolean('left_early')) {
                    $attendance->left_early = true;
                    $attendance->left_early_excused = $request->boolean('left_early_excused');
                    $attendance->minutes_early = $validated['minutes_early'] ?? 0;
                    if (!empty($validated['notes'])) {
                        $attendance->notes = $validated['notes'];
                    }
                    $attendance->save();
                    return response()->json(['success' => true, 'message' => 'Left early marked']);
                }

                // Normal attendance update
                if (!empty($validated['status'])) {
                    $attendance->status = $validated['status'];
                    // Set excused_by if marking as excused
                    if (str_contains($validated['status'], 'excused')) {
                        $attendance->excused_by = auth()->id();
                    }
                }
                if (isset($validated['minutes_late'])) {
                    $attendance->minutes_late = $validated['minutes_late'];
                }
                if (!empty($validated['notes'])) {
                    $attendance->notes = $validated['notes'];
                }
                if (isset($validated['absence_reason_id'])) {
                    $attendance->absence_reason_id = $validated['absence_reason_id'];
                }
                if (isset($validated['class_start_time'])) {
                    $attendance->class_start_time = $validated['class_start_time'];
                }
                if (isset($validated['class_end_time'])) {
                    $attendance->class_end_time = $validated['class_end_time'];
                }
                $attendance->recorded_by = auth()->id();
                $attendance->save();

                return response()->json(['success' => true, 'attendance' => $attendance]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('Attendance validation failed: ' . json_encode($e->errors()));
                \Log::error('Request data: ' . json_encode($request->all()));
                return response()->json(['success' => false, 'error' => 'Validation failed', 'messages' => $e->errors()], 422);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return response()->json(['success' => false, 'error' => 'Not authorized'], 403);
            } catch (\Exception $e) {
                \Log::error('Attendance store error: ' . $e->getMessage());
                return response()->json(['success' => false, 'error' => 'Failed to save attendance'], 500);
            }
        }

        // Bulk form submission (original behavior)
        $validated = $request->validate([
            'date' => 'required|date',
            'class_id' => 'nullable|exists:school_classes,id',
            'class_start_time' => 'nullable|date_format:H:i',
            'class_end_time' => 'nullable|date_format:H:i',
            'attendance' => 'required|array',
            'attendance.*.status' => 'required|string',
            'attendance.*.minutes_late' => 'nullable|integer|min:0',
        ]);

        $date = $validated['date'];
        $classId = $validated['class_id'] ?? null;
        $classStartTime = $validated['class_start_time'] ?? null;
        $classEndTime = $validated['class_end_time'] ?? null;

        foreach ($validated['attendance'] as $studentId => $data) {
            // Skip unmarked students
            if ($data['status'] === 'unmarked') {
                continue;
            }

            // Authorization check: Skip students this user doesn't have permission to mark attendance for
            if (!auth()->user()->hasRole(['Super Admin', 'Admin']) && $classId) {
                if (!auth()->user()->canMarkAttendanceFor($studentId, $classId)) {
                    continue; // Skip unauthorized students silently
                }
            }

            Attendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'date' => $date,
                    'school_class_id' => $classId,
                ],
                [
                    'status' => $data['status'],
                    'minutes_late' => $data['minutes_late'] ?? null,
                    'class_start_time' => $classStartTime,
                    'class_end_time' => $classEndTime,
                    'recorded_by' => auth()->id(),
                ]
            );
        }

        $redirectUrl = route('attendance.index', ['date' => $date, 'class_id' => $classId]);
        
        return redirect($redirectUrl)
            ->with('success', 'Attendance saved for ' . Carbon::parse($date)->format('M d, Y'));
    }

    /**
     * Cancel a class for a specific date.
     */
    public function cancelClass(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ]);

        \App\Models\ClassCancellation::updateOrCreate(
            [
                'school_class_id' => $validated['class_id'],
                'cancelled_date' => $validated['date'],
            ],
            [
                'reason' => $validated['reason'] ?? 'Cancelled',
                'cancelled_by' => auth()->id(),
            ]
        );

        return redirect()->route('attendance.index', ['date' => $validated['date']])
            ->with('success', 'Class has been marked as cancelled for ' . $validated['date']);
    }

    /**
     * Restore a cancelled class.
     */
    public function restoreClass(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'date' => 'required|date',
        ]);

        \App\Models\ClassCancellation::where('school_class_id', $validated['class_id'])
            ->where('cancelled_date', $validated['date'])
            ->delete();

        return redirect()->route('attendance.index', ['date' => $validated['date'], 'class_id' => $validated['class_id']])
            ->with('success', 'Class has been restored for ' . $validated['date']);
    }

    /**
     * Delete/unmark an attendance record.
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'school_class_id' => 'required|exists:school_classes,id',
            'date' => 'required|date',
        ]);

        // Authorization check: Can this user delete attendance for this student in this class?
        $this->authorize('create', [Attendance::class, $validated['student_id'], $validated['school_class_id']]);

        Attendance::where('student_id', $validated['student_id'])
            ->where('school_class_id', $validated['school_class_id'])
            ->where('date', $validated['date'])
            ->delete();

        return response()->json(['success' => true, 'message' => 'Attendance record removed']);
    }

    /**
     * Clear all attendance records for a class on a specific date.
     */
    public function clearClassAttendance(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'date' => 'required|date',
        ]);

        $count = Attendance::where('school_class_id', $validated['class_id'])
            ->where('date', $validated['date'])
            ->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Cleared {$count} attendance records",
                'count' => $count,
            ]);
        }

        return redirect()->route('attendance.index', ['date' => $validated['date'], 'class_id' => $validated['class_id']])
            ->with('success', "Cleared {$count} attendance records for " . Carbon::parse($validated['date'])->format('M d, Y'));
    }

    /**
     * Clear all attendance records for all classes on a specific date.
     */
    public function clearDayAttendance(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $count = Attendance::where('date', $validated['date'])->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Cleared {$count} attendance records for all classes",
                'count' => $count,
            ]);
        }

        return redirect()->route('attendance.index', ['date' => $validated['date']])
            ->with('success', "Cleared {$count} attendance records for " . Carbon::parse($validated['date'])->format('M d, Y'));
    }

    /**
     * Show the import attendance page.
     */
    public function import()
    {
        $classes = SchoolClass::active()->orderBy('display_order')->orderBy('name')->get();
        return view('attendance.import', compact('classes'));
    }

    /**
     * Download a template for importing attendance.
     */
    public function importTemplate(Request $request)
    {
        $classId = $request->get('class_id');
        $date = $request->get('date', now()->format('Y-m-d'));

        $filename = 'attendance_template_' . $date . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($classId, $date) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for proper Hebrew character display in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($file, [
                'Date',
                'Class ID',
                'Student ID',
                'Student Last Name',
                'Student First Name',
                'Status',
                'Minutes Late',
                'Note'
            ]);

            // If class is selected, add rows for each student
            if ($classId) {
                $students = Student::whereHas('classes', fn($q) => $q->where('school_classes.id', $classId))
                    ->where('enrollment_status', 'active')
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get();

                foreach ($students as $student) {
                    fputcsv($file, [
                        $date,
                        $classId,
                        $student->id,
                        $student->last_name,
                        $student->first_name,
                        '', // Status to be filled
                        '', // Minutes late
                        '', // Note
                    ]);
                }
            } else {
                // Add example row
                fputcsv($file, [
                    $date,
                    '1',
                    '1',
                    'Example',
                    'Student',
                    'present',
                    '',
                    ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Process the imported attendance CSV.
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        if (!$handle) {
            return back()->with('error', 'Could not open the file.');
        }

        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'Empty file or invalid format.');
        }

        // Normalize header names
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        // Map expected columns
        $dateIdx = array_search('date', $header);
        $classIdIdx = array_search('class id', $header);
        $studentIdIdx = array_search('student id', $header);
        $statusIdx = array_search('status', $header);
        $minutesLateIdx = array_search('minutes late', $header);
        $noteIdx = array_search('note', $header);

        if ($dateIdx === false || $studentIdIdx === false || $statusIdx === false) {
            fclose($handle);
            return back()->with('error', 'Missing required columns: Date, Student ID, Status');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $lineNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;

            try {
                $date = trim($row[$dateIdx] ?? '');
                $classId = $classIdIdx !== false ? trim($row[$classIdIdx] ?? '') : null;
                $studentId = trim($row[$studentIdIdx] ?? '');
                $status = strtolower(trim($row[$statusIdx] ?? ''));
                $minutesLate = $minutesLateIdx !== false ? (int)($row[$minutesLateIdx] ?? 0) : 0;
                $note = $noteIdx !== false ? trim($row[$noteIdx] ?? '') : null;

                // Skip empty rows
                if (empty($date) || empty($studentId) || empty($status)) {
                    $skipped++;
                    continue;
                }

                // Validate date
                try {
                    $parsedDate = Carbon::parse($date);
                } catch (\Exception $e) {
                    $errors[] = "Line {$lineNum}: Invalid date format";
                    $skipped++;
                    continue;
                }

                // Validate student exists
                if (!Student::find($studentId)) {
                    $errors[] = "Line {$lineNum}: Student ID {$studentId} not found";
                    $skipped++;
                    continue;
                }

                // Validate class exists if provided
                if ($classId && !SchoolClass::find($classId)) {
                    $errors[] = "Line {$lineNum}: Class ID {$classId} not found";
                    $skipped++;
                    continue;
                }

                // Normalize status
                $normalizedStatus = match ($status) {
                    'present', 'p', '1', 'yes', 'y' => 'present',
                    'late', 'l', 'tardy' => 'late_unexcused',
                    'late excused', 'late_excused', 'le' => 'late_excused',
                    'late unexcused', 'late_unexcused', 'lu' => 'late_unexcused',
                    'absent', 'a', '0', 'no', 'n' => 'absent_unexcused',
                    'absent excused', 'absent_excused', 'ae' => 'absent_excused',
                    'absent unexcused', 'absent_unexcused', 'au' => 'absent_unexcused',
                    default => null,
                };

                if (!$normalizedStatus) {
                    $errors[] = "Line {$lineNum}: Invalid status '{$status}'";
                    $skipped++;
                    continue;
                }

                // Create or update attendance
                Attendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'date' => $parsedDate->format('Y-m-d'),
                        'school_class_id' => $classId ?: null,
                    ],
                    [
                        'status' => $normalizedStatus,
                        'minutes_late' => str_starts_with($normalizedStatus, 'late') ? $minutesLate : null,
                        'notes' => $note ?: null,
                        'recorded_by' => auth()->id(),
                    ]
                );

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Line {$lineNum}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        $message = "Imported {$imported} records.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} rows.";
        }

        if (count($errors) > 0) {
            return back()->with('warning', $message)->with('import_errors', array_slice($errors, 0, 10));
        }

        return back()->with('success', $message);
    }

    /**
     * Generate bulk week import template (Item 18)
     * Format: First Name, Last Name, then 7 date columns per class
     */
    public function bulkImportTemplate(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d')));
        $endDate = Carbon::parse($request->get('end_date', now()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d')));
        $classIds = $request->get('class_ids', []);

        // Limit to 7 days
        if ($startDate->diffInDays($endDate) > 6) {
            $endDate = $startDate->copy()->addDays(6);
        }

        // Limit to 10 classes
        $classIds = array_slice($classIds, 0, 10);

        if (empty($classIds)) {
            return back()->with('error', 'Please select at least one class.');
        }

        $classes = SchoolClass::whereIn('id', $classIds)->orderBy('display_order')->orderBy('name')->get();

        // Get all students from selected classes (unique)
        $students = Student::whereHas('classes', fn($q) => $q->whereIn('school_classes.id', $classIds))
            ->where('enrollment_status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Generate date range
        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        $filename = 'bulk_attendance_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($students, $classes, $dates) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for proper Hebrew character display in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Build header row
            $header = ['Student ID', 'First Name', 'Last Name'];
            foreach ($classes as $class) {
                foreach ($dates as $date) {
                    $header[] = $class->name . ' - ' . $date->format('M d Y');
                }
            }
            fputcsv($file, $header);

            // Add student rows
            foreach ($students as $student) {
                $row = [$student->id, $student->first_name, $student->last_name];
                // Add empty cells for each class-date combination
                foreach ($classes as $class) {
                    foreach ($dates as $date) {
                        $row[] = ''; // Empty cell to be filled
                    }
                }
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Process bulk week import (Item 18)
     * Format: * = present, r = absent excused, a = absent, [number] = late minutes
     */
    public function processBulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        if ($handle === false) {
            Log::error('Failed to open bulk import file', ['filename' => $file->getClientOriginalName()]);
            return back()->with('error', 'Could not open the file. Please try again.');
        }

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false || empty($header)) {
            fclose($handle);
            Log::warning('Empty or invalid bulk import file', ['filename' => $file->getClientOriginalName()]);
            return back()->with('error', 'Empty file or invalid format.');
        }

        // Parse header to extract student ID column and class-date columns
        // Format: "Student ID", "First Name", "Last Name", "Class Name - Date", "Class Name - Date", ...
        $studentIdIdx = 0;
        $firstNameIdx = 1;
        $lastNameIdx = 2;
        $classDateColumns = [];

        for ($i = 3; $i < count($header); $i++) {
            $colName = trim($header[$i]);
            // Extract class name and date from "Class Name - Mon DD YYYY" format
            if (preg_match('/^(.+?)\s+-\s+(.+)$/', $colName, $matches)) {
                $className = trim($matches[1]);
                $dateStr = trim($matches[2]);

                // Find class by name
                $class = SchoolClass::where('name', $className)->first();
                if ($class) {
                    $classDateColumns[$i] = [
                        'class_id' => $class->id,
                        'class_name' => $className,
                        'date_str' => $dateStr,
                    ];
                }
            }
        }

        if (empty($classDateColumns)) {
            fclose($handle);
            return back()->with('error', 'No valid class-date columns found in CSV.');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $lineNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;

            try {
                $studentId = trim($row[$studentIdIdx] ?? '');
                $firstName = trim($row[$firstNameIdx] ?? '');
                $lastName = trim($row[$lastNameIdx] ?? '');

                // Skip empty rows
                if (empty($studentId)) {
                    continue;
                }

                // Find student by ID (more reliable than name matching, especially with Hebrew)
                $student = Student::where('id', $studentId)
                    ->where('enrollment_status', 'active')
                    ->first();

                if (!$student) {
                    $errors[] = "Line {$lineNum}: Student ID '{$studentId}' not found or inactive";
                    $skipped++;
                    continue;
                }

                // Process each class-date column
                foreach ($classDateColumns as $colIdx => $colInfo) {
                    $value = trim($row[$colIdx] ?? '');

                    // Skip empty cells
                    if (empty($value)) {
                        continue;
                    }

                    // Parse date from column info
                    // Date string now includes year (e.g., "Jan 05 2025")
                    try {
                        $date = Carbon::parse($colInfo['date_str']);
                    } catch (\Exception $e) {
                        $errors[] = "Line {$lineNum}, {$colInfo['class_name']}: Invalid date format '{$colInfo['date_str']}'";
                        continue;
                    }

                    // Parse attendance code
                    // * or 1 = present
                    // r = absent excused
                    // a or 0 = absent
                    // [number] = late with minutes
                    $status = null;
                    $minutesLate = null;

                    if ($value === '*' || $value === '1') {
                        $status = 'present';
                    } elseif (strtolower($value) === 'r') {
                        $status = 'absent_excused';
                    } elseif (strtolower($value) === 'a' || $value === '0') {
                        $status = 'absent_unexcused';
                    } elseif (is_numeric($value)) {
                        $status = 'late_unexcused';
                        $minutesLate = (int)$value;
                    } else {
                        $errors[] = "Line {$lineNum}, {$colInfo['class_name']}: Invalid code '{$value}'";
                        continue;
                    }

                    // Create or update attendance
                    Attendance::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'date' => $date->format('Y-m-d'),
                            'school_class_id' => $colInfo['class_id'],
                        ],
                        [
                            'status' => $status,
                            'minutes_late' => $minutesLate,
                            'recorded_by' => auth()->id(),
                        ]
                    );

                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = "Line {$lineNum}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        $message = "Imported {$imported} attendance records.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} rows.";
        }

        if (count($errors) > 0) {
            return back()->with('warning', $message)->with('import_errors', array_slice($errors, 0, 20));
        }

        return back()->with('success', $message);
    }

    /**
     * Save a time override for a specific class on a specific date
     */
    public function saveTimeOverride(Request $request)
    {
        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        $override = \App\Models\ClassTimeOverride::updateOrCreate(
            [
                'school_class_id' => $validated['school_class_id'],
                'override_date' => $validated['date'],
            ],
            [
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'created_by' => auth()->id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Time override saved',
            'override' => $override,
        ]);
    }
}
