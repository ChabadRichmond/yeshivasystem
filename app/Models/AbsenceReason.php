<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbsenceReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'is_excused',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_excused' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExcused($query)
    {
        return $query->where('is_excused', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
