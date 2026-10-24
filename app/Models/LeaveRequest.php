<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id', 'leave_type', 'start_date', 'end_date', 'days', 'reason', 'status', 'decided_by', 'decided_at',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'decided_at' => 'datetime'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function decider(): BelongsTo { return $this->belongsTo(User::class, 'decided_by'); }
}
