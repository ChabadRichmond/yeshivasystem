<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Pivot model for teacher-student assignments within a class.
 */
class ClassTeacherStudent extends Model
{
    // Role constants
    public const ROLE_PRIMARY_TEACHER = 'primary_teacher';
    public const ROLE_ATTENDANCE_TAKER = 'attendance_taker';

    protected $table = 'class_teacher_student';

    protected $fillable = [
        'school_class_id',
        'teacher_user_id',
        'student_id',
        'role',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Check if this assignment is for a primary teacher role.
     */
    public function isPrimaryTeacher(): bool
    {
        return $this->role === self::ROLE_PRIMARY_TEACHER;
    }

    /**
     * Check if this assignment is for an attendance taker role.
     */
    public function isAttendanceTaker(): bool
    {
        return $this->role === self::ROLE_ATTENDANCE_TAKER;
    }

    /**
     * Boot method to handle cache invalidation.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when assignments are created, updated, or deleted
        static::saved(function ($assignment) {
            self::clearPermissionCache($assignment->teacher_user_id);
        });

        static::deleted(function ($assignment) {
            self::clearPermissionCache($assignment->teacher_user_id);
        });
    }

    /**
     * Clear permission cache for a specific teacher.
     */
    protected static function clearPermissionCache(int $teacherId): void
    {
        Cache::forget("teacher_{$teacherId}_primary_students");
        Cache::forget("teacher_{$teacherId}_accessible_students");

        // Clear class-specific attendance caches (without using tags)
        // Get all classes and clear their specific caches
        $classIds = \DB::table('class_teacher_student')
            ->where('teacher_user_id', $teacherId)
            ->distinct()
            ->pluck('school_class_id');

        foreach ($classIds as $classId) {
            Cache::forget("teacher_{$teacherId}_attendance_class_{$classId}");
        }
    }
}
