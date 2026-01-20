<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Guardian;
use App\Models\AcademicGrade;
use App\Models\Subject;
use App\Models\TestScore;
use App\Models\Attendance;
use App\Models\ClassCancellation;
use Carbon\Carbon;
use App\Policies\StudentPolicy;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StudentController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view students|view own students', only: ['index', 'show']),
            new Middleware('permission:manage students', only: ['create', 'store', 'destroy']),
        ];
    }

    /**
     * Display a listing of students.
     * Scoped based on user role.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Build base query
        $query = Student::with(['academicGrade', 'classes'])
            ->where('enrollment_status', 'active');
        
        // Apply role-based scoping
        if ($user->hasRole(['Super Admin', 'Admin', 'Billing Admin'])) {
            // Full access - no scoping needed
        } elseif ($user->hasRole('Teacher')) {
            // Teacher: only their primary students
            $primaryStudentIds = $user->getPrimaryStudentIds();
            if (!empty($primaryStudentIds)) {
                $query->whereIn('id', $primaryStudentIds);
            } else {
                $query->whereRaw('1 = 0'); // No students assigned
            }
        } elseif ($user->hasRole('Parent')) {
            // Parent: only their linked children
            $guardian = Guardian::where('user_id', $user->id)->first();
            if ($guardian) {
                $childIds = $guardian->students()->pluck('students.id');
                $query->whereIn('id', $childIds);
            } else {
                $query->whereRaw('1 = 0'); // No results
            }
        } elseif ($user->hasRole('Student')) {
            // Student: only their own profile
            $query->where('user_id', $user->id);
        } else {
            // No access
            $query->whereRaw('1 = 0');
        }
        
        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('grade_id')) {
            $query->where('academic_grade_id', $request->grade_id);
        }
        
        $students = $query->orderBy('last_name')->paginate(20);
        $grades = AcademicGrade::where('is_active', true)->orderBy('display_order')->get();
        
        // Pass read-only flag for parents/students
        $isReadOnly = $user->hasRole(['Parent', 'Student']);
        
        return view('students.index', compact('students', 'grades', 'isReadOnly'));
    }

    /**
     * Show the form for creating a new student.
     */
    public function create()
    {
        $this->authorize('create', Student::class);
        
        $grades = AcademicGrade::where('is_active', true)->orderBy('display_order')->get();
        return view('students.create', compact('grades'));
    }

    /**
     * Store a newly created student.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Student::class);
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'academic_grade_id' => 'nullable|exists:academic_grades,id',
            'student_id' => 'nullable|string|max:50',
            'enrollment_status' => 'required|in:active,inactive,graduated,transferred',
            'enrollment_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'medical_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('photos', 'public');
            $validated['photo'] = $path;
        }

        $student = Student::create($validated);

        return redirect()->route('students.show', $student)
            ->with('success', 'Student created successfully.');
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student)
    {
        $this->authorize('view', $student);

        $student->load([
            'academicGrade',
            'classes.schedules',
            'guardians.user',
        ]);

        // Load recent attendance for display (limited to 30 for UI)
        $recentAttendances = Attendance::where('student_id', $student->id)
            ->with('schoolClass.schedules')
            ->latest('date')
            ->limit(30)
            ->get();

        // Load ALL attendance for stats calculation (no limit)
        $allAttendances = Attendance::where('student_id', $student->id)
            ->with('schoolClass.schedules')
            ->get();

        // Exclude cancelled sessions from stats calculation
        $cancelledSessionKeys = ClassCancellation::select('school_class_id', 'cancelled_date')
            ->get()
            ->map(fn($c) => $c->school_class_id . '-' . $c->cancelled_date->format('Y-m-d'))
            ->flip()
            ->toArray();

        if (!empty($cancelledSessionKeys)) {
            $allAttendances = $allAttendances->reject(function ($a) use ($cancelledSessionKeys) {
                $key = $a->school_class_id . '-' . $a->date->format('Y-m-d');
                return isset($cancelledSessionKeys[$key]);
            });
        }

        // Calculate time-based attendance stats (matching ReportController method)
        $totalPossible = 0;
        $totalAttended = 0;
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;

        foreach ($allAttendances as $a) {
            $duration = $this->getClassDurationForAttendance($a);

            if ($a->status === 'absent_excused') {
                // Excused absences don't count at all
                continue;
            } elseif ($a->status === 'late_excused') {
                // Late excused: subtract late minutes from calculation
                $lateMinutes = $a->minutes_late ?? 0;
                $adjustedDuration = max(0, $duration - $lateMinutes);
                $totalPossible += $adjustedDuration;
                $totalAttended += $adjustedDuration;
                $lateCount++;
            } elseif (str_starts_with($a->status, 'absent')) {
                // Regular absent (unexcused)
                $totalPossible += $duration;
                $absentCount++;
            } elseif (str_starts_with($a->status, 'late')) {
                // Regular late (unexcused) - deduct late minutes from attended time
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

        $attendanceStats = [
            'total' => $allAttendances->count(),
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'percentage' => $totalPossible > 0 ? round(($totalAttended / $totalPossible) * 100, 1) : 0,
        ];

        // Group recent attendances by date for display
        $attendancesByDate = $recentAttendances->groupBy(fn($a) => $a->date->format('Y-m-d'));

        // Get subject averages for grades overview
        $subjects = Subject::active()->ordered()->get();
        $subjectAverages = [];
        foreach ($subjects as $subject) {
            $average = $student->getSubjectAverage($subject->id);
            if ($average !== null) {
                $subjectAverages[$subject->id] = [
                    'subject' => $subject,
                    'average' => $average,
                    'letter_grade' => TestScore::percentageToLetterGrade($average),
                ];
            }
        }

        // Get recent test scores
        $recentTestScores = $student->testScores()
            ->with('subject')
            ->latest('test_date')
            ->limit(5)
            ->get();

        $isReadOnly = auth()->user()->hasRole(['Parent', 'Student']);

        return view('students.show', compact('student', 'isReadOnly', 'attendanceStats', 'attendancesByDate', 'subjectAverages', 'recentTestScores'));
    }

    /**
     * Show the form for editing the specified student.
     */
    public function edit(Student $student)
    {
        $this->authorize('update', $student);
        
        $grades = AcademicGrade::where('is_active', true)->orderBy('display_order')->get();
        $isReadOnly = auth()->user()->hasRole(['Parent', 'Student']);
        
        // Students can only edit limited profile fields
        $limitedEdit = auth()->user()->hasRole('Student');
        
        return view('students.edit', compact('student', 'grades', 'isReadOnly', 'limitedEdit'));
    }

    /**
     * Update the specified student.
     */
    public function update(Request $request, Student $student)
    {
        $this->authorize('update', $student);
        
        $user = auth()->user();
        
        // Students can only update specific profile fields
        if ($user->hasRole('Student') && $student->user_id === $user->id) {
            $validated = $request->validate([
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'province' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
            ]);
        } else {
            // Full update for admins
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'academic_grade_id' => 'nullable|exists:academic_grades,id',
                'student_id' => 'nullable|string|max:50',
                'enrollment_status' => 'required|in:active,inactive,graduated,transferred',
                'enrollment_date' => 'nullable|date',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'province' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'phone' => 'nullable|string|max:20',
                'medical_notes' => 'nullable|string',
                'notes' => 'nullable|string',
                'photo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            ]);
        }

        // Handle photo upload (already validated above)
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($student->photo && \Storage::disk('public')->exists($student->photo)) {
                \Storage::disk('public')->delete($student->photo);
            }

            // Store new photo
            $path = $request->file('photo')->store('photos', 'public');
            $validated['photo'] = $path;
        }

        $student->update($validated);

        return redirect()->route('students.show', $student)
            ->with('success', 'Student updated successfully.');
    }

    /**
     * Delete student's photo.
     */
    public function deletePhoto(Student $student)
    {
        $this->authorize('update', $student);

        if ($student->photo) {
            // Delete the photo file from storage
            if (\Storage::disk('public')->exists($student->photo)) {
                \Storage::disk('public')->delete($student->photo);
            }

            // Clear the photo field in database
            $student->update(['photo' => null]);

            return redirect()->back()->with('success', 'Photo deleted successfully.');
        }

        return redirect()->back()->with('error', 'No photo to delete.');
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Student $student)
    {
        $this->authorize('delete', $student);

        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Student deleted successfully.');
    }

    /**
     * Add a permission/leave period for a student
     */
    public function addPermission(Request $request, Student $student)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'first_excused_class_id' => 'nullable|exists:school_classes,id',
            'last_excused_class_id' => 'nullable|exists:school_classes,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $student->permissions()->create([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'first_excused_class_id' => $validated['first_excused_class_id'] ?? null,
            'last_excused_class_id' => $validated['last_excused_class_id'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Permission added successfully.');
    }

    /**
     * Delete a permission/leave period
     */
    public function deletePermission(Student $student, $permissionId)
    {
        $permission = $student->permissions()->findOrFail($permissionId);
        $permission->delete();

        return redirect()->back()->with('success', 'Permission deleted successfully.');
    }

    /**
     * Get the class duration for a specific attendance record.
     * Priority order:
     * 1. Use class_start_time and class_end_time from the attendance record (date-specific override)
     * 2. Look up day-of-week specific schedule from class_schedules
     * 3. Fallback to 60 minutes
     */
    private function getClassDurationForAttendance($attendance): int
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

        // Priority 2: Use eager-loaded schedules from schoolClass relationship
        if ($attendance->school_class_id && $attendance->date) {
            $dayOfWeek = $attendance->date->dayOfWeek;
            $schedule = null;

            // Try to use pre-loaded relationship first
            if ($attendance->relationLoaded('schoolClass') && $attendance->schoolClass && $attendance->schoolClass->relationLoaded('schedules')) {
                $schedule = $attendance->schoolClass->schedules
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->first();
            } else {
                // Fallback to database query if relationship not loaded
                $schedule = \App\Models\ClassSchedule::where('school_class_id', $attendance->school_class_id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->first();
            }

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
}
