<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', now()->format('Y-m'));

        return view('adjustments.index', [
            'period' => $period,
            'type' => $request->input('type'),
            'adjustments' => PayrollAdjustment::with('employee')->where('period', $period)
                ->when($request->type, fn ($q, $t) => $q->where('type', $t))->latest()->get(),
            'employees' => Employee::active()->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'period' => ['required', 'date_format:Y-m'],
            'type' => ['required', Rule::in(['bonus', 'deduction', 'overtime'])],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);
        PayrollAdjustment::create($data + ['created_by' => $request->user()->id]);

        return redirect()->route('adjustments.index', ['period' => $data['period']])
            ->with('status', 'Adjustment saved. Recalculate the draft payroll to include it.');
    }

    public function destroy(PayrollAdjustment $adjustment): RedirectResponse
    {
        $adjustment->delete();

        return back()->with('status', 'Adjustment removed.');
    }
}
