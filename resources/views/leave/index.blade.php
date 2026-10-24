@extends('layouts.app')
@section('title', auth()->user()->isAdmin() ? 'Leave requests' : 'My leave')

@section('content')
@php $admin = auth()->user()->isAdmin(); @endphp
<div class="page-intro">
    <div><span class="eyebrow">Time & attendance</span><h2>{{ $admin ? 'Leave management' : 'My leave' }}</h2><p>{{ $admin ? 'Review requests and approval history.' : 'Request time off and track your balance.' }}</p></div>
    @unless ($admin)<div class="page-actions"><button class="btn btn-primary" onclick="document.getElementById('leave-new').showModal()">@include('partials.icon', ['name' => 'plus', 'size' => 16]) Request leave</button></div>@endunless
</div>

@unless ($admin)
<div class="kpi-grid">
    <article class="kpi kpi-mint"><div><span class="l">Annual leave remaining</span><strong>{{ max($annualAllowance - $annualUsed, 0) }}</strong><small>of {{ $annualAllowance }} days this year</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'calendar'])</span></article>
    <article class="kpi kpi-blue"><div><span class="l">Annual leave used</span><strong>{{ $annualUsed }}</strong><small>approved days</small></div><span class="kpi-icon">@include('partials.icon', ['name' => 'check'])</span></article>
</div>
@endunless

<section class="card">
    <div class="tabs">
        @foreach ([null => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $s => $label)
            <a class="{{ request('status') == $s ? 'active' : '' }}" href="{{ route('leave.index', $s ? ['status' => $s] : []) }}">{{ $label }}</a>
        @endforeach
    </div>
    @if ($requests->count())
    <div class="table-scroll"><table>
        <thead><tr>@if ($admin)<th>Employee</th>@endif<th>Type</th><th>Dates</th><th class="num">Days</th><th>Reason</th><th>Status</th>@if ($admin)<th></th>@endif</tr></thead>
        <tbody>
        @foreach ($requests as $r)
            <tr>
                @if ($admin)<td><div class="person-cell"><span class="avatar">{{ $r->employee->initials }}</span><strong>{{ $r->employee->full_name }}</strong></div></td>@endif
                <td>{{ ucfirst($r->leave_type) }}</td>
                <td>{{ $r->start_date->format('M j') }}{{ $r->end_date->ne($r->start_date) ? ' – '.$r->end_date->format('M j, Y') : ', '.$r->start_date->format('Y') }}</td>
                <td class="num">{{ $r->days }}</td>
                <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis">{{ $r->reason ?: '—' }}</td>
                <td>@include('partials.badge', ['status' => $r->status])</td>
                @if ($admin)
                <td>@if ($r->status === 'pending')<div class="actions">
                    <form method="POST" action="{{ route('leave.decide', $r) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="btn btn-success btn-sm">Approve</button></form>
                    <form method="POST" action="{{ route('leave.decide', $r) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="btn btn-secondary btn-sm">Reject</button></form>
                </div>@endif</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @else
        <div class="empty"><h3>No leave requests</h3><p>Nothing to show for this filter.</p></div>
    @endif
    <div class="pagination"><span>{{ $requests->total() }} requests</span>{{ $requests->links() }}</div>
</section>

@unless ($admin)
<dialog id="leave-new">
    <form method="POST" action="{{ route('leave.store') }}">
        @csrf
        <header><h2>Request leave</h2><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()">✕</button></header>
        <div class="modal-body">
            <label class="field">Leave type *<select name="leave_type">@foreach (['annual' => 'Annual leave', 'sick' => 'Sick leave', 'personal' => 'Personal leave', 'unpaid' => 'Unpaid leave'] as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select></label>
            <div class="form-row">
                <label class="field">Start date *<input type="date" name="start_date" min="{{ today()->toDateString() }}" required></label>
                <label class="field">End date *<input type="date" name="end_date" min="{{ today()->toDateString() }}" required></label>
            </div>
            <label class="field">Reason<textarea name="reason" maxlength="500"></textarea></label>
            <span class="hint muted">Working days (Mon–Fri) are counted automatically.</span>
        </div>
        <footer><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button><button class="btn btn-primary">Submit request</button></footer>
    </form>
</dialog>
@endunless
@endsection
