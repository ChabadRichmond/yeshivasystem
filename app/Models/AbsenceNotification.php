<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'student_id',
        'date',
        'method',
        'notified_to',
        'contact_used',
        'message',
        'sent_at',
        'sent_by',
        'response',
        'response_at',
    ];

    protected $casts = [
        'date' => 'date',
        'sent_at' => 'datetime',
        'response_at' => 'datetime',
    ];

    // Relationships
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // Scopes
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('sent_at');
    }

    public function scopeWithResponse($query)
    {
        return $query->whereNotNull('response');
    }

    // Helper methods
    public function markAsSent($userId = null)
    {
        $this->update([
            'sent_at' => now(),
            'sent_by' => $userId ?? auth()->id(),
        ]);
    }

    public function addResponse($response)
    {
        $this->update([
            'response' => $response,
            'response_at' => now(),
        ]);
    }
}
