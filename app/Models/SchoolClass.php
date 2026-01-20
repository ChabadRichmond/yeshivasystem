<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'school_classes';

    protected $fillable = [
        'name',
        'display_order',
        'grade_level',
        'teacher_id',
        'academic_year',
        'schedule_time',
        'description',
        'is_active',
    ];

    protected $casts = [
        'schedule_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    /**
     * Get the primary teacher for this class.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get all teachers assigned to this class (many-to-many).
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_teacher')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    /**
     * Get students enrolled in this class.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_student', 'school_class_id', 'student_id')
                    ->withTimestamps();
    }

    /**
     * Get attendance records for this class.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'school_class_id');
    }

    /**
     * Get active classes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get classes for current academic year.
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('academic_year', date('Y'));
    }

    /**
     * Get teacher-student assignments for this class (legacy - being phased out).
     */
    public function teacherStudentAssignments()
    {
        return $this->hasMany(ClassTeacherStudent::class, 'school_class_id');
    }

    /**
     * Get teaching groups for this class.
     */
    public function teachingGroups(): HasMany
    {
        return $this->hasMany(TeachingGroup::class, 'school_class_id')->ordered();
    }

    /**
     * Get attendance taker assignments for this class.
     */
    public function attendanceTakerAssignments(): HasMany
    {
        return $this->hasMany(ClassAttendanceTaker::class, 'school_class_id');
    }

    /**
     * Get students assigned to a specific teacher's teaching group in this class.
     */
    public function getStudentsForTeacher(int $teacherId): \Illuminate\Support\Collection
    {
        // Get students from teaching groups where teacher is primary
        $teachingGroup = $this->teachingGroups()
            ->where('primary_teacher_id', $teacherId)
            ->first();

        if ($teachingGroup) {
            return $teachingGroup->students;
        }

        return collect();
    }

    /**
     * Get the teaching group a student belongs to in this class.
     */
    public function getTeachingGroupForStudent(int $studentId): ?TeachingGroup
    {
        return $this->teachingGroups()
            ->whereHas('students', fn($q) => $q->where('students.id', $studentId))
            ->first();
    }

    /**
     * Get the primary teacher for a student in this class.
     */
    public function getPrimaryTeacherForStudent(int $studentId): ?User
    {
        $teachingGroup = $this->getTeachingGroupForStudent($studentId);
        return $teachingGroup?->primaryTeacher;
    }

    /**
     * Get the attendance taker for a student in this class.
     */
    public function getAttendanceTakerForStudent(int $studentId): ?User
    {
        $assignment = $this->attendanceTakerAssignments()
            ->where('student_id', $studentId)
            ->with('attendanceTaker')
            ->first();

        return $assignment?->attendanceTaker;
    }

    /**
     * Get students sorted for a teacher's attendance view.
     * Students in their teaching group OR assigned to them as attendance taker appear first.
     */
    public function getStudentsSortedForTeacher(int $teacherId): \Illuminate\Support\Collection
    {
        // Get student IDs from teacher's teaching group
        $teachingGroup = $this->teachingGroups()
            ->where('primary_teacher_id', $teacherId)
            ->first();
        $teachingGroupStudentIds = $teachingGroup?->students->pluck('id')->toArray() ?? [];

        // Get student IDs where teacher is attendance taker
        $attendanceTakerStudentIds = $this->attendanceTakerAssignments()
            ->where('attendance_taker_id', $teacherId)
            ->pluck('student_id')
            ->toArray();

        $priorityStudentIds = array_unique(array_merge($attendanceTakerStudentIds, $teachingGroupStudentIds));

        if (empty($priorityStudentIds)) {
            return $this->students()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        return $this->students()
            ->orderByRaw('FIELD(students.id, ' . implode(',', $priorityStudentIds) . ') DESC')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Get weekly schedules for this class.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'school_class_id');
    }

    /**
     * Get cancellations for this class.
     */
    public function cancellations(): HasMany
    {
        return $this->hasMany(ClassCancellation::class, 'school_class_id');
    }

    /**
     * Get schedule for a specific day of week.
     */
    public function getScheduleForDay(int $dayOfWeek): ?ClassSchedule
    {
        return $this->schedules()->where('day_of_week', $dayOfWeek)->where('is_active', true)->first();
    }

    /**
     * Check if class is cancelled on a specific date.
     */
    public function isCancelledOn($date): bool
    {
        return $this->cancellations()->where('cancelled_date', $date)->exists();
    }

    /**
     * Get start time for a specific date (checks weekly schedule first, falls back to default).
     */
    public function getStartTimeForDate($date): ?string
    {
        $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;
        $schedule = $this->getScheduleForDay($dayOfWeek);
        
        if ($schedule) {
            return \Carbon\Carbon::parse($schedule->start_time)->format('H:i');
        }
        
        // Fall back to the default schedule_time
        return $this->schedule_time ? \Carbon\Carbon::parse($this->schedule_time)->format('H:i') : null;
    }
}
