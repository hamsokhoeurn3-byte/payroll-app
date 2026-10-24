<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    protected $fillable = [
        'employee_id', 'requested_amount', 'approved_amount', 'outstanding', 'monthly_installment', 'reason', 'status',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
