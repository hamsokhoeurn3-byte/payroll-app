<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    /** Employees see their own payslips; admins are sent to the payroll workspace. */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return redirect()->route('payroll.index');
        }
        $employee = $user->employee;
        abort_unless($employee, 403);

        // Employees only see payslips once the run has been approved.
        $payslips = $employee->payslips()->whereIn('status', ['processing', 'paid'])
            ->orderByDesc('period')->paginate(12);

        return view('payslips.index', ['payslips' => $payslips, 'employee' => $employee]);
    }

    public function show(Request $request, Payslip $payslip)
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            abort_unless($user->employee && $payslip->employee_id === $user->employee->id
                && in_array($payslip->status, ['processing', 'paid']), 403);
        }
        $payslip->load('employee.department');

        return view('payslips.show', ['payslip' => $payslip]);
    }
}
