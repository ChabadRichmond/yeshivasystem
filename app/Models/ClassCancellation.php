<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassCancellation extends Model
{
    protected $fillable = [
        'school_class_id',
        'cancelled_date',
        'reason',
        'cancelled_by',
    ];

    protected $casts = [
        'cancelled_date' => 'date',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
