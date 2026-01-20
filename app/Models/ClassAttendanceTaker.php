<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassAttendanceTaker extends Model
{
    protected $fillable = [
        'school_class_id',
        'student_id',
        'attendance_taker_id',
    ];

    /**
     * The class this assignment belongs to
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * The student this assignment is for
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * The teacher who takes attendance for this student
     */
    public function attendanceTaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendance_taker_id');
    }
}
