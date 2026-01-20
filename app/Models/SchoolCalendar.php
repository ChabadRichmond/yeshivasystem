<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SchoolCalendar extends Model
{
    use HasFactory;

    protected $table = 'school_calendar';

    protected $fillable = [
        'date',
        'type',
        'name',
        'description',
        'affects_all_classes',
    ];

    protected $casts = [
        'date' => 'date',
        'affects_all_classes' => 'boolean',
    ];

    // Relationships (many-to-many with classes)
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'calendar_class', 'school_calendar_id', 'school_class_id');
    }

    // Scopes
    public function scopeHolidays($query)
    {
        return $query->where('type', 'holiday');
    }

    public function scopeHalfDays($query)
    {
        return $query->where('type', 'half_day');
    }

    public function scopeVacations($query)
    {
        return $query->where('type', 'vacation');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeAffectsClass($query, $classId = null)
    {
        return $query->where(function ($q) use ($classId) {
            $q->where('affects_all_classes', true);
            if ($classId) {
                $q->orWhereHas('classes', function ($subQ) use ($classId) {
                    $subQ->where('school_classes.id', $classId);
                });
            }
        });
    }

    // Helper methods
    public static function isHoliday($date, $classId = null): bool
    {
        return static::forDate($date)
            ->whereIn('type', ['holiday', 'vacation'])
            ->affectsClass($classId)
            ->exists();
    }

    public static function isHalfDay($date, $classId = null): bool
    {
        return static::forDate($date)
            ->where('type', 'half_day')
            ->affectsClass($classId)
            ->exists();
    }

    public static function getCalendarEntry($date, $classId = null)
    {
        return static::forDate($date)
            ->affectsClass($classId)
            ->first();
    }

    // Get list of affected class names for display
    public function getAffectedClassesText(): string
    {
        if ($this->affects_all_classes) {
            return 'All classes';
        }
        
        $classNames = $this->classes->pluck('name')->toArray();
        return empty($classNames) ? 'No classes specified' : implode(', ', $classNames);
    }
}
