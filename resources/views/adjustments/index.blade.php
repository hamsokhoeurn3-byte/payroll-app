@extends('layouts.app')
@section('title', 'Bonuses & deductions')

@section('content')
<div class="page-intro">
    <div><span class="eyebrow">Payroll & finance</span><h2>Bonuses, deductions &amp; overtime</h2><p>One-off items picked up by the payroll run for the selected month.</p></div>
    <div class="page-actions"><button class="btn btn-primary" onclick="document.getElementById('adj-new').showModal()">@include('partials.icon', ['name' => 'plus', 'size' => 16]) Add adjustment</button></div>
</div>

<section class="card">
    <div class="tabs">
        @foreach ([null => 'All', 'bonus' => 'Bonuses', 'deduction' => 'Deductions', 'overtime' => 'Overtime'] as $t => $label)
            <a class="{{ $type == $t ? 'active' : '' }}" href="{{ route('adjustments.index', array_filter(['period' => $period, 'type' => $t])) }}">{{ $label }}</a>
        @endforeach
    </div>
    <form method="GET" class="table-tools">
        <input type="hidden" name="type" value="{{ $type }}">
        <label class="field"><input type="month" name="period" value="{{ $period }}"></label>
        <button class="btn btn-secondary">Show month</button>
        <span class="right">{{ $adjustments->count() }} items · @money($adjustments->sum('amount')) total</span>
    </form>
    @if ($adjustments->count())
    <div class="table-scroll"><table>
        <thead><tr><th>Employee</th><th>Type</th><th>Description</th><th class="num">Amount</th><th></th></tr></thead>
        <tbody>
        @foreach ($adjustments as $a)
            <tr>
                <td><div class="person-cell"><span class="avatar">{{ $a->employee->initials }}</span><strong>{{ $a->employee->full_name }}</strong></div></td>
                <td><span class="badge badge-{{ $a->type === 'deduction' ? 'danger' : 'success' }}">{{ ucfirst($a->type) }}</span></td>
                <td>{{ $a->description }}</td>
                <td class="num {{ $a->type === 'deduction' ? 'negative' : '' }}">{{ $a->type === 'deduction' ? '−' : '' }}@money($a->amount)</td>
                <td><div class="actions"><form method="POST" action="{{ route('adjustments.destroy', $a) }}" onsubmit="return confirm('Remove this adjustment?')">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm" style="color:var(--danger)">Remove</button></form></div></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @else
        <div class="empty"><h3>No adjustments for this month</h3><p>Add a bonus, deduction or overtime entry.</p></div>
    @endif
</section>

<dialog id="adj-new">
    <form method="POST" action="{{ route('adjustments.store') }}">
        @csrf
        <header><h2>Add adjustment</h2><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()">✕</button></header>
        <div class="modal-body">
            <label class="field">Employee *<select name="employee_id" required>@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }} · {{ $e->employee_code }}</option>@endforeach</select></label>
            <div class="form-row">
                <label class="field">Type *<select name="type"><option value="bonus">Bonus</option><option value="deduction">Deduction</option><option value="overtime">Overtime (manual)</option></select></label>
                <label class="field">Pay month *<input type="month" name="period" value="{{ $period }}" required></label>
            </div>
            <label class="field">Description *<input name="description" maxlength="255" required></label>
            <label class="field">Amount *<input type="number" step="0.01" min="0.01" name="amount" required></label>
            <span class="hint muted">Hours recorded in Attendance are paid as overtime automatically; use manual overtime only for extra payments.</span>
        </div>
        <footer><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button><button class="btn btn-primary">Save</button></footer>
    </form>
</dialog>
@endsection
