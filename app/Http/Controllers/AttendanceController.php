<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $query = Attendance::with('employee')
            ->whereBetween('work_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->orderByDesc('work_date');

        if ($user->isAdmin()) {
            $query->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id));
        } else {
            abort_unless($user->employee, 403);
            $query->where('employee_id', $user->employee->id);
        }

        $records = $query->get();

        return view('attendance.index', [
            'records' => $records,
            'month' => $month,
            'employees' => $user->isAdmin() ? Employee::active()->orderBy('first_name')->get() : collect(),
            'today' => $user->employee?->attendances()->whereDate('work_date', today())->first(),
            'totalHours' => $records->sum('hours'),
            'overtimeHours' => $records->sum('overtime_hours'),
            'lateCount' => $records->where('status', 'late')->count(),
        ]);
    }

    /** Employee check in / check out for today. */
    public function toggle(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403);

        $now = now();
        $record = Attendance::firstOrNew(['employee_id' => $employee->id, 'work_date' => today()]);

        if (! $record->exists || ! $record->check_in) {
            $record->check_in = $now->format('H:i:s');
            $record->status = $now->format('H:i') > '09:15' ? 'late' : 'present';
            $record->save();

            return back()->with('status', 'Checked in at '.$now->format('g:i A').'.');
        }
        if ($record->check_out) {
            return back()->withErrors(['attendance' => 'You already checked out today.']);
        }

        $record->check_out = $now->format('H:i:s');
        $this->computeHours($record);
        $record->save();

        return back()->with('status', 'Checked out at '.$now->format('g:i A').'.');
    }

    /** Admin: add or correct an attendance record. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'work_date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'status' => ['required', 'in:present,late,absent,on_leave'],
        ]);

        $record = Attendance::firstOrNew(['employee_id' => $data['employee_id'], 'work_date' => $data['work_date']]);
        $record->check_in = $data['check_in'] ?? null;
        $record->check_out = $data['check_out'] ?? null;
        $record->status = $data['status'];
        $this->computeHours($record);
        $record->save();

        return back()->with('status', 'Attendance saved.');
    }

    private function computeHours(Attendance $record): void
    {
        if ($record->check_in && $record->check_out) {
            $hours = round(Carbon::parse($record->check_in)->diffInMinutes(Carbon::parse($record->check_out)) / 60, 2);
            $record->hours = $hours;
            $record->overtime_hours = max(round($hours - (float) config('payroll.standard_hours'), 2), 0);
        } else {
            $record->hours = 0;
            $record->overtime_hours = 0;
        }
    }
}
