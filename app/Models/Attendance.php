<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = ['employee_id', 'work_date', 'check_in', 'check_out', 'hours', 'overtime_hours', 'status'];

    protected function casts(): array
    {
        return ['work_date' => 'date', 'hours' => 'decimal:2', 'overtime_hours' => 'decimal:2'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
