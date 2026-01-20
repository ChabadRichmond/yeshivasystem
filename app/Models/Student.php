<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'date_of_birth', 'gender',
        'grade_level', 'academic_grade_id', 'student_id', 'enrollment_status', 'enrollment_date',
        'photo', 'address', 'city', 'province', 'postal_code', 'phone',
        'medical_notes', 'notes', 'user_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if (empty($student->student_id)) {
                $maxId = static::withTrashed()
                    ->where("student_id", "LIKE", "STU%")
                    ->selectRaw("MAX(CAST(SUBSTRING(student_id, 4) AS UNSIGNED)) as max_num")
                    ->value("max_num") ?? 0;
                $student->student_id = "STU" . str_pad($maxId + 1, 4, "0", STR_PAD_LEFT);
            }
        });
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicGrade(): BelongsTo
    {
        return $this->belongsTo(AcademicGrade::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)->withTimestamps();
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_student', 'student_id', 'school_class_id')->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function testScores(): HasMany
    {
        return $this->hasMany(TestScore::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(StudentPermission::class);
    }

    /**
     * Get teaching groups this student belongs to.
     */
    public function teachingGroups(): BelongsToMany
    {
        return $this->belongsToMany(TeachingGroup::class, 'teaching_group_student')
            ->withTimestamps();
    }

    /**
     * Get the teaching group for this student in a specific class.
     */
    public function getTeachingGroupInClass(int $classId): ?TeachingGroup
    {
        return $this->teachingGroups()
            ->where('school_class_id', $classId)
            ->first();
    }

    /**
     * Get the primary teacher for this student in a specific class.
     */
    public function getPrimaryTeacherInClass(int $classId): ?User
    {
        $group = $this->getTeachingGroupInClass($classId);
        return $group?->primaryTeacher;
    }

    /**
     * Get attendance taker assignments for this student.
     */
    public function attendanceTakerAssignments(): HasMany
    {
        return $this->hasMany(ClassAttendanceTaker::class);
    }

    /**
     * Get the attendance taker for this student in a specific class.
     */
    public function getAttendanceTakerInClass(int $classId): ?User
    {
        $assignment = $this->attendanceTakerAssignments()
            ->where('school_class_id', $classId)
            ->first();
        return $assignment?->attendanceTaker;
    }

    /**
     * Check if student is on permission for a specific date
     */
    public function isOnPermission($date): bool
    {
        return $this->permissions()
            ->coversDate($date)
            ->exists();
    }

    /**
     * Get active permission for a specific date (if any)
     */
    public function getPermissionForDate($date): ?StudentPermission
    {
        return $this->permissions()
            ->coversDate($date)
            ->first();
    }

    /**
     * Get average grade for a specific subject.
     */
    public function getSubjectAverage(int $subjectId, ?string $startDate = null, ?string $endDate = null): ?float
    {
        $query = $this->testScores()->where('subject_id', $subjectId);

        if ($startDate && $endDate) {
            $query->whereBetween('test_date', [$startDate, $endDate]);
        }

        return TestScore::calculateWeightedAverage($query->get());
    }
}
