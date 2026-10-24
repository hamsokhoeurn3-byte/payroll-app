@extends('layouts.app')
@section('title', 'Add employee')

@section('content')
<div class="page-intro"><div><span class="eyebrow">Workforce</span><h2>Add employee</h2><p>Employee ID <span class="mono">{{ $nextCode }}</span> will be assigned automatically.</p></div></div>
<form method="POST" action="{{ route('employees.store') }}" class="form-grid">
    @csrf
    @include('employees._form')
    <div class="card card-pad form-grid">
        <label class="check"><input type="checkbox" name="create_login" value="1" id="create_login" @checked(old('create_login'))> Create a login so this employee can view payslips</label>
        <label class="field" id="pw" style="display:none">Initial password *<input type="password" name="password" minlength="8" autocomplete="new-password"><span class="hint">Minimum 8 characters. The employee signs in with their work email.</span></label>
    </div>
    <div class="page-actions"><button class="btn btn-primary">Save employee</button><a class="btn btn-secondary" href="{{ route('employees.index') }}">Cancel</a></div>
</form>
@push('scripts')
<script>
    const cb = document.getElementById('create_login'), pw = document.getElementById('pw');
    const sync = () => pw.style.display = cb.checked ? 'grid' : 'none';
    cb.addEventListener('change', sync); sync();
</script>
@endpush
@endsection
