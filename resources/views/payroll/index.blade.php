@extends('layouts.app')
@section('title', 'Payroll')

@section('content')
<div class="page-intro">
    <div><span class="eyebrow">Payroll & finance</span><h2>Payroll workspace</h2><p>Generate pay runs, review calculations, approve and mark them paid.</p></div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="document.getElementById('generate').showModal()">@include('partials.icon', ['name' => 'plus', 'size' => 16]) Generate payroll</button>
    </div>
</div>

@if ($latest)
<div class="banner">
    <div>
        <span class="icon-box blue">@include('partials.icon', ['name' => 'shield'])</span>
        <p><strong>{{ $latest->periodLabel() }} payroll is {{ $latest->status }}</strong><small>{{ $latest->employee_count }} employees · Net pay @money($latest->total_net)</small></p>
    </div>
    <a class="btn btn-secondary" href="{{ route('payroll.show', $latest) }}">Open pay run</a>
</div>
@endif

<div class="card" style="margin-bottom:16px">
    <div class="card-pad" style="padding-bottom:0"><div class="card-head"><div><h3>Pay runs</h3><p>One run per month. Draft runs can be recalculated; approved and paid runs are locked.</p></div></div></div>
    @if ($runs->count())
    <div class="table-scroll"><table>
        <thead><tr><th>Period</th><th>Employees</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th><th>Payment date</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($runs as $r)
            <tr>
                <td><strong>{{ $r->periodLabel() }}</strong></td>
                <td>{{ $r->employee_count }}</td>
                <td class="num">@money($r->total_gross)</td>
                <td class="num negative">−@money($r->total_deductions)</td>
                <td class="num"><strong>@money($r->total_net)</strong></td>
                <td>{{ $r->payment_date?->format('M j, Y') ?? '—' }}</td>
                <td>@include('partials.badge', ['status' => $r->status])</td>
                <td><div class="actions"><a class="btn btn-secondary btn-sm" href="{{ route('payroll.show', $r) }}">Open</a><a class="btn btn-ghost btn-sm" href="{{ route('payroll.export', $r) }}">CSV</a></div></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @else
        <div class="empty"><h3>No payroll runs yet</h3><p>Click "Generate payroll" to create the first draft.</p></div>
    @endif
</div>

<section class="card">
    <form method="GET" class="table-tools">
        <strong style="margin-right:6px">All payslips</strong>
        <label class="field"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search employee or payslip no…"></label>
        <label class="field"><select name="period"><option value="">All periods</option>@foreach ($runs as $r)<option value="{{ $r->period }}" @selected(request('period') === $r->period)>{{ $r->periodLabel() }}</option>@endforeach</select></label>
        <label class="field"><select name="status"><option value="">All statuses</option>@foreach (['paid', 'processing', 'pending'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select></label>
        <button class="btn btn-secondary">Filter</button>
        @if (request()->hasAny(['q', 'period', 'status']))<a class="btn btn-ghost" href="{{ route('payroll.index') }}">Clear</a>@endif
    </form>
    @if ($payslips->count())
    <div class="table-scroll"><table>
        <thead><tr><th>Employee</th><th>Payslip</th><th>Period</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net pay</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($payslips as $p)
            <tr>
                <td><div class="person-cell"><span class="avatar">{{ $p->employee->initials }}</span><p><strong>{{ $p->employee->full_name }}</strong><small>{{ $p->employee->position }}</small></p></div></td>
                <td><span class="mono">{{ $p->payslip_no }}</span></td>
                <td>{{ $p->periodLabel() }}</td>
                <td class="num">@money($p->gross)</td>
                <td class="num negative">−@money($p->total_deductions)</td>
                <td class="num"><strong>@money($p->net)</strong></td>
                <td>@include('partials.badge', ['status' => $p->status])</td>
                <td><div class="actions"><a class="btn btn-ghost btn-sm" href="{{ route('payslips.show', $p) }}">View</a></div></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @else
        <div class="empty"><h3>No payslips found</h3><p>Generate a payroll run or change the filters.</p></div>
    @endif
    <div class="pagination"><span>{{ $payslips->total() }} payslips</span>{{ $payslips->links() }}</div>
</section>

<dialog id="generate">
    <form method="POST" action="{{ route('payroll.generate') }}">
        @csrf
        <header><h2>Generate payroll</h2><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()">✕</button></header>
        <div class="modal-body">
            <div class="alert alert-info" style="margin:0">Creates a draft with a payslip for every active employee, using their current salary, approved bonuses/deductions, overtime hours and loan instalments. Amounts follow the sample rules in <code>config/payroll.php</code>.</div>
            <label class="field">Pay period (month) *<input type="month" name="period" value="{{ $suggestedPeriod }}" required></label>
        </div>
        <footer><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button><button class="btn btn-primary">Generate draft</button></footer>
    </form>
</dialog>
@endsection
