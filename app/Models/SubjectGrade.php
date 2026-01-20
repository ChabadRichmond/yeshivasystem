<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectGrade extends Model
{
    use HasFactory;

    protected $table = 'grades';

    protected $fillable = [
        'report_card_id',
        'subject_id',
        'subject', // Legacy field for backward compatibility
        'grade',
        'percentage',
        'comments',
        'calculated_from_tests',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'calculated_from_tests' => 'boolean',
    ];

    /**
     * Get the report card this grade belongs to.
     */
    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }

    /**
     * Get the subject this grade is for.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the subject name (from relationship or legacy field).
     */
    public function getSubjectNameAttribute(): string
    {
        return $this->subject?->name ?? $this->attributes['subject'] ?? 'Unknown';
    }

    /**
     * Convert percentage to letter grade.
     */
    public static function percentageToLetterGrade(float $percentage): string
    {
        return TestScore::percentageToLetterGrade($percentage);
    }

    /**
     * Calculate grade from test scores for a student/subject.
     */
    public static function calculateFromTestScores(int $studentId, int $subjectId, ?string $startDate = null, ?string $endDate = null): ?array
    {
        $query = TestScore::where('student_id', $studentId)
            ->where('subject_id', $subjectId);

        if ($startDate && $endDate) {
            $query->whereBetween('test_date', [$startDate, $endDate]);
        }

        $testScores = $query->get();

        if ($testScores->isEmpty()) {
            return null;
        }

        $average = TestScore::calculateWeightedAverage($testScores);

        return [
            'percentage' => $average,
            'letter_grade' => $average ? static::percentageToLetterGrade($average) : null,
            'test_count' => $testScores->count(),
        ];
    }
}
