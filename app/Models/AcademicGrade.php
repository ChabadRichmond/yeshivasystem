<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicGrade extends Model
{
    use HasFactory;

    protected $table = 'academic_grades';

    protected $fillable = [
        'name',
        'code',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get students in this grade.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'academic_grade_id');
    }

    /**
     * Scope for active grades.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
