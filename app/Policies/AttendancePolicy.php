<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any attendance records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin', 'Teacher', 'Billing Admin', 'Parent', 'Student']);
    }

    /**
     * Determine whether the user can view the attendance record.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        // Admin, Teacher, Billing Admin can view any attendance
        if ($user->hasRole(['Admin', 'Teacher', 'Billing Admin'])) {
            return true;
        }
        
        // Parent can view their children's attendance
        if ($user->hasRole('Parent')) {
            $student = $attendance->student;
            return $student && $student->guardians()->whereHas('user', function ($q) use ($user) {
                $q->where('id', $user->id);
            })->exists();
        }
        
        // Student can view their own attendance
        if ($user->hasRole('Student')) {
            $student = $attendance->student;
            return $student && $student->user_id === $user->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create/mark attendance.
     * With granular permissions: check student-specific and class-specific access.
     */
    public function create(User $user, ?int $studentId = null, ?int $classId = null): bool
    {
        // Admins can mark attendance for anyone
        if ($user->hasRole(['Admin']) || $user->can('mark attendance')) {
            return true;
        }

        // Check granular permissions for teachers
        if ($studentId && $classId && $user->hasRole('Teacher')) {
            return $user->canMarkAttendanceFor($studentId, $classId);
        }

        // Fallback: Teachers can mark attendance (will be further restricted in controller)
        return $user->hasRole('Teacher');
    }

    /**
     * Determine whether the user can view attendance for a specific student.
     */
    public function viewForStudent(User $user, int $studentId): bool
    {
        // Admins and Billing Admins can view any student
        if ($user->hasRole(['Admin', 'Billing Admin'])) {
            return true;
        }

        // Primary teachers can view their students
        if ($user->hasRole('Teacher')) {
            return $user->isPrimaryTeacherFor($studentId);
        }

        // Parents can view their children
        if ($user->hasRole('Parent')) {
            $guardian = $user->guardian;
            if ($guardian) {
                return $guardian->students()->where('id', $studentId)->exists();
            }
        }

        // Students can view their own
        if ($user->hasRole('Student')) {
            $student = $user->studentProfile;
            return $student && $student->id === $studentId;
        }

        return false;
    }

    /**
     * Determine whether the user can view reports.
     * Teachers can only view reports for THEIR OWN classes.
     */
    public function viewReports(User $user): bool
    {
        return $user->hasRole(['Admin', 'Teacher', 'Billing Admin']);
    }

    /**
     * Determine whether the user can view all reports (not restricted to own classes).
     */
    public function viewAllReports(User $user): bool
    {
        // Only Admin can view ALL class reports
        return $user->hasRole('Admin') || $user->can('view all reports');
    }

    /**
     * Get the class IDs that a teacher can view reports for.
     * Returns null if user can view all, or array of class IDs.
     */
    public static function getReportableClassIds(User $user): ?array
    {
        if ($user->hasRole(['Super Admin', 'Admin']) || $user->can('view all reports')) {
            return null; // No restriction
        }
        
        if ($user->hasRole('Teacher')) {
            // Get classes where user is primary teacher OR in class_teacher pivot
            $primaryClassIds = $user->primaryClasses()->pluck('id')->toArray();
            $assignedClassIds = $user->teachingClasses()->pluck('school_classes.id')->toArray();
            
            return array_unique(array_merge($primaryClassIds, $assignedClassIds));
        }
        
        return []; // No access
    }
}
