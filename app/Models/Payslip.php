<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_run_id', 'employee_id', 'payslip_no', 'period', 'base_salary', 'allowances', 'bonus', 'overtime',
        'gross', 'tax', 'social_security', 'benefits', 'loan_deduction', 'other_deductions', 'total_deductions',
        'net', 'status', 'payment_date',
    ];

    protected function casts(): array
    {
        return ['payment_date' => 'date'];
    }

    public function run(): BelongsTo { return $this->belongsTo(PayrollRun::class, 'payroll_run_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }

    public function periodLabel(): string
    {
        return \Carbon\Carbon::createFromFormat('Y-m', $this->period)->format('F Y');
    }
}
