@extends('layouts.app')
@section('title', 'Payslip')

@section('content')
<div class="page-intro no-print">
    <div><span class="eyebrow">Payslip</span><h2>{{ $payslip->periodLabel() }}</h2><p>{{ $payslip->payslip_no }}</p></div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ auth()->user()->isAdmin() ? route('payroll.show', $payslip->payroll_run_id) : route('payslips.index') }}">Back</a>
        <button class="btn btn-primary" onclick="window.print()">@include('partials.icon', ['name' => 'printer', 'size' => 16]) Print / save PDF</button>
    </div>
</div>

@php $e = $payslip->employee; @endphp
<article class="payslip">
    <div class="payslip-brand">
        <div class="logo"><span class="logo-mark"><i></i><i></i><i></i></span><strong>{{ config('payroll.company_name') }}</strong></div>
        @include('partials.badge', ['status' => $payslip->status])
    </div>
    <div class="payslip-title">
        <div><small>PAYSLIP</small><h3>{{ $payslip->periodLabel() }}</h3></div>
        <div class="meta"><span>Payslip no.</span><strong>{{ $payslip->payslip_no }}</strong><span>Payment date</span><strong>{{ $payslip->payment_date?->format('M j, Y') ?? '—' }}</strong></div>
    </div>
    <div class="payslip-person">
        <span class="avatar avatar-lg">{{ $e->initials }}</span>
        <p><strong>{{ $e->full_name }}</strong><span>{{ $e->position }} · {{ $e->department?->name }}</span><small>{{ $e->employee_code }} · {{ $e->email }}</small></p>
    </div>
    <div class="payslip-columns">
        <div>
            <h4>Earnings</h4>
            <p><span>Base salary</span><strong>@money($payslip->base_salary)</strong></p>
            <p><span>Allowances</span><strong>@money($payslip->allowances)</strong></p>
            <p><span>Bonus</span><strong>@money($payslip->bonus)</strong></p>
            <p><span>Overtime</span><strong>@money($payslip->overtime)</strong></p>
            <p class="total"><span>Gross earnings</span><strong>@money($payslip->gross)</strong></p>
        </div>
        <div>
            <h4>Deductions</h4>
            <p><span>Income tax</span><strong>@money($payslip->tax)</strong></p>
            <p><span>Social security</span><strong>@money($payslip->social_security)</strong></p>
            <p><span>Benefits</span><strong>@money($payslip->benefits)</strong></p>
            <p><span>Loan repayment</span><strong>@money($payslip->loan_deduction)</strong></p>
            <p><span>Other deductions</span><strong>@money($payslip->other_deductions)</strong></p>
            <p class="total"><span>Total deductions</span><strong>@money($payslip->total_deductions)</strong></p>
        </div>
    </div>
    <div class="net-pay"><div><span>Net pay</span><small>{{ $e->bank_name ? 'Paid to '.$e->bank_name.' '.$e->bank_account : 'Bank details not set' }}</small></div><strong>@money($payslip->net)</strong></div>
</article>
@endsection
