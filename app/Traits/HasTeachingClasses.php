<?php

namespace App\Traits;

use App\Models\SchoolClass;
use App\Models\ClassTeacherStudent;
use App\Models\TeachingGroup;
use App\Models\ClassAttendanceTaker;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Trait to add teaching-related methods to User model.
 *
 * Usage: Add `use HasTeachingClasses;` to your User model.
 *
 * Teaching model:
 * - Teachers are primary teachers of "teaching groups" within a class
 * - A teaching group has ONE primary teacher and MANY students
 * - Attendance takers are assigned separately per-student, per-class
 */
trait HasTeachingClasses
{
    /**
     * Get classes where this user is the primary teacher (legacy - class level).
     */
    public function primaryClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    /**
     * Get teaching groups where this user is the primary teacher.
     */
    public function teachingGroups(): HasMany
    {
        return $this->hasMany(TeachingGroup::class, 'primary_teacher_id');
    }

    /**
     * Get attendance taker assignments where this user takes attendance.
     */
    public function attendanceTakerAssignments(): HasMany
    {
        return $this->hasMany(ClassAttendanceTaker::class, 'attendance_taker_id');
    }

    /**
     * Get classes where this user is an assigned teacher (via pivot).
     */
    public function teachingClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_teacher')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    /**
     * Get all class IDs this user can access for reports.
     * Includes classes where they are primary teacher of any teaching group.
     */
    public function getTeachingClassIds(): array
    {
        return Cache::remember("teacher_{$this->id}_teaching_classes", 3600, function () {
            // Classes where user is primary teacher of a teaching group
            $teachingGroupClassIds = DB::table('teaching_groups')
                ->where('primary_teacher_id', $this->id)
                ->distinct()
                ->pluck('school_class_id')
                ->toArray();

            // Legacy: classes via class_teacher pivot
            $assignedIds = $this->teachingClasses()->pluck('school_classes.id')->toArray();

            // Legacy: direct teacher_id assignment
            $primaryIds = $this->primaryClasses()->pluck('id')->toArray();

            return array_unique(array_merge($teachingGroupClassIds, $assignedIds, $primaryIds));
        });
    }

    /**
     * Check if user teaches a specific class.
     */
    public function teachesClass(int $classId): bool
    {
        return in_array($classId, $this->getTeachingClassIds());
    }

    /**
     * Get guardian record if user is a parent.
     */
    public function guardian()
    {
        return $this->hasOne(\App\Models\Guardian::class);
    }

    /**
     * Get student record if user is a student.
     */
    public function studentProfile()
    {
        return $this->hasOne(\App\Models\Student::class);
    }

    /**
     * Get children (students) if user is a parent.
     */
    public function children()
    {
        $guardian = $this->guardian;
        if ($guardian) {
            return $guardian->students();
        }
        return collect();
    }

    // ==================== Permission Helper Methods ====================

    /**
     * Get all student IDs this teacher has PRIMARY access to (across ALL classes).
     * Primary access = students in teaching groups where this teacher is the primary teacher.
     * Cached for 1 hour.
     */
    public function getPrimaryStudentIds(): array
    {
        return Cache::remember("teacher_{$this->id}_primary_students", 3600, function () {
            // Get students from teaching groups where user is primary teacher
            return DB::table('teaching_group_student')
                ->join('teaching_groups', 'teaching_groups.id', '=', 'teaching_group_student.teaching_group_id')
                ->where('teaching_groups.primary_teacher_id', $this->id)
                ->distinct()
                ->pluck('teaching_group_student.student_id')
                ->toArray();
        });
    }

    /**
     * Check if teacher is PRIMARY teacher for a specific student (any class).
     */
    public function isPrimaryTeacherFor(int $studentId): bool
    {
        return in_array($studentId, $this->getPrimaryStudentIds());
    }

    /**
     * Get student IDs where this teacher is the attendance taker in a specific class.
     */
    public function getAttendanceTakerStudentIds(int $classId): array
    {
        return Cache::remember("teacher_{$this->id}_attendance_class_{$classId}", 3600, function () use ($classId) {
            return DB::table('class_attendance_takers')
                ->where('attendance_taker_id', $this->id)
                ->where('school_class_id', $classId)
                ->pluck('student_id')
                ->toArray();
        });
    }

    /**
     * Get student IDs from this teacher's teaching group in a specific class.
     */
    public function getTeachingGroupStudentIds(int $classId): array
    {
        return Cache::remember("teacher_{$this->id}_teaching_group_class_{$classId}", 3600, function () use ($classId) {
            return DB::table('teaching_group_student')
                ->join('teaching_groups', 'teaching_groups.id', '=', 'teaching_group_student.teaching_group_id')
                ->where('teaching_groups.primary_teacher_id', $this->id)
                ->where('teaching_groups.school_class_id', $classId)
                ->pluck('teaching_group_student.student_id')
                ->toArray();
        });
    }

    /**
     * Get ALL students this teacher can mark attendance for in a class.
     * (students in their teaching group + students assigned to them as attendance taker)
     */
    public function getAttendanceAccessibleStudentIds(int $classId): array
    {
        $teachingGroupStudents = $this->getTeachingGroupStudentIds($classId);
        $attendanceTakerStudents = $this->getAttendanceTakerStudentIds($classId);

        return array_unique(array_merge($teachingGroupStudents, $attendanceTakerStudents));
    }

    /**
     * Check if teacher can mark attendance for a student in a specific class.
     */
    public function canMarkAttendanceFor(int $studentId, int $classId): bool
    {
        return in_array($studentId, $this->getAttendanceAccessibleStudentIds($classId));
    }

    /**
     * Get all students a teacher has ANY access to (for view filtering).
     * Includes both primary students (via teaching groups) and attendance taker students.
     */
    public function getAccessibleStudentIds(): array
    {
        return Cache::remember("teacher_{$this->id}_accessible_students", 3600, function () {
            // Students from teaching groups
            $primaryStudents = DB::table('teaching_group_student')
                ->join('teaching_groups', 'teaching_groups.id', '=', 'teaching_group_student.teaching_group_id')
                ->where('teaching_groups.primary_teacher_id', $this->id)
                ->distinct()
                ->pluck('teaching_group_student.student_id')
                ->toArray();

            // Students from attendance taker assignments
            $attendanceStudents = DB::table('class_attendance_takers')
                ->where('attendance_taker_id', $this->id)
                ->distinct()
                ->pluck('student_id')
                ->toArray();

            return array_unique(array_merge($primaryStudents, $attendanceStudents));
        });
    }

    /**
     * Get class IDs where this teacher can mark attendance.
     * Returns classes where they have a teaching group OR are an attendance taker.
     */
    public function getAttendanceClassIds(): array
    {
        return Cache::remember("teacher_{$this->id}_attendance_classes", 3600, function () {
            // Classes with teaching groups
            $teachingGroupClasses = DB::table('teaching_groups')
                ->where('primary_teacher_id', $this->id)
                ->distinct()
                ->pluck('school_class_id')
                ->toArray();

            // Classes where user is attendance taker
            $attendanceTakerClasses = DB::table('class_attendance_takers')
                ->where('attendance_taker_id', $this->id)
                ->distinct()
                ->pluck('school_class_id')
                ->toArray();

            return array_unique(array_merge($teachingGroupClasses, $attendanceTakerClasses));
        });
    }

    /**
     * Check if user can access a specific class for attendance.
     */
    public function canAccessClassForAttendance(int $classId): bool
    {
        return in_array($classId, $this->getAttendanceClassIds());
    }

    /**
     * Clear all cached permission data for this teacher.
     */
    public function clearTeachingCaches(): void
    {
        Cache::forget("teacher_{$this->id}_primary_students");
        Cache::forget("teacher_{$this->id}_accessible_students");
        Cache::forget("teacher_{$this->id}_attendance_classes");
        Cache::forget("teacher_{$this->id}_teaching_classes");

        // Clear class-specific caches (these are harder to enumerate, so we'll just let them expire)
    }
}
