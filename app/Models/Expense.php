<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = ['employee_id', 'category', 'expense_date', 'amount', 'description', 'status'];

    protected function casts(): array
    {
        return ['expense_date' => 'date'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
