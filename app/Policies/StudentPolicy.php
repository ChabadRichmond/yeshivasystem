<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Super Admin can do anything
        if ($user->hasRole('Super Admin')) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any students.
     */
    public function viewAny(User $user): bool
    {
        // Admin and Teacher can view all students
        if ($user->hasRole(['Admin', 'Teacher', 'Billing Admin'])) {
            return true;
        }
        
        // Parents and Students can only access their specific students
        // They'll be filtered in the controller
        if ($user->hasRole(['Parent', 'Student'])) {
            return true;
        }
        
        return $user->can('view students');
    }

    /**
     * Determine whether the user can view the student.
     */
    public function view(User $user, Student $student): bool
    {
        // Admin and Billing Admin can view any student
        if ($user->hasRole(['Admin', 'Billing Admin'])) {
            return true;
        }

        // Primary teachers can view their students
        if ($user->hasRole('Teacher') && $user->isPrimaryTeacherFor($student->id)) {
            return true;
        }

        // Parent can only view their linked children
        if ($user->hasRole('Parent')) {
            return $student->guardians()->whereHas('user', function ($q) use ($user) {
                $q->where('id', $user->id);
            })->exists();
        }
        
        // Student can only view their own profile
        if ($user->hasRole('Student')) {
            return $student->user_id === $user->id;
        }
        
        return $user->can('view students');
    }

    /**
     * Determine whether the user can create students.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Admin']) || $user->can('manage students');
    }

    /**
     * Determine whether the user can update the student.
     */
    public function update(User $user, Student $student): bool
    {
        // Admin can update any student
        if ($user->hasRole('Admin')) {
            return true;
        }
        
        // Student can update their own profile (limited fields)
        if ($user->hasRole('Student') && $student->user_id === $user->id) {
            return true;
        }
        
        return $user->can('manage students');
    }

    /**
     * Determine whether the user can delete the student.
     */
    public function delete(User $user, Student $student): bool
    {
        return $user->hasRole('Admin') || $user->can('manage students');
    }
}
