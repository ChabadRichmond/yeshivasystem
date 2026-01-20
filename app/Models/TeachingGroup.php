<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TeachingGroup extends Model
{
    protected $fillable = [
        'school_class_id',
        'name',
        'primary_teacher_id',
        'display_order',
    ];

    /**
     * The class this teaching group belongs to
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * The primary teacher for this teaching group
     */
    public function primaryTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_teacher_id');
    }

    /**
     * Students assigned to this teaching group
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'teaching_group_student')
            ->withTimestamps();
    }

    /**
     * Scope to order by display_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
