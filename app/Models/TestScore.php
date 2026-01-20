<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'school_class_id',
        'test_name',
        'test_date',
        'score',
        'max_score',
        'percentage',
        'letter_grade',
        'weight',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'test_date' => 'date',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($testScore) {
            // Auto-calculate percentage if score and max_score are set
            if ($testScore->score !== null && $testScore->max_score > 0) {
                $testScore->percentage = round(($testScore->score / $testScore->max_score) * 100, 2);
            }

            // Auto-calculate letter grade from percentage
            if ($testScore->percentage !== null && !$testScore->letter_grade) {
                $testScore->letter_grade = static::percentageToLetterGrade($testScore->percentage);
            }
        });
    }

    /**
     * Convert percentage to letter grade.
     */
    public static function percentageToLetterGrade(float $percentage): string
    {
        return match (true) {
            $percentage >= 97 => 'A+',
            $percentage >= 93 => 'A',
            $percentage >= 90 => 'A-',
            $percentage >= 87 => 'B+',
            $percentage >= 83 => 'B',
            $percentage >= 80 => 'B-',
            $percentage >= 77 => 'C+',
            $percentage >= 73 => 'C',
            $percentage >= 70 => 'C-',
            $percentage >= 67 => 'D+',
            $percentage >= 63 => 'D',
            $percentage >= 60 => 'D-',
            default => 'F',
        };
    }

    /**
     * Get the student this test score belongs to.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the subject this test score belongs to.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the class this test was taken in.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * Get the user who recorded this test score.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope for a specific student.
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope for a specific subject.
     */
    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Scope for a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('test_date', [$startDate, $endDate]);
    }

    /**
     * Calculate weighted average for a collection of test scores.
     */
    public static function calculateWeightedAverage($testScores): ?float
    {
        if ($testScores->isEmpty()) {
            return null;
        }

        $totalWeight = $testScores->sum('weight');
        if ($totalWeight == 0) {
            return null;
        }

        $weightedSum = $testScores->sum(function ($score) {
            return $score->percentage * $score->weight;
        });

        return round($weightedSum / $totalWeight, 2);
    }
}
