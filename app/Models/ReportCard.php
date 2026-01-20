<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'term', 'academic_year', 'status', 'teacher_comments',
        'admin_comments', 'created_by', 'approved_by', 'approved_at', 'published_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function grades()
    {
        return $this->hasMany(SubjectGrade::class);
    }

    public function subjectGrades()
    {
        return $this->hasMany(SubjectGrade::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
