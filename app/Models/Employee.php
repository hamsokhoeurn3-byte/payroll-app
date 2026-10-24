<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user_id', 'department_id', 'employee_code', 'first_name', 'last_name', 'email', 'phone',
        'position', 'location', 'status', 'hire_date', 'base_salary', 'allowances', 'overtime_rate',
        'bank_name', 'bank_account',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'base_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function payslips(): HasMany { return $this->hasMany(Payslip::class); }
    public function loans(): HasMany { return $this->hasMany(Loan::class); }
    public function expenses(): HasMany { return $this->hasMany(Expense::class); }
    public function adjustments(): HasMany { return $this->hasMany(PayrollAdjustment::class); }
    public function salaryAdjustments(): HasMany { return $this->hasMany(SalaryAdjustment::class); }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public static function nextCode(): string
    {
        $last = static::max('employee_code');
        $n = $last ? ((int) substr($last, 4)) + 1 : 1001;

        return 'EMP-'.$n;
    }
}
