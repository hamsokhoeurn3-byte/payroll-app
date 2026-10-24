<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('departments.index', [
            'departments' => Department::withCount('employees')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Department::create($this->validated($request));

        return back()->with('status', 'Department created.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validated($request, $department));

        return back()->with('status', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->employees()->exists()) {
            return back()->withErrors(['delete' => 'Move or remove the employees in this department first.']);
        }
        $department->delete();

        return back()->with('status', 'Department deleted.');
    }

    private function validated(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($department?->id)],
            'head_name' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
