<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Loan::with('employee')->latest();
        if (! $user->isAdmin()) {
            abort_unless($user->employee, 403);
            $query->where('employee_id', $user->employee->id);
        }

        return view('loans.index', ['loans' => $query->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'Only employees can request loans.');

        $data = $request->validate([
            'requested_amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        Loan::create($data + ['employee_id' => $employee->id, 'status' => 'pending']);

        return back()->with('status', 'Loan request submitted.');
    }

    public function decide(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($loan->status === 'pending', 422, 'This request was already decided.');
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'approved_amount' => ['required_if:status,approved', 'nullable', 'numeric', 'min:1'],
            'installments' => ['required_if:status,approved', 'nullable', 'integer', 'min:1', 'max:60'],
        ]);

        if ($data['status'] === 'approved') {
            $loan->update([
                'status' => 'approved',
                'approved_amount' => $data['approved_amount'],
                'outstanding' => $data['approved_amount'],
                'monthly_installment' => round($data['approved_amount'] / $data['installments'], 2),
            ]);
        } else {
            $loan->update(['status' => 'rejected']);
        }

        return back()->with('status', 'Loan '.$data['status'].'. Approved loans are deducted automatically from payroll.');
    }
}
