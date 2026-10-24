@extends('layouts.app')
@section('title', 'Employees')

@section('content')
<div class="page-intro">
    <div><span class="eyebrow">Workforce</span><h2>Employees</h2><p>Manage employee profiles, employment details and pay settings.</p></div>
    <div class="page-actions"><a class="btn btn-primary" href="{{ route('employees.create') }}">@include('partials.icon', ['name' => 'plus', 'size' => 16]) Add employee</a></div>
</div>

<section class="card">
    <form method="GET" class="table-tools">
        <label class="field"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, ID or email…"></label>
        <label class="field"><select name="department"><option value="">All departments</option>@foreach ($departments as $d)<option value="{{ $d->id }}" @selected(request('department') == $d->id)>{{ $d->name }}</option>@endforeach</select></label>
        <label class="field"><select name="status"><option value="">All statuses</option>@foreach (['active' => 'Active', 'on_leave' => 'On leave', 'inactive' => 'Inactive'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></label>
        <button class="btn btn-secondary">Filter</button>
        @if (request()->hasAny(['q', 'department', 'status']))<a class="btn btn-ghost" href="{{ route('employees.index') }}">Clear</a>@endif
        <span class="right">{{ $employees->total() }} employees</span>
    </form>

    @if ($employees->count())
    <div class="table-scroll"><table>
        <thead><tr><th>Employee</th><th>ID</th><th>Department</th><th>Position</th><th class="num">Base salary</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($employees as $e)
            <tr>
                <td><div class="person-cell"><span class="avatar">{{ $e->initials }}</span><p><strong>{{ $e->full_name }}</strong><small>{{ $e->email }}</small></p></div></td>
                <td><span class="mono">{{ $e->employee_code }}</span></td>
                <td>{{ $e->department?->name ?? '—' }}</td>
                <td>{{ $e->position }}</td>
                <td class="num">@money($e->base_salary)</td>
                <td>@include('partials.badge', ['status' => $e->status])</td>
                <td><div class="actions"><a class="btn btn-secondary btn-sm" href="{{ route('employees.show', $e) }}">View</a><a class="btn btn-ghost btn-sm" href="{{ route('employees.edit', $e) }}">Edit</a></div></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @else
        <div class="empty"><h3>No employees found</h3><p>Try changing your search or add a new employee.</p></div>
    @endif
    <div class="pagination"><span>Showing {{ $employees->firstItem() ?? 0 }}–{{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }}</span>{{ $employees->links() }}</div>
</section>
@endsection
