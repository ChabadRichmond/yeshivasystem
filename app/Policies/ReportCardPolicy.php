<?php

namespace App\Policies;

use App\Models\ReportCard;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReportCardPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Super Admin and Admin can do anything
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any report cards.
     */
    public function viewAny(User $user): bool
    {
        // Teachers can view if they have primary students
        if ($user->hasRole('Teacher')) {
            return count($user->getPrimaryStudentIds()) > 0;
        }

        return false;
    }

    /**
     * Determine whether the user can view the report card.
     */
    public function view(User $user, ReportCard $reportCard): bool
    {
        // Primary teachers can view report cards for their students
        if ($user->hasRole('Teacher')) {
            return $user->isPrimaryTeacherFor($reportCard->student_id);
        }

        // Parents can view their children's report cards
        if ($user->hasRole('Parent')) {
            $guardian = $user->guardian;
            if ($guardian) {
                return $guardian->students()->where('id', $reportCard->student_id)->exists();
            }
        }

        // Students can view their own report cards
        if ($user->hasRole('Student')) {
            $student = $user->studentProfile;
            return $student && $student->id === $reportCard->student_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create report cards.
     * Accepts optional studentId parameter for granular checks.
     */
    public function create(User $user, ?int $studentId = null): bool
    {
        // Teachers can create report cards for their primary students
        if ($user->hasRole('Teacher')) {
            if ($studentId !== null) {
                return $user->isPrimaryTeacherFor($studentId);
            }
            // Fallback: Has at least one primary student
            return count($user->getPrimaryStudentIds()) > 0;
        }

        return false;
    }

    /**
     * Determine whether the user can update the report card.
     */
    public function update(User $user, ReportCard $reportCard): bool
    {
        // Primary teachers can update report cards for their students
        if ($user->hasRole('Teacher')) {
            return $user->isPrimaryTeacherFor($reportCard->student_id);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the report card.
     */
    public function delete(User $user, ReportCard $reportCard): bool
    {
        // Only admins can delete (handled by before() method)
        return false;
    }

    /**
     * Determine whether the user can restore the report card.
     */
    public function restore(User $user, ReportCard $reportCard): bool
    {
        // Only admins can restore (handled by before() method)
        return false;
    }

    /**
     * Determine whether the user can permanently delete the report card.
     */
    public function forceDelete(User $user, ReportCard $reportCard): bool
    {
        // Only admins can force delete (handled by before() method)
        return false;
    }
}
