<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public const ANNUAL_LEAVE_DAYS = 20;

    public function __invoke(Request $request)
    {
        return $request->user()->isAdmin() ? $this->admin() : $this->employee($request);
    }

    private function admin()
    {
        $today = today();
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::active()->count();
        $present = Attendance::whereDate('work_date', $today)->whereIn('status', ['present', 'late'])->count();
        $latestRun = PayrollRun::orderByDesc('period')->first();

        return view('dashboard.admin', [
            'totalEmployees' => $totalEmployees,
            'activeEmployees' => $activeEmployees,
            'pendingLeave' => LeaveRequest::where('status', 'pending')->count(),
            'pendingExpenses' => Expense::where('status', 'pending')->sum('amount'),
            'pendingExpenseCount' => Expense::where('status', 'pending')->count(),
            'attendanceRate' => $activeEmployees ? round($present / $activeEmployees * 100, 1) : 0,
            'latestRun' => $latestRun,
            'chartRuns' => PayrollRun::orderByDesc('period')->take(8)->get()->reverse()->values(),
            'departments' => Department::withCount(['employees' => fn ($q) => $q->active()])->orderByDesc('employees_count')->get(),
            'recentEmployees' => Employee::with('department')->latest()->take(4)->get(),
        ]);
    }

    private function employee(Request $request)
    {
        $employee = $request->user()->employee()->with('department')->first();
        abort_unless($employee, 403, 'No employee profile is linked to this account.');

        $usedAnnual = (int) $employee->leaveRequests()->where('status', 'approved')->where('leave_type', 'annual')
            ->whereYear('start_date', now()->year)->sum('days');

        return view('dashboard.employee', [
            'employee' => $employee,
            'todayAttendance' => $employee->attendances()->whereDate('work_date', today())->first(),
            'leaveBalance' => max(self::ANNUAL_LEAVE_DAYS - $usedAnnual, 0),
            'pendingLeave' => $employee->leaveRequests()->where('status', 'pending')->count(),
            'latestPayslip' => $employee->payslips()->orderByDesc('period')->first(),
            'recentPayslips' => $employee->payslips()->orderByDesc('period')->take(5)->get(),
        ]);
    }
}
