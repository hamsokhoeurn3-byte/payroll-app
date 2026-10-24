@extends('layouts.app')
@section('title', 'Salary')

@section('content')
<div class="page-intro"><div><span class="eyebrow">Payroll & finance</span><h2>Salary</h2><p>Review and adjust compensation. Every change is recorded in the history below.</p></div></div>

<section class="card" style="margin-bottom:16px">
    <form method="GET" class="table-tools"><label class="field"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search employee…"></label><button class="btn btn-secondary">Search</button></form>
    <div class="table-scroll"><table>
        <thead><tr><th>Employee</th><th class="num">Base salary</th><th class="num">Allowances</th><th class="num">Overtime / hr</th><th class="num">Monthly total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($employees as $e)
            <tr>
                <td><div class="person-cell"><span class="avatar">{{ $e->initials }}</span><p><strong>{{ $e->full_name }}</strong><small>{{ $e->employee_code }} · {{ $e->department?->name }}</small></p></div></td>
                <td class="num">@money($e->base_salary)</td><td class="num">@money($e->allowances)</td><td class="num">@money($e->overtime_rate)</td>
                <td class="num"><strong>@money($e->base_salary + $e->allowances)</strong></td>
                <td>@include('partials.badge', ['status' => $e->status])</td>
                <td><div class="actions"><button class="btn btn-secondary btn-sm" onclick="document.getElementById('sal-{{ $e->id }}').showModal()">Adjust</button></div></td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="empty"><h3>No employees found</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

<section class="card card-pad">
    <div class="card-head"><div><h3>Recent adjustments</h3><p>Latest 10 changes</p></div></div>
    @forelse ($history as $h)
        <div class="list-item"><span class="grow"><strong>{{ $h->employee->full_name }}</strong><small>{{ $h->reason ?: 'No reason given' }} · effective {{ $h->effective_date->format('M j, Y') }}</small></span><span>@money($h->old_base_salary) → <strong>@money($h->new_base_salary)</strong></span></div>
    @empty
        <p class="muted">No adjustments recorded yet.</p>
    @endforelse
</section>

@foreach ($employees as $e)
<dialog id="sal-{{ $e->id }}">
    <form method="POST" action="{{ route('salary.update', $e) }}">
        @csrf @method('PUT')
        <header><h2>Adjust · {{ $e->full_name }}</h2><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()">✕</button></header>
        <div class="modal-body">
            <div class="form-row">
                <label class="field">Base salary (monthly) *<input type="number" step="0.01" min="0" name="base_salary" value="{{ $e->base_salary }}" required></label>
                <label class="field">Allowances (monthly) *<input type="number" step="0.01" min="0" name="allowances" value="{{ $e->allowances }}" required></label>
            </div>
            <div class="form-row">
                <label class="field">Overtime rate / hour *<input type="number" step="0.01" min="0" name="overtime_rate" value="{{ $e->overtime_rate }}" required></label>
                <label class="field">Effective date *<input type="date" name="effective_date" value="{{ today()->toDateString() }}" required></label>
            </div>
            <label class="field">Reason<input name="reason" maxlength="255" placeholder="Annual review, promotion…"></label>
        </div>
        <footer><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button><button class="btn btn-primary">Save change</button></footer>
    </form>
</dialog>
@endforeach
@endsection
