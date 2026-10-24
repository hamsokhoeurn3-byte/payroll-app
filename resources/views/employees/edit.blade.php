@extends('layouts.app')
@section('title', 'Edit employee')

@section('content')
<div class="page-intro"><div><span class="eyebrow">{{ $employee->employee_code }}</span><h2>Edit {{ $employee->full_name }}</h2><p>Changes to salary apply to the next payroll calculation.</p></div></div>
<form method="POST" action="{{ route('employees.update', $employee) }}" class="form-grid">
    @csrf @method('PUT')
    @include('employees._form')
    <div class="page-actions"><button class="btn btn-primary">Save changes</button><a class="btn btn-secondary" href="{{ route('employees.show', $employee) }}">Cancel</a></div>
</form>
<form method="POST" action="{{ route('employees.destroy', $employee) }}" style="margin-top:24px" onsubmit="return confirm('Delete this employee permanently?')">
    @csrf @method('DELETE')
    <button class="btn btn-ghost" style="color:var(--danger)">Delete employee</button>
</form>
@endsection
