<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'period', 'status', 'payment_date', 'total_gross', 'total_deductions', 'total_net',
        'employee_count', 'created_by', 'approved_by', 'approved_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date', 'approved_at' => 'datetime', 'paid_at' => 'datetime',
            'total_gross' => 'decimal:2', 'total_deductions' => 'decimal:2', 'total_net' => 'decimal:2',
        ];
    }

    public function payslips(): HasMany { return $this->hasMany(Payslip::class); }

    public function periodLabel(): string
    {
        return \Carbon\Carbon::createFromFormat('Y-m', $this->period)->format('F Y');
    }

    public function recalculateTotals(): void
    {
        $this->update([
            'total_gross' => $this->payslips()->sum('gross'),
            'total_deductions' => $this->payslips()->sum('total_deductions'),
            'total_net' => $this->payslips()->sum('net'),
            'employee_count' => $this->payslips()->count(),
        ]);
    }
}
