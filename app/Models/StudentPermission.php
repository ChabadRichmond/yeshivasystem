<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class StudentPermission extends Model
{
    protected $fillable = [
        'student_id',
        'start_date',
        'end_date',
        'first_excused_class_id',
        'last_excused_class_id',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function firstExcusedClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'first_excused_class_id');
    }

    public function lastExcusedClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'last_excused_class_id');
    }

    /**
     * Check if a given date falls within this permission period
     */
    public function coversDate($date): bool
    {
        $checkDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $checkDate->between($this->start_date, $this->end_date);
    }

    /**
     * Scope to find permissions that cover a specific date
     */
    public function scopeCoversDate($query, $date)
    {
        return $query->where('start_date', '<=', $date)
                     ->where('end_date', '>=', $date);
    }

    /**
     * Scope to find permissions that overlap with a date range
     */
    public function scopeOverlapsDateRange($query, $startDate, $endDate)
    {
        return $query->where('start_date', '<=', $endDate)
                     ->where('end_date', '>=', $startDate);
    }

    /**
     * Check if this permission is a full-day permission (no class restrictions)
     */
    public function isFullDay(): bool
    {
        return $this->first_excused_class_id === null && $this->last_excused_class_id === null;
    }

    /**
     * Check if a specific class is covered by this permission on a given date.
     *
     * @param string|Carbon $date The date to check
     * @param int $classId The class ID to check
     * @param array $orderedClassIds Optional: Pre-computed ordered class IDs for the day
     * @return bool True if the class is covered by this permission
     */
    public function coversClass($date, int $classId, array $orderedClassIds = []): bool
    {
        // First check if date is covered
        if (!$this->coversDate($date)) {
            return false;
        }

        // If full day permission, all classes are covered
        if ($this->isFullDay()) {
            return true;
        }

        // If we don't have ordered class IDs, we need to determine if this class falls
        // within the permission range. For now, just check direct match.
        if (empty($orderedClassIds)) {
            // Simple check: is this the first or last excused class?
            if ($this->first_excused_class_id === $classId || $this->last_excused_class_id === $classId) {
                return true;
            }
            // Without ordering info, we can't determine if it's between
            // Default to not covered if we can't determine
            return false;
        }

        // Find positions in the ordered list
        $classPosition = array_search($classId, $orderedClassIds);
        if ($classPosition === false) {
            return false; // Class not in the ordered list
        }

        $firstPosition = $this->first_excused_class_id
            ? array_search($this->first_excused_class_id, $orderedClassIds)
            : 0; // If no first specified, start from beginning

        $lastPosition = $this->last_excused_class_id
            ? array_search($this->last_excused_class_id, $orderedClassIds)
            : count($orderedClassIds) - 1; // If no last specified, go to end

        // Handle case where first/last class isn't in today's schedule
        if ($firstPosition === false) $firstPosition = 0;
        if ($lastPosition === false) $lastPosition = count($orderedClassIds) - 1;

        // Check if the class falls within the excused range (inclusive)
        return $classPosition >= $firstPosition && $classPosition <= $lastPosition;
    }

    /**
     * Get formatted class range for display
     */
    public function getClassRangeAttribute(): ?string
    {
        if ($this->isFullDay()) {
            return null;
        }

        $first = $this->firstExcusedClass?->name ?? 'Start of day';
        $last = $this->lastExcusedClass?->name ?? 'End of day';

        if ($first === $last || ($this->first_excused_class_id && !$this->last_excused_class_id)) {
            return "From: {$first}";
        }

        return "{$first} → {$last}";
    }
}
