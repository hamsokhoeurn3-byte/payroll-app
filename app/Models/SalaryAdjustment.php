<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryAdjustment extends Model
{
    protected $fillable = [
        'employee_id', 'old_base_salary', 'new_base_salary', 'old_allowances', 'new_allowances',
        'effective_date', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return ['effective_date' => 'date'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
