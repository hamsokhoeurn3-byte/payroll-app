<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustment extends Model
{
    protected $fillable = ['employee_id', 'period', 'type', 'description', 'amount', 'created_by'];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
