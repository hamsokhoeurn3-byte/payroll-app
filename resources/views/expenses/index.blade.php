@extends('layouts.app')
@section('title', auth()->user()->isAdmin() ? 'Expenses' : 'My expenses')

@section('content')
@php $admin = auth()->user()->isAdmin(); @endphp
<div class="page-intro">
    <div><span class="eyebrow">Payroll & finance</span><h2>{{ $admin ? 'Expenses' : 'My expenses' }}</h2><p>Track claims, approvals and reimbursements.</p></div>
    @unless ($admin)<div class="page-actions"><button class="btn btn-primary" onclick="document.getElementById('exp-new').showModal()">@include('partials.icon', ['name' => 'plus', 'size' => 16]) New expense</button></div>@endunless
</div>

<section class="card">
    <div class="tabs">
        @foreach ([null => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'reimbursed' => 'Reimbursed', 'rejected' => 'Rejected'] as $s => $label)
            <a class="{{ request('status') == $s ? 'active' : '' }}" href="{{ route('expenses.index', $s ? ['status' => $s] : []) }}">{{ $label }}</a>
        @endforeach
    </div>
    @if ($expenses->count())
    <div class="table-scroll"><table>
        <thead><tr>@if ($admin)<th>Employee</th>@endif<th>Category</th><th>Date</th><th>Description</th><th class="num">Amount</th><th>Status</th>@if ($admin)<th></th>@endif</tr></thead>
        <tbody>
        @foreach ($expenses as $x)
            <tr>
                @if ($admin)<td><div class="person-cell"><span class="avatar">{{ $x->employee->initials }}</span><strong>{{ $x->employee->full_name }}</strong></div></td>@endif
                <td>{{ $x->category }}</td><td>{{ $x->expense_date->format('M j, Y') }}</td><td>{{ $x->description ?: '—' }}</td>
                <td class="num"><strong>@money($x->amount)</strong></td>
                <td>@include('partials.badge', ['status' => $x->status])</td>
                @if ($admin)<td><div class="actions">
                    @if ($x->status === 'pending')
                        <form method="POST" action="{{ route('expenses.decide', $x) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="btn btn-success btn-sm">Approve</button></form>
                        <form method="POST" action="{{ route('expenses.decide', $x) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="btn btn-secondary btn-sm">Reject</button></form>
                    @elseif ($x->status === 'approved')
                        <form method="POST" action="{{ route('expenses.decide', $x) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="reimbursed"><button class="btn btn-secondary btn-sm">Mark reimbursed</button></form>
                    @endif
                </div></td>@endif
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @else
        <div class="empty"><h3>No expenses</h3><p>Nothing to show for this filter.</p></div>
    @endif
    <div class="pagination"><span>{{ $expenses->total() }} claims</span>{{ $expenses->links() }}</div>
</section>

@unless ($admin)
<dialog id="exp-new">
    <form method="POST" action="{{ route('expenses.store') }}">
        @csrf
        <header><h2>New expense claim</h2><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()">✕</button></header>
        <div class="modal-body">
            <div class="form-row">
                <label class="field">Category *<select name="category">@foreach (['Travel', 'Software', 'Client meeting', 'Training', 'Equipment', 'Other'] as $c)<option>{{ $c }}</option>@endforeach</select></label>
                <label class="field">Date *<input type="date" name="expense_date" max="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" required></label>
            </div>
            <label class="field">Amount *<input type="number" step="0.01" min="0.01" name="amount" required></label>
            <label class="field">Description<input name="description" maxlength="255"></label>
        </div>
        <footer><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button><button class="btn btn-primary">Submit claim</button></footer>
    </form>
</dialog>
@endunless
@endsection
