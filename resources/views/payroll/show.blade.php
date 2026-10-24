@extends('layouts.app')
@section('title', $run->periodLabel().' payroll')

@section('content')
<div class="page-intro">
    <div><span class="eyebrow">Pay run</span><h2>{{ $run->periodLabel() }}</h2><p>@include('partials.badge', ['status' => $run->status]) &nbsp; Payment date {{ $run->payment_date?->format('M j, Y') ?? '—' }}</p></div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('payroll.index') }}">Back</a>
        <a class="btn btn-secondary" href="{{ route('payroll.export', $run) }}">@include('partials.icon', ['name' => 'download', 'size' => 16]) Export CSV</a>
        @if ($run->status === 'draft')
            <form method="POST" action="{{ route('payroll.recalculate', $run) }}">@csrf<button class="btn btn-secondary">Recalculate</button></form>
            <form method="POST" action="{{ route('payroll.approve', $run) }}" onsubmit="return confirm('Approve this payroll? Figures will be locked and employees can see their payslips.')">@csrf<button class="btn btn-primary">Approve payroll</button></form>
        @elseif ($run->status === 'approved')
            <form method="POST" action="{{ route('payroll.pay', $run) }}" onsubmit="return confirm('Mark this run as paid? Loan balances will be reduced.')">@csrf<button class="btn btn-success">Mark as paid</button></form>
        @endif
    </div>
</div>

<div class="kpi-grid">
    <article class="kpi kpi-blue"><div><span class="l">Gross payroll</span><strong>@money($run->total_gross)</strong><small>{{ $run->employee_count }} employees</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'wallet'])</span></article>
    <article class="kpi kpi-amber"><div><span class="l">Deductions</span><strong>@money($run->total_deductions)</strong><small>{{ $run->total_gross > 0 ? round($run->total_deductions / $run->total_gross * 100, 1) : 0 }}% of gross</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'receipt'])</span></article>
    <article class="kpi kpi-mint"><div><span class="l">Net payroll</span><strong>@money($run->total_net)</strong><small>Amount to transfer</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'credit'])</span></article>
</div>

@if ($run->status === 'draft')
    <div class="alert alert-info">This run is a draft. Add bonuses or deductions under <a href="{{ route('adjustments.index', ['period' => $run->period]) }}">Bonuses &amp; deductions</a>, then press Recalculate. New active employees are added when you generate the same period again.</div>
@endif

<section class="card">
    <div class="table-scroll"><table>
        <thead><tr><th>Employee</th><th class="num">Base</th><th class="num">Allowances</th><th class="num">Bonus</th><th class="num">Overtime</th><th class="num">Gross</th><th class="num">Tax</th><th class="num">Social sec.</th><th class="num">Benefits</th><th class="num">Loan</th><th class="num">Other</th><th class="num">Net pay</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($payslips as $p)
            <tr>
                <td><div class="person-cell"><span class="avatar">{{ $p->employee->initials }}</span><p><strong>{{ $p->employee->full_name }}</strong><small>{{ $p->employee->department?->name }}</small></p></div></td>
                <td class="num">@money($p->base_salary)</td><td class="num">@money($p->allowances)</td><td class="num">@money($p->bonus)</td><td class="num">@money($p->overtime)</td>
                <td class="num"><strong>@money($p->gross)</strong></td>
                <td class="num negative">@money($p->tax)</td><td class="num negative">@money($p->social_security)</td><td class="num negative">@money($p->benefits)</td><td class="num negative">@money($p->loan_deduction)</td><td class="num negative">@money($p->other_deductions)</td>
                <td class="num"><strong>@money($p->net)</strong></td>
                <td>@include('partials.badge', ['status' => $p->status])</td>
                <td><a class="btn btn-ghost btn-sm" href="{{ route('payslips.show', $p) }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="14"><div class="empty"><h3>No payslips in this run</h3><p>There were no active employees when it was generated.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

@if ($run->status === 'draft')
<form method="POST" action="{{ route('payroll.destroy', $run) }}" style="margin-top:20px" onsubmit="return confirm('Delete this draft payroll?')">@csrf @method('DELETE')<button class="btn btn-ghost" style="color:var(--danger)">Delete draft</button></form>
@endif
@endsection
