@extends('layouts.app')
@section('title', auth()->user()->isAdmin() ? 'Loans' : 'My loans')

@section('content')
@php $admin = auth()->user()->isAdmin(); @endphp
<div class="page-intro">
    <div><span class="eyebrow">Payroll & finance</span><h2>{{ $admin ? 'Loans' : 'My loans' }}</h2><p>Approved loans are repaid automatically through monthly payroll deductions.</p></div>
    @unless ($admin)<div class="page-actions"><button class="btn btn-primary" onclick="document.getElementById('loan-new').showModal()">@include('partials.icon', ['name' => 'plus', 'size' => 16]) Request loan</button></div>@endunless
</div>

<section class="card">
    @if ($loans->count())
    <div class="table-scroll"><table>
        <thead><tr>@if ($admin)<th>Employee</th>@endif<th class="num">Requested</th><th class="num">Approved</th><th class="num">Outstanding</th><th class="num">Monthly instalment</th><th>Reason</th><th>Status</th>@if ($admin)<th></th>@endif</tr></thead>
        <tbody>
        @foreach ($loans as $l)
            <tr>
                @if ($admin)<td><div class="person-cell"><span class="avatar">{{ $l->employee->initials }}</span><strong>{{ $l->employee->full_name }}</strong></div></td>@endif
                <td class="num">@money($l->requested_amount)</td>
                <td class="num">{{ $l->approved_amount !== null ? '' : '—' }}@if ($l->approved_amount !== null)@money($l->approved_amount)@endif</td>
                <td class="num"><strong>@money($l->outstanding)</strong></td>
                <td class="num">@money($l->monthly_installment)</td>
                <td>{{ $l->reason ?: '—' }}</td>
                <td>@include('partials.badge', ['status' => $l->status])</td>
                @if ($admin)<td>@if ($l->status === 'pending')<div class="actions">
                    <button class="btn btn-success btn-sm" onclick="document.getElementById('loan-{{ $l->id }}').showModal()">Approve…</button>
                    <form method="POST" action="{{ route('loans.decide', $l) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="btn btn-secondary btn-sm">Reject</button></form>
                </div>@endif</td>@endif
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @else
        <div class="empty"><h3>No loans</h3><p>{{ $admin ? 'No loan requests yet.' : 'You have not requested any loans.' }}</p></div>
    @endif
</section>

@if ($admin)
    @foreach ($loans->where('status', 'pending') as $l)
    <dialog id="loan-{{ $l->id }}">
        <form method="POST" action="{{ route('loans.decide', $l) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="approved">
            <header><h2>Approve loan · {{ $l->employee->full_name }}</h2><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()">✕</button></header>
            <div class="modal-body">
                <label class="field">Approved amount *<input type="number" step="0.01" min="1" name="approved_amount" value="{{ $l->requested_amount }}" required></label>
                <label class="field">Repay over (months) *<input type="number" min="1" max="60" name="installments" value="6" required><span class="hint">The monthly instalment is deducted from each payslip until repaid.</span></label>
            </div>
            <footer><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button><button class="btn btn-success">Approve loan</button></footer>
        </form>
    </dialog>
    @endforeach
@else
<dialog id="loan-new">
    <form method="POST" action="{{ route('loans.store') }}">
        @csrf
        <header><h2>Request a loan</h2><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()">✕</button></header>
        <div class="modal-body">
            <label class="field">Amount *<input type="number" step="0.01" min="1" name="requested_amount" required></label>
            <label class="field">Reason<input name="reason" maxlength="255"></label>
        </div>
        <footer><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button><button class="btn btn-primary">Submit request</button></footer>
    </form>
</dialog>
@endif
@endsection
