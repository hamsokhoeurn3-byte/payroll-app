@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="page-intro">
    <div>
        <span class="eyebrow">{{ now()->format('l, F j, Y') }}</span>
        <h2>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ $employee->first_name }}</h2>
        <p>Here's your workday at a glance.</p>
    </div>
    <div class="page-actions">
        @if (! $todayAttendance?->check_out)
            <form method="POST" action="{{ route('attendance.toggle') }}">@csrf
                <button class="btn btn-primary" type="submit">@include('partials.icon', ['name' => 'clock', 'size' => 16]) {{ $todayAttendance?->check_in ? 'Check out' : 'Check in' }}</button>
            </form>
        @endif
    </div>
</div>

<div class="status-line">
    <span class="avatar avatar-xl">{{ $employee->initials }}</span>
    <div><strong style="font-size:18px">{{ $employee->full_name }}</strong><small class="muted">{{ $employee->position }} · {{ $employee->department?->name }} · {{ $employee->employee_code }}</small></div>
    <div style="text-align:right">
        <small class="muted">Today's attendance</small>
        <strong>
            @if ($todayAttendance?->check_out) Checked out · {{ \Carbon\Carbon::parse($todayAttendance->check_out)->format('g:i A') }}
            @elseif ($todayAttendance?->check_in) Checked in · {{ \Carbon\Carbon::parse($todayAttendance->check_in)->format('g:i A') }}
            @else Not checked in @endif
        </strong>
    </div>
</div>

<div class="employee-tiles">
    <a class="tile" href="{{ route('leave.index') }}"><span class="icon-box amber">@include('partials.icon', ['name' => 'calendar'])</span><p><small>Annual leave balance</small><strong>{{ $leaveBalance }} days</strong><small>{{ $pendingLeave }} request(s) pending</small></p></a>
    <a class="tile" href="{{ route('payslips.index') }}"><span class="icon-box green">@include('partials.icon', ['name' => 'wallet'])</span><p><small>Latest net pay</small><strong>{{ $latestPayslip && $latestPayslip->status !== 'pending' ? config('payroll.currency_symbol').number_format($latestPayslip->net, 2) : '—' }}</strong><small>{{ $latestPayslip && $latestPayslip->status !== 'pending' ? $latestPayslip->periodLabel() : 'Nothing approved yet' }}</small></p></a>
    <a class="tile" href="{{ route('loans.index') }}"><span class="icon-box violet">@include('partials.icon', ['name' => 'landmark'])</span><p><small>Loans</small><strong>{{ $employee->loans()->where('status', 'approved')->sum('outstanding') > 0 ? 'Active' : 'None' }}</strong><small>@money($employee->loans()->where('status', 'approved')->sum('outstanding')) outstanding</small></p></a>
</div>

<section class="card card-pad">
    <div class="card-head"><div><h3>Recent payslips</h3><p>Your latest approved pay records</p></div><a href="{{ route('payslips.index') }}">View all →</a></div>
    @php $visible = $recentPayslips->whereIn('status', ['processing', 'paid']); @endphp
    @forelse ($visible as $p)
        <a class="list-item" href="{{ route('payslips.show', $p) }}"><span class="icon-box blue">@include('partials.icon', ['name' => 'wallet'])</span><span class="grow"><strong>{{ $p->periodLabel() }}</strong><small>{{ $p->payslip_no }}</small></span><strong>@money($p->net)</strong>@include('partials.badge', ['status' => $p->status])</a>
    @empty
        <div class="empty">No payslips available yet.</div>
    @endforelse
</section>
@endsection
