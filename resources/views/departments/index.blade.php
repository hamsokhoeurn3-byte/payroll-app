@extends('layouts.app')
@section('title', 'Departments')

@section('content')
<div class="page-intro">
    <div><span class="eyebrow">Workforce</span><h2>Departments</h2><p>Define department ownership and team capacity.</p></div>
    <div class="page-actions"><button class="btn btn-primary" onclick="document.getElementById('dept-new').showModal()">@include('partials.icon', ['name' => 'plus', 'size' => 16]) Add department</button></div>
</div>

<section class="card">
    @if ($departments->count())
    <div class="table-scroll"><table>
        <thead><tr><th>Department</th><th>Head</th><th>Location</th><th>Employees</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($departments as $d)
            <tr>
                <td><strong>{{ $d->name }}</strong></td><td>{{ $d->head_name ?: '—' }}</td><td>{{ $d->location ?: '—' }}</td><td>{{ $d->employees_count }}</td>
                <td>@include('partials.badge', ['status' => $d->status])</td>
                <td><div class="actions">
                    <button class="btn btn-secondary btn-sm" onclick="document.getElementById('dept-{{ $d->id }}').showModal()">Edit</button>
                    <form method="POST" action="{{ route('departments.destroy', $d) }}" onsubmit="return confirm('Delete this department?')">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm" style="color:var(--danger)">Delete</button></form>
                </div></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @else
        <div class="empty"><h3>No departments</h3><p>Add your first department.</p></div>
    @endif
</section>

@foreach ($departments->concat([new \App\Models\Department(['status' => 'active'])]) as $d)
<dialog id="{{ $d->exists ? 'dept-'.$d->id : 'dept-new' }}">
    <form method="POST" action="{{ $d->exists ? route('departments.update', $d) : route('departments.store') }}">
        @csrf @if ($d->exists) @method('PUT') @endif
        <header><h2>{{ $d->exists ? 'Edit department' : 'Add department' }}</h2><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()">✕</button></header>
        <div class="modal-body">
            <label class="field">Name *<input name="name" value="{{ $d->name }}" required></label>
            <div class="form-row">
                <label class="field">Head<input name="head_name" value="{{ $d->head_name }}"></label>
                <label class="field">Location<input name="location" value="{{ $d->location }}"></label>
            </div>
            <label class="field">Status<select name="status"><option value="active" @selected($d->status === 'active')>Active</option><option value="inactive" @selected($d->status === 'inactive')>Inactive</option></select></label>
        </div>
        <footer><button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button><button class="btn btn-primary">Save</button></footer>
    </form>
</dialog>
@endforeach
@endsection
