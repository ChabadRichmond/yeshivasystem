<?php

namespace App\Policies;

use App\Models\TestScore;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TestScorePolicy
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
     * Determine whether the user can view any test scores.
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
     * Determine whether the user can view the test score.
     */
    public function view(User $user, TestScore $testScore): bool
    {
        // Primary teachers can view scores for their students
        if ($user->hasRole('Teacher')) {
            return $user->isPrimaryTeacherFor($testScore->student_id);
        }

        // Parents can view their children's scores
        if ($user->hasRole('Parent')) {
            $guardian = $user->guardian;
            if ($guardian) {
                return $guardian->students()->where('id', $testScore->student_id)->exists();
            }
        }

        // Students can view their own scores
        if ($user->hasRole('Student')) {
            $student = $user->studentProfile;
            return $student && $student->id === $testScore->student_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create test scores.
     * Accepts optional studentId parameter for granular checks.
     */
    public function create(User $user, ?int $studentId = null): bool
    {
        // Teachers can create scores for their primary students
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
     * Determine whether the user can update the test score.
     */
    public function update(User $user, TestScore $testScore): bool
    {
        // Primary teachers can update scores for their students
        if ($user->hasRole('Teacher')) {
            return $user->isPrimaryTeacherFor($testScore->student_id);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the test score.
     */
    public function delete(User $user, TestScore $testScore): bool
    {
        // Only admins can delete (handled by before() method)
        return false;
    }

    /**
     * Determine whether the user can restore the test score.
     */
    public function restore(User $user, TestScore $testScore): bool
    {
        // Only admins can restore (handled by before() method)
        return false;
    }

    /**
     * Determine whether the user can permanently delete the test score.
     */
    public function forceDelete(User $user, TestScore $testScore): bool
    {
        // Only admins can force delete (handled by before() method)
        return false;
    }
}
