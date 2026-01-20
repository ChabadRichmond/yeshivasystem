<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        "student_id", "school_class_id", "date", "status", "arrival_time",
        "minutes_late", "minutes_early", "class_start_time", "class_end_time", "notes", "recorded_by",
        "left_early", "left_early_excused",
        "absence_reason_id", "excused_by", "notified_parent",
    ];

    protected $casts = [
        "date" => "date",
        "arrival_time" => "datetime:H:i",
        "class_start_time" => "datetime:H:i",
        "class_end_time" => "datetime:H:i",
        "minutes_late" => "integer",
        "minutes_early" => "integer",
        "left_early" => "boolean",
        "left_early_excused" => "boolean",
        "notified_parent" => "boolean",
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, "school_class_id");
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "recorded_by");
    }

    public function absenceReason(): BelongsTo
    {
        return $this->belongsTo(AbsenceReason::class);
    }

    public function excusedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, "excused_by");
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AbsenceNotification::class);
    }

    // Scopes
    public function scopeAbsent($query)
    {
        return $query->whereIn('status', ['absent', 'excused']);
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'late']);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('school_class_id', $classId);
    }

    public function scopeNotNotified($query)
    {
        return $query->where('notified_parent', false);
    }

    // Helper methods
    public function isAbsent(): bool
    {
        return in_array($this->status, ['absent', 'excused']);
    }

    public function isExcused(): bool
    {
        return $this->status === 'excused' || 
               ($this->absenceReason && $this->absenceReason->is_excused);
    }

    public function markAsNotified($userId = null): void
    {
        $this->update(['notified_parent' => true]);
    }

    public function excuse($userId = null, $reasonId = null): void
    {
        $this->update([
            'status' => 'excused',
            'excused_by' => $userId ?? auth()->id(),
            'absence_reason_id' => $reasonId,
        ]);
    }
}
