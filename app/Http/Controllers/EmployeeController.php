<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with('department')
            ->when($request->q, fn ($q, $term) => $q->where(function ($w) use ($term) {
                $w->where('first_name', 'like', "%$term%")->orWhere('last_name', 'like', "%$term%")
                    ->orWhere('employee_code', 'like', "%$term%")->orWhere('email', 'like', "%$term%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->department, fn ($q, $d) => $q->where('department_id', $d))
            ->orderBy('first_name')->paginate(15)->withQueryString();

        return view('employees.index', ['employees' => $employees, 'departments' => Department::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('employees.create', [
            'employee' => new Employee(['status' => 'active', 'hire_date' => today()]),
            'departments' => Department::orderBy('name')->get(),
            'nextCode' => Employee::nextCode(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $createLogin = $request->boolean('create_login');
        if ($createLogin) {
            $request->validate(['password' => ['required', 'string', 'min:8']]);
        }

        DB::transaction(function () use ($data, $createLogin, $request) {
            $employee = Employee::create($data + ['employee_code' => Employee::nextCode()]);
            if ($createLogin) {
                $user = User::create([
                    'name' => $employee->full_name, 'email' => $employee->email,
                    'password' => $request->password, 'role' => 'employee',
                ]);
                $employee->update(['user_id' => $user->id]);
            }
        });

        return redirect()->route('employees.index')->with('status', 'Employee created.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['department', 'user']);

        return view('employees.show', [
            'employee' => $employee,
            'payslips' => $employee->payslips()->orderByDesc('period')->take(6)->get(),
            'loans' => $employee->loans()->latest()->take(5)->get(),
        ]);
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', ['employee' => $employee, 'departments' => Department::orderBy('name')->get()]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->validated($request, $employee));
        if ($employee->user) {
            $employee->user->update(['name' => $employee->full_name, 'email' => $employee->email,
                'is_active' => $employee->status !== 'inactive']);
        }

        return redirect()->route('employees.show', $employee)->with('status', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->payslips()->exists()) {
            return back()->withErrors(['delete' => 'This employee has payslips. Set the status to Inactive instead of deleting.']);
        }
        $user = $employee->user;
        $employee->delete();
        $user?->delete();

        return redirect()->route('employees.index')->with('status', 'Employee deleted.');
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('employees', 'email')->ignore($employee?->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'on_leave', 'inactive'])],
            'hire_date' => ['nullable', 'date'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account' => ['nullable', 'string', 'max:60'],
        ]) + ['allowances' => $request->input('allowances', 0) ?: 0, 'overtime_rate' => $request->input('overtime_rate', 0) ?: 0];
    }
}
