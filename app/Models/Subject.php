<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all test scores for this subject.
     */
    public function testScores(): HasMany
    {
        return $this->hasMany(TestScore::class);
    }

    /**
     * Get all subject grades (report card grades) for this subject.
     */
    public function subjectGrades(): HasMany
    {
        return $this->hasMany(SubjectGrade::class);
    }

    /**
     * Scope for active subjects.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering by display_order then name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
