@extends('layouts.app')
@section('title', $employee->full_name)

@section('content')
<div class="page-intro">
    <div><span class="eyebrow">{{ $employee->employee_code }}</span><h2>{{ $employee->full_name }}</h2><p>{{ $employee->position }} · {{ $employee->department?->name ?? 'No department' }}</p></div>
    <div class="page-actions"><a class="btn btn-secondary" href="{{ route('employees.index') }}">Back</a><a class="btn btn-primary" href="{{ route('employees.edit', $employee) }}">Edit</a></div>
</div>

<div class="profile-layout">
    <section class="card card-pad">
        <div class="person-cell" style="margin-bottom:10px"><span class="avatar avatar-lg">{{ $employee->initials }}</span>@include('partials.badge', ['status' => $employee->status])</div>
        <dl class="details-grid">
            <div><dt>Email</dt><dd>{{ $employee->email }}</dd></div>
            <div><dt>Phone</dt><dd>{{ $employee->phone ?: '—' }}</dd></div>
            <div><dt>Location</dt><dd>{{ $employee->location ?: '—' }}</dd></div>
            <div><dt>Hire date</dt><dd>{{ $employee->hire_date?->format('M j, Y') ?? '—' }}</dd></div>
            <div><dt>Base salary (monthly)</dt><dd>@money($employee->base_salary)</dd></div>
            <div><dt>Allowances (monthly)</dt><dd>@money($employee->allowances)</dd></div>
            <div><dt>Overtime rate / hour</dt><dd>@money($employee->overtime_rate)</dd></div>
            <div><dt>Bank</dt><dd>{{ $employee->bank_name ?: '—' }} {{ $employee->bank_account ? '· '.$employee->bank_account : '' }}</dd></div>
            <div><dt>Portal login</dt><dd>{{ $employee->user ? 'Enabled' : 'Not created' }}</dd></div>
        </dl>
    </section>

    <section class="card card-pad">
        <div class="card-head"><div><h3>Recent payslips</h3></div></div>
        @forelse ($payslips as $p)
            <a class="list-item" href="{{ route('payslips.show', $p) }}"><span class="grow"><strong>{{ $p->periodLabel() }}</strong><small class="mono">{{ $p->payslip_no }}</small></span><strong>@money($p->net)</strong></a>
        @empty
            <p class="muted">No payslips yet.</p>
        @endforelse
        @if ($loans->count())
            <h3 style="margin:18px 0 6px;font-size:14px">Loans</h3>
            @foreach ($loans as $l)<div class="list-item"><span class="grow"><strong>@money($l->approved_amount ?? $l->requested_amount)</strong><small>@money($l->outstanding) outstanding</small></span>@include('partials.badge', ['status' => $l->status])</div>@endforeach
        @endif
    </section>
</div>
@endsection
