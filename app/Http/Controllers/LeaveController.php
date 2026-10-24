<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = LeaveRequest::with('employee')->latest();

        if (! $user->isAdmin()) {
            abort_unless($user->employee, 403);
            $query->where('employee_id', $user->employee->id);
        }
        $query->when($request->status, fn ($q, $s) => $q->where('status', $s));

        $usedAnnual = $user->employee
            ? (int) $user->employee->leaveRequests()->where('status', 'approved')->where('leave_type', 'annual')
                ->whereYear('start_date', now()->year)->sum('days')
            : 0;

        return view('leave.index', [
            'requests' => $query->paginate(15)->withQueryString(),
            'annualAllowance' => DashboardController::ANNUAL_LEAVE_DAYS,
            'annualUsed' => $usedAnnual,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'Only employees can submit leave requests.');

        $data = $request->validate([
            'leave_type' => ['required', 'in:annual,sick,personal,unpaid'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Working days (Mon-Fri) between the dates, inclusive.
        $days = 0;
        for ($d = Carbon::parse($data['start_date']); $d->lte(Carbon::parse($data['end_date'])); $d->addDay()) {
            if (! $d->isWeekend()) {
                $days++;
            }
        }
        if ($days === 0) {
            return back()->withInput()->withErrors(['start_date' => 'The selected dates contain no working days.']);
        }

        LeaveRequest::create($data + ['employee_id' => $employee->id, 'days' => $days, 'status' => 'pending']);

        return back()->with('status', 'Leave request submitted for approval.');
    }

    public function decide(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:approved,rejected']]);
        abort_unless($leave->status === 'pending', 422, 'This request was already decided.');

        $leave->update(['status' => $data['status'], 'decided_by' => $request->user()->id, 'decided_at' => now()]);

        return back()->with('status', 'Leave request '.$data['status'].'.');
    }
}
