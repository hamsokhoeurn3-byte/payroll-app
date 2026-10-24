@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="page-intro">
    <div>
        <span class="eyebrow">{{ now()->format('l, F j, Y') }}</span>
        <h2>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}</h2>
        <p>Here's what's happening across payroll today.</p>
    </div>
    <div class="page-actions">
        @if ($latestRun)<a class="btn btn-secondary" href="{{ route('payroll.export', $latestRun) }}">@include('partials.icon', ['name' => 'download', 'size' => 16]) Export latest run</a>@endif
        <a class="btn btn-primary" href="{{ route('payroll.index') }}">@include('partials.icon', ['name' => 'plus', 'size' => 16]) Run payroll</a>
    </div>
</div>

<div class="kpi-grid">
    <article class="kpi kpi-blue"><div><span class="l">Total employees</span><strong>{{ $totalEmployees }}</strong><small>{{ $activeEmployees }} active</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'users'])</span></article>
    <article class="kpi kpi-mint"><div><span class="l">Attendance today</span><strong>{{ $attendanceRate }}%</strong><small>of active employees checked in</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'clock'])</span></article>
    <article class="kpi kpi-amber"><div><span class="l">Pending leave</span><strong>{{ $pendingLeave }}</strong><small>Needs your attention</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'calendar'])</span></article>
    <article class="kpi kpi-violet"><div><span class="l">Latest net payroll</span><strong>{{ $latestRun ? '$'.number_format($latestRun->total_net / 1000, 1).'K' : '—' }}</strong><small>{{ $latestRun ? $latestRun->periodLabel().' · '.ucfirst($latestRun->status) : 'No payroll run yet' }}</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'wallet'])</span></article>
    <article class="kpi kpi-cyan"><div><span class="l">Pending expenses</span><strong>@money($pendingExpenses)</strong><small>{{ $pendingExpenseCount }} claim(s)</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'receipt'])</span></article>
</div>

<div class="dashboard-grid">
    <section class="card card-pad span-2">
        <div class="card-head"><div><h3>Payroll overview</h3><p>Gross pay and deductions by pay period</p></div></div>
        @php $max = max($chartRuns->max('total_gross') ?: 1, 1); @endphp
        @if ($chartRuns->isEmpty())
            <div class="empty"><h3>No payroll data yet</h3><p>Generate your first payroll run to see the trend.</p></div>
        @else
            <div class="chart">
                @foreach ($chartRuns as $r)
                    <div class="col" title="{{ $r->periodLabel() }}">
                        <span class="gross" style="height: {{ round($r->total_gross / $max * 100) }}%"></span>
                        <span class="deduct" style="height: {{ round($r->total_deductions / $max * 100) }}%"></span>
                    </div>
                @endforeach
            </div>
            <div class="chart-labels">@foreach ($chartRuns as $r)<span>{{ \Carbon\Carbon::createFromFormat('Y-m', $r->period)->format('M y') }}</span>@endforeach</div>
            <div class="legend-row"><span><i style="background:var(--primary)"></i>Gross pay</span><span><i style="background:#a5b4fc"></i>Deductions</span></div>
        @endif
    </section>

    <section class="card card-pad">
        <div class="card-head"><div><h3>Department mix</h3><p>Active employees</p></div></div>
        @php $dMax = max($departments->max('employees_count') ?: 1, 1); @endphp
        @foreach ($departments as $d)
            <div class="dept-row"><span style="width:90px">{{ $d->name }}</span><div class="bar"><span style="width: {{ round($d->employees_count / $dMax * 100) }}%"></span></div><strong>{{ $d->employees_count }}</strong></div>
        @endforeach
    </section>

    <section class="card card-pad">
        <div class="card-head"><div><h3>Pending approvals</h3><p>Items that need review</p></div></div>
        <a class="list-item" href="{{ route('leave.index', ['status' => 'pending']) }}"><span class="icon-box amber">@include('partials.icon', ['name' => 'calendar'])</span><span class="grow"><strong>Leave requests</strong><small>{{ $pendingLeave }} waiting</small></span></a>
        <a class="list-item" href="{{ route('expenses.index', ['status' => 'pending']) }}"><span class="icon-box blue">@include('partials.icon', ['name' => 'receipt'])</span><span class="grow"><strong>Expense claims</strong><small>@money($pendingExpenses) total</small></span></a>
        <a class="list-item" href="{{ route('loans.index') }}"><span class="icon-box violet">@include('partials.icon', ['name' => 'landmark'])</span><span class="grow"><strong>Loan requests</strong><small>{{ \App\Models\Loan::where('status', 'pending')->count() }} waiting</small></span></a>
    </section>

    <section class="card card-pad span-2">
        <div class="card-head"><div><h3>Recent employees</h3><p>Newest additions to your team</p></div><a href="{{ route('employees.index') }}">All employees →</a></div>
        @forelse ($recentEmployees as $e)
            <a class="list-item" href="{{ route('employees.show', $e) }}"><span class="avatar">{{ $e->initials }}</span><span class="grow"><strong>{{ $e->full_name }}</strong><small>{{ $e->position }} · {{ $e->department?->name ?? 'No department' }}</small></span>@include('partials.badge', ['status' => $e->status])</a>
        @empty
            <div class="empty">No employees yet.</div>
        @endforelse
    </section>
</div>
@endsection
