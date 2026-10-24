@extends('layouts.app')
@section('title', auth()->user()->isAdmin() ? 'Attendance' : 'My attendance')

@section('content')
@php $admin = auth()->user()->isAdmin(); @endphp
<div class="page-intro">
    <div><span class="eyebrow">Time & attendance</span><h2>{{ $admin ? 'Attendance' : 'My attendance' }}</h2><p>Daily check-in, hours worked and punctuality. Hours beyond {{ config('payroll.standard_hours') }}h/day count as overtime in payroll.</p></div>
    <div class="page-actions">
        @if ($admin)
            <button class="btn btn-primary" onclick="document.getElementById('att-new').showModal()">@include('partials.icon', ['name' => 'plus', 'size' => 16]) Add / correct record</button>
        @elseif (! $today?->check_out)
            <form method="POST" action="{{ route('attendance.toggle') }}">@csrf<button class="btn btn-primary">@include('partials.icon', ['name' => 'clock', 'size' => 16]) {{ $today?->check_in ? 'Check out' : 'Check in' }}</button></form>
        @endif
    </div>
</div>

<div class="kpi-grid">
    <article class="kpi kpi-blue"><div><span class="l">Hours worked</span><strong>{{ number_format($totalHours, 1) }}</strong><small>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'clock'])</span></article>
    <article class="kpi kpi-violet"><div><span class="l">Overtime hours</span><strong>{{ number_format($overtimeHours, 1) }}</strong><small>Paid in the next payroll</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'wallet'])</span></article>
    <article class="kpi kpi-amber"><div><span class="l">Late arrivals</span><strong>{{ $lateCount }}</strong><small>After 9:15 AM</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'calendar'])</span></article>
</div>

<section class="card">
    <form method="GET" class="table-tools">
        <label class="field"><input type="month" name="month" value="{{ $month }}"></label>
        @if ($admin)<label class="field"><select name="employee_id"><option value="">All employees</option>@foreach ($employees as $e)<option value="{{ $e->id }}" @selected(request('employee_id') == $e->id)>{{ $e->full_name }}</option>@endforeach</select></label>@endif
        <button class="btn btn-secondary">Filter</button>
        <span class="right">{{ $records->count() }} records</span>
    </form>
    @if ($records->count())
    <div class="table-scroll"><table>
        <thead><tr>@if ($admin)<th>Employee</th>@endif<th>Date</th><th>Check in</th><th>Check out</th><th class="num">Hours</th><th class="num">Overtime</th><th>Status</th></tr></thead>
        <tbody>
        @foreach ($records as $r)
            <tr>
                @if ($admin)<td><div class="person-cell"><span class="avatar">{{ $r->employee->initials }}</span><strong>{{ $r->employee->full_name }}</strong></div></td>@endif
                <td>{{ $r->work_date->format('D, M j, Y') }}</td>
                <td>{{ $r->check_in ? \Carbon\Carbon::parse($r->check_in)->format('g:i A') : '—' }}</td>
                <td>{{ $r->check_out ? \Carbon\Carbon::parse($r->check_out)->format('g:i A') : '—' }}</td>
                <td class="num">{{ number_format($r->hours, 2) }}</td><td class="num">{{ number_format($r->overtime_hours, 2) }}</td>
                <td>@include('partials.badge', ['status' => $r->status])</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @else
        <div class="empty"><h3>No attendance records</h3><p>Nothing recorded for this period.</p></div>
    @endif
</section>

@if ($admin)
<dialog id="att-new">
    <form method="POST" action="{{ route('attendance.store') }}">
        @csrf
        <header><h2>Add / correct attendance</h2><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()">✕</button></header>
        <div class="modal-body">
            <label class="field">Employee *<select name="employee_id" required>@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }} · {{ $e->employee_code }}</option>@endforeach</select></label>
            <div class="form-row">
                <label class="field">Date *<input type="date" name="work_date" value="{{ today()->toDateString() }}" required></label>
                <label class="field">Status *<select name="status">@foreach (['present', 'late', 'absent', 'on_leave'] as $s)<option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>@endforeach</select></label>
            </div>
            <div class="form-row">
                <label class="field">Check in<input type="time" name="check_in"></label>
                <label class="field">Check out<input type="time" name="check_out"></label>
            </div>
            <span class="hint muted">Saving a record for an existing employee/date replaces it. Hours are calculated automatically.</span>
        </div>
        <footer><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button><button class="btn btn-primary">Save record</button></footer>
    </form>
</dialog>
@endif
@endsection
