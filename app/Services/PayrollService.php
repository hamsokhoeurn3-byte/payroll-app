<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /** Calculate one employee's pay for a period (YYYY-MM). Pure calculation, nothing is saved. */
    public function calculate(Employee $employee, string $period): array
    {
        $adjustments = PayrollAdjustment::where('employee_id', $employee->id)
            ->where('period', $period)->get()->groupBy('type');

        $bonus = (float) ($adjustments['bonus'] ?? collect())->sum('amount');
        $overtimeManual = (float) ($adjustments['overtime'] ?? collect())->sum('amount');
        $otherDeductions = (float) ($adjustments['deduction'] ?? collect())->sum('amount');

        // Overtime from attendance hours in this month, priced at the employee's hourly rate.
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $overtimeHours = (float) $employee->attendances()
            ->whereBetween('work_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->sum('overtime_hours');
        $overtimeAuto = $overtimeHours * (float) $employee->overtime_rate * (float) config('payroll.overtime_multiplier');

        $base = (float) $employee->base_salary;
        $allowances = (float) $employee->allowances;
        $overtime = round($overtimeManual + $overtimeAuto, 2);
        $gross = round($base + $allowances + $bonus + $overtime, 2);

        $tax = $this->incomeTax($gross);
        $social = round(min($base * (float) config('payroll.social_security_rate'), (float) config('payroll.social_security_cap')), 2);
        $benefits = (float) config('payroll.benefits_deduction');
        $loan = $this->loanInstallment($employee);

        $totalDeductions = round($tax + $social + $benefits + $loan + $otherDeductions, 2);
        $net = round($gross - $totalDeductions, 2);

        return [
            'base_salary' => $base, 'allowances' => $allowances, 'bonus' => $bonus, 'overtime' => $overtime,
            'gross' => $gross, 'tax' => $tax, 'social_security' => $social, 'benefits' => $benefits,
            'loan_deduction' => $loan, 'other_deductions' => $otherDeductions,
            'total_deductions' => $totalDeductions, 'net' => max($net, 0),
        ];
    }

    public function incomeTax(float $gross): float
    {
        $brackets = config('payroll.tax_brackets');
        $tax = 0.0;
        foreach ($brackets as $i => $b) {
            $upper = $brackets[$i + 1]['from'] ?? PHP_FLOAT_MAX;
            if ($gross > $b['from']) {
                $tax += (min($gross, $upper) - $b['from']) * $b['rate'];
            }
        }

        return round($tax, 2);
    }

    private function loanInstallment(Employee $employee): float
    {
        return (float) $employee->loans()->where('status', 'approved')->where('outstanding', '>', 0)->get()
            ->sum(fn (Loan $l) => min((float) $l->monthly_installment, (float) $l->outstanding));
    }

    /**
     * Create (or top up) the draft run for a period with a payslip for every active employee
     * that does not already have one. Returns [run, createdCount].
     */
    public function generate(string $period, ?int $userId = null): array
    {
        return DB::transaction(function () use ($period, $userId) {
            $run = PayrollRun::firstOrCreate(
                ['period' => $period],
                ['status' => 'draft', 'created_by' => $userId, 'payment_date' => $this->payDate($period)]
            );

            if ($run->status !== 'draft') {
                return [$run, 0];
            }

            $created = 0;
            $existing = $run->payslips()->pluck('employee_id')->all();
            foreach (Employee::active()->whereNotIn('id', $existing)->get() as $employee) {
                $data = $this->calculate($employee, $period);
                Payslip::create($data + [
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'payslip_no' => 'PAY-'.str_replace('-', '', $period).'-'.str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT),
                    'period' => $period,
                    'status' => 'pending',
                    'payment_date' => $run->payment_date,
                ]);
                $created++;
            }
            $run->recalculateTotals();

            return [$run, $created];
        });
    }

    /** Recalculate every payslip of a draft run (after adjustments/salary changes). */
    public function recalculate(PayrollRun $run): void
    {
        abort_unless($run->status === 'draft', 422, 'Only draft runs can be recalculated.');
        DB::transaction(function () use ($run) {
            foreach ($run->payslips()->with('employee')->get() as $slip) {
                $slip->update($this->calculate($slip->employee, $run->period));
            }
            $run->recalculateTotals();
        });
    }

    public function approve(PayrollRun $run, int $userId): void
    {
        abort_unless($run->status === 'draft', 422, 'Only draft runs can be approved.');
        $run->payslips()->update(['status' => 'processing']);
        $run->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
    }

    /** Mark an approved run as paid and apply loan repayments. */
    public function markPaid(PayrollRun $run): void
    {
        abort_unless($run->status === 'approved', 422, 'Approve the run before marking it paid.');
        DB::transaction(function () use ($run) {
            foreach ($run->payslips()->where('loan_deduction', '>', 0)->get() as $slip) {
                $remaining = (float) $slip->loan_deduction;
                $loans = Loan::where('employee_id', $slip->employee_id)->where('status', 'approved')
                    ->where('outstanding', '>', 0)->orderBy('id')->get();
                foreach ($loans as $loan) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $pay = min($remaining, (float) $loan->monthly_installment, (float) $loan->outstanding);
                    $loan->outstanding = round((float) $loan->outstanding - $pay, 2);
                    if ($loan->outstanding <= 0) {
                        $loan->status = 'repaid';
                    }
                    $loan->save();
                    $remaining -= $pay;
                }
            }
            $run->payslips()->update(['status' => 'paid']);
            $run->update(['status' => 'paid', 'paid_at' => now()]);
        });
    }

    private function payDate(string $period): Carbon
    {
        $month = Carbon::createFromFormat('Y-m', $period)->startOfMonth();

        return $month->copy()->day(min((int) config('payroll.pay_day'), $month->daysInMonth));
    }
}
