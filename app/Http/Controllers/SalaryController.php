<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        return view('salary.index', [
            'employees' => Employee::with('department')
                ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w->where('first_name', 'like', "%$t%")
                    ->orWhere('last_name', 'like', "%$t%")->orWhere('employee_code', 'like', "%$t%")))
                ->orderBy('first_name')->get(),
            'history' => SalaryAdjustment::with('employee')->latest()->take(10)->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'base_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['required', 'numeric', 'min:0'],
            'overtime_rate' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        SalaryAdjustment::create([
            'employee_id' => $employee->id,
            'old_base_salary' => $employee->base_salary, 'new_base_salary' => $data['base_salary'],
            'old_allowances' => $employee->allowances, 'new_allowances' => $data['allowances'],
            'effective_date' => $data['effective_date'], 'reason' => $data['reason'] ?? null,
            'created_by' => $request->user()->id,
        ]);
        $employee->update([
            'base_salary' => $data['base_salary'], 'allowances' => $data['allowances'],
            'overtime_rate' => $data['overtime_rate'],
        ]);

        return back()->with('status', "Compensation updated for {$employee->full_name}. Recalculate any draft payroll to apply it.");
    }
}
