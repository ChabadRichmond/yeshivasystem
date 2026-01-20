<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\AcademicGrade;
use App\Models\ClassTeacherStudent;
use App\Models\ClassSchedule;
use App\Models\TeachingGroup;
use App\Models\ClassAttendanceTaker;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SchoolClassController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view students', only: ['index', 'show']),
            new Middleware('permission:manage students', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index()
    {
        $classes = SchoolClass::with(['teacher', 'students', 'schedules'])
            ->active()
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(50);
        
        $grades = AcademicGrade::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return view('classes.index', compact('classes', 'grades'));
    }

    public function create()
    {
        $teachers = User::role(['Teacher', 'Admin', 'Super Admin'])->get();
        $students = Student::where('enrollment_status', 'active')->orderBy('last_name')->get();
        
        return view('classes.create', compact('teachers', 'students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level' => 'nullable|string|max:50',
            'teacher_id' => 'nullable|exists:users,id',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
            'academic_year' => 'nullable|string|max:10',
            'schedule_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $class = SchoolClass::create($validated);

        if ($request->has('student_ids')) {
            $class->students()->sync($request->student_ids);
        }
        
        // Sync additional teachers
        if ($request->has('teacher_ids')) {
            $class->teachers()->sync($request->teacher_ids);
        }

        return redirect()->route('classes.index')
            ->with('success', 'Class created successfully.');
    }

    public function show(SchoolClass $class)
    {
        $class->load(['teacher', 'students', 'schedules', 'attendances' => function ($query) {
            $query->latest('date')->limit(30);
        }]);

        return view('classes.show', compact('class'));
    }

    public function edit(SchoolClass $class)
    {
        $teachers = User::role(['Teacher', 'Admin', 'Super Admin'])->get();
        $students = Student::where('enrollment_status', 'active')->orderBy('last_name')->get();
        $class->load([
            'students',
            'teachers',
            'teacherStudentAssignments', // Legacy - keeping for backwards compatibility
            'schedules',
            'teachingGroups.students',
            'teachingGroups.primaryTeacher',
            'attendanceTakerAssignments.attendanceTaker',
        ]);

        return view('classes.edit', compact('class', 'teachers', 'students'));
    }

    public function update(Request $request, SchoolClass $class)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_order' => 'nullable|integer|min:0',
            'grade_level' => 'nullable|string|max:50',
            'teacher_id' => 'nullable|exists:users,id',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
            'academic_year' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $class->update($validated);

        if ($request->has('student_ids')) {
            $class->students()->sync($request->student_ids);
        }
        
        // Sync additional teachers
        if ($request->has('teacher_ids')) {
            $class->teachers()->sync($request->teacher_ids);
        }
        
        // Sync teaching groups
        // Format: teaching_groups[groupId] = { name: string, primary_teacher_id: int, students: [studentId, ...] }
        // New groups have id like "new_1", "new_2", etc.
        if ($request->has('teaching_groups')) {
            $existingGroupIds = $class->teachingGroups()->pluck('id')->toArray();
            $submittedGroupIds = [];
            $affectedTeacherIds = [];

            foreach ($request->teaching_groups as $groupId => $groupData) {
                if (empty($groupData['name'])) {
                    continue; // Skip empty groups
                }

                // Track affected teachers for cache clearing
                if (!empty($groupData['primary_teacher_id'])) {
                    $affectedTeacherIds[] = $groupData['primary_teacher_id'];
                }

                if (str_starts_with($groupId, 'new_')) {
                    // Create new teaching group
                    $group = TeachingGroup::create([
                        'school_class_id' => $class->id,
                        'name' => $groupData['name'],
                        'primary_teacher_id' => $groupData['primary_teacher_id'] ?? null,
                        'display_order' => $groupData['display_order'] ?? 0,
                    ]);
                    $submittedGroupIds[] = $group->id;
                } else {
                    // Update existing group
                    $group = TeachingGroup::find($groupId);
                    if ($group && $group->school_class_id === $class->id) {
                        $group->update([
                            'name' => $groupData['name'],
                            'primary_teacher_id' => $groupData['primary_teacher_id'] ?? null,
                            'display_order' => $groupData['display_order'] ?? 0,
                        ]);
                        $submittedGroupIds[] = (int) $groupId;
                    }
                }

                // Sync students to this teaching group
                if (isset($group)) {
                    $studentIds = $groupData['students'] ?? [];
                    $group->students()->sync($studentIds);
                }
            }

            // Delete groups that were removed
            $groupsToDelete = array_diff($existingGroupIds, $submittedGroupIds);
            TeachingGroup::whereIn('id', $groupsToDelete)->delete();

            // Clear caches for affected teachers
            foreach (array_unique($affectedTeacherIds) as $teacherId) {
                \Illuminate\Support\Facades\Cache::forget("teacher_{$teacherId}_primary_students");
                \Illuminate\Support\Facades\Cache::forget("teacher_{$teacherId}_accessible_students");
                \Illuminate\Support\Facades\Cache::forget("teacher_{$teacherId}_teaching_classes");
                \Illuminate\Support\Facades\Cache::forget("teacher_{$teacherId}_attendance_classes");
                \Illuminate\Support\Facades\Cache::forget("teacher_{$teacherId}_teaching_group_class_{$class->id}");
            }
        }

        // Sync attendance taker assignments
        // Format: attendance_takers[studentId] = teacherId
        if ($request->has('attendance_takers')) {
            // Clear existing attendance taker assignments for this class
            ClassAttendanceTaker::where('school_class_id', $class->id)->delete();

            $affectedTeacherIds = [];
            foreach ($request->attendance_takers as $studentId => $teacherId) {
                if (!empty($teacherId)) {
                    ClassAttendanceTaker::create([
                        'school_class_id' => $class->id,
                        'student_id' => $studentId,
                        'attendance_taker_id' => $teacherId,
                    ]);
                    $affectedTeacherIds[] = $teacherId;
                }
            }

            // Clear caches for affected teachers
            foreach (array_unique($affectedTeacherIds) as $teacherId) {
                \Illuminate\Support\Facades\Cache::forget("teacher_{$teacherId}_accessible_students");
                \Illuminate\Support\Facades\Cache::forget("teacher_{$teacherId}_attendance_classes");
                \Illuminate\Support\Facades\Cache::forget("teacher_{$teacherId}_attendance_class_{$class->id}");
            }
        }

        // Legacy: Sync teacher-student assignments WITH ROLES (keeping for backwards compatibility)
        if ($request->has('teacher_students')) {
            // Clear existing assignments for this class
            ClassTeacherStudent::where('school_class_id', $class->id)->delete();

            foreach ($request->teacher_students as $teacherId => $studentRoles) {
                if (!empty($studentRoles)) {
                    foreach ($studentRoles as $studentId => $role) {
                        if (!empty($role)) {
                            ClassTeacherStudent::updateOrCreate(
                                [
                                    'school_class_id' => $class->id,
                                    'student_id' => $studentId,
                                    'role' => $role,
                                ],
                                [
                                    'teacher_user_id' => $teacherId,
                                ]
                            );
                        }
                    }
                }
            }
        }
        
        // Sync weekly schedules
        if ($request->has('schedules')) {
            foreach ($request->schedules as $dayOfWeek => $scheduleData) {
                $isEnabled = isset($scheduleData['enabled']) && $scheduleData['enabled'] == '1';
                $startTime = $scheduleData['start_time'] ?? null;
                $endTime = $scheduleData['end_time'] ?? null;
                
                $schedule = ClassSchedule::updateOrCreate(
                    ['school_class_id' => $class->id, 'day_of_week' => $dayOfWeek],
                    [
                        'start_time' => $startTime ?: '00:00',
                        'end_time' => $endTime ?: null,
                        'is_active' => $isEnabled && !empty($startTime),
                    ]
                );
            }
        }

        return redirect()->route('classes.show', $class)
            ->with('success', 'Class updated successfully.');
    }

    public function destroy(Request $request, SchoolClass $class)
    {
        $hardDelete = $request->input('hard_delete', false);

        if ($hardDelete) {
            // Hard delete - permanently remove from database
            $class->forceDelete();
            $message = 'Class permanently deleted.';
        } else {
            // Soft delete - mark as deleted but keep in database
            $class->delete();
            $message = 'Class deleted successfully.';
        }

        return redirect()->route('classes.index')
            ->with('success', $message);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:school_classes,id',
        ]);

        foreach ($request->order as $position => $classId) {
            SchoolClass::where('id', $classId)->update(['display_order' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }
}
