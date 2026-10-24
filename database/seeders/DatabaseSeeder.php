<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\LeaveRequest;
use App\Models\Loan;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Administrator
        User::create([
            'name' => 'Alex Morgan', 'email' => 'admin@payroll.test',
            'password' => 'password', 'role' => 'admin',
        ]);

        $departments = collect([
            ['Engineering', 'Liam Carter', 'Austin + Remote'],
            ['Product', 'Olivia Martin', 'New York'],
            ['Finance', 'Noah Williams', 'Chicago'],
            ['People', 'Ava Thompson', 'Toronto'],
            ['Sales', 'Ethan Reed', 'Seattle'],
            ['Marketing', 'Sophia Bennett', 'London'],
        ])->mapWithKeys(fn ($d) => [$d[0] => Department::create([
            'name' => $d[0], 'head_name' => $d[1], 'location' => $d[2], 'status' => 'active',
        ])]);

        // first, last, dept, position, location, status, base, allowances, overtime rate
        $people = [
            ['Olivia', 'Martin', 'Product', 'Product Designer', 'New York', 'active', 7200, 400, 38],
            ['Liam', 'Carter', 'Engineering', 'Senior Engineer', 'Austin', 'active', 9400, 600, 50],
            ['Sophia', 'Bennett', 'Marketing', 'Marketing Lead', 'London', 'on_leave', 7800, 450, 42],
            ['Noah', 'Williams', 'Finance', 'Finance Analyst', 'Chicago', 'active', 6900, 350, 36],
            ['Ava', 'Thompson', 'People', 'People Partner', 'Toronto', 'active', 8200, 500, 44],
            ['Ethan', 'Reed', 'Sales', 'Sales Executive', 'Seattle', 'inactive', 6500, 300, 34],
            ['Maya', 'Patel', 'Engineering', 'QA Engineer', 'Remote', 'active', 6100, 250, 32],
        ];

        $employees = [];
        foreach ($people as $i => [$first, $last, $dept, $position, $loc, $status, $base, $allow, $ot]) {
            $email = strtolower($first).'@payroll.test';
            $user = User::create([
                'name' => "$first $last", 'email' => $email, 'password' => 'password',
                'role' => 'employee', 'is_active' => $status !== 'inactive',
            ]);
            $employees[$first] = Employee::create([
                'user_id' => $user->id, 'department_id' => $departments[$dept]->id,
                'employee_code' => 'EMP-'.(1001 + $i), 'first_name' => $first, 'last_name' => $last,
                'email' => $email, 'phone' => '+1 555 010'.$i, 'position' => $position, 'location' => $loc,
                'status' => $status, 'hire_date' => now()->subMonths(6 + $i * 5)->startOfMonth(),
                'base_salary' => $base, 'allowances' => $allow, 'overtime_rate' => $ot,
                'bank_name' => 'Demo Bank', 'bank_account' => '000'.(123400 + $i),
            ]);
        }

        // Attendance for the last 20 weekdays
        $active = collect($employees)->filter(fn ($e) => $e->status === 'active');
        for ($d = 0, $made = 0; $made < 20; $d++) {
            $date = today()->subDays($d);
            if ($date->isWeekend()) {
                continue;
            }
            $made++;
            foreach ($active as $e) {
                $in = fake()->dateTimeBetween('08:40', '09:25')->format('H:i:00');
                $outHour = fake()->numberBetween(17, 19);
                $out = sprintf('%02d:%02d:00', $outHour, fake()->numberBetween(0, 59));
                $hours = round((strtotime($out) - strtotime($in)) / 3600, 2);
                Attendance::create([
                    'employee_id' => $e->id, 'work_date' => $date, 'check_in' => $in, 'check_out' => $out,
                    'hours' => $hours, 'overtime_hours' => max(round($hours - 8, 2), 0),
                    'status' => $in > '09:15:00' ? 'late' : 'present',
                ]);
            }
        }

        // Leave, loans, expenses
        LeaveRequest::create(['employee_id' => $employees['Maya']->id, 'leave_type' => 'personal',
            'start_date' => today()->addDays(10), 'end_date' => today()->addDays(11), 'days' => 2,
            'reason' => 'Family event', 'status' => 'pending']);
        LeaveRequest::create(['employee_id' => $employees['Olivia']->id, 'leave_type' => 'annual',
            'start_date' => today()->addDays(21), 'end_date' => today()->addDays(25), 'days' => 5,
            'reason' => 'Vacation', 'status' => 'pending']);
        LeaveRequest::create(['employee_id' => $employees['Sophia']->id, 'leave_type' => 'annual',
            'start_date' => today()->subDays(2), 'end_date' => today()->addDays(2), 'days' => 5,
            'status' => 'approved', 'decided_by' => 1, 'decided_at' => now()->subDays(10)]);

        Loan::create(['employee_id' => $employees['Liam']->id, 'requested_amount' => 4000, 'approved_amount' => 4000,
            'outstanding' => 2500, 'monthly_installment' => 500, 'reason' => 'Home repair', 'status' => 'approved']);
        Loan::create(['employee_id' => $employees['Maya']->id, 'requested_amount' => 2500, 'reason' => 'Laptop', 'status' => 'pending']);

        Expense::create(['employee_id' => $employees['Liam']->id, 'category' => 'Travel', 'expense_date' => today()->subDays(3),
            'amount' => 684.20, 'description' => 'Conference flights', 'status' => 'pending']);
        Expense::create(['employee_id' => $employees['Olivia']->id, 'category' => 'Software', 'expense_date' => today()->subDays(5),
            'amount' => 124.00, 'description' => 'Design plugin licence', 'status' => 'approved']);

        // A completed payroll run for last month, plus bonus/deduction examples
        $lastMonth = now()->subMonth()->format('Y-m');
        PayrollAdjustment::create(['employee_id' => $employees['Liam']->id, 'period' => $lastMonth, 'type' => 'bonus',
            'description' => 'Release bonus', 'amount' => 800, 'created_by' => 1]);
        PayrollAdjustment::create(['employee_id' => $employees['Olivia']->id, 'period' => $lastMonth, 'type' => 'bonus',
            'description' => 'Performance bonus', 'amount' => 450, 'created_by' => 1]);

        $payroll = app(PayrollService::class);
        [$run] = $payroll->generate($lastMonth, 1);
        $payroll->approve($run, 1);
        $payroll->markPaid($run->fresh());

        // Current month stays as an open draft/adjustment month
        PayrollAdjustment::create(['employee_id' => $employees['Noah']->id, 'period' => now()->format('Y-m'), 'type' => 'bonus',
            'description' => 'Quarter-end bonus', 'amount' => 550, 'created_by' => 1]);
    }
}
