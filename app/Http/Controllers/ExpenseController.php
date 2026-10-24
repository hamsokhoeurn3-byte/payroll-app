<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Expense::with('employee')->latest('expense_date');
        if (! $user->isAdmin()) {
            abort_unless($user->employee, 403);
            $query->where('employee_id', $user->employee->id);
        }
        $query->when($request->status, fn ($q, $s) => $q->where('status', $s));

        return view('expenses.index', ['expenses' => $query->paginate(15)->withQueryString()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'Only employees can submit expense claims.');

        $data = $request->validate([
            'category' => ['required', 'string', 'max:60'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        Expense::create($data + ['employee_id' => $employee->id, 'status' => 'pending']);

        return back()->with('status', 'Expense claim submitted.');
    }

    public function decide(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:approved,rejected,reimbursed']]);
        $allowed = ['pending' => ['approved', 'rejected'], 'approved' => ['reimbursed']];
        abort_unless(in_array($data['status'], $allowed[$expense->status] ?? [], true), 422, 'Invalid status change.');

        $expense->update(['status' => $data['status']]);

        return back()->with('status', 'Expense marked '.$data['status'].'.');
    }
}
