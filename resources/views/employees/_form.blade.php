@php $v = fn ($k, $d = '') => old($k, $employee->{$k} ?? $d); @endphp
<div class="card card-pad form-grid">
    <h3>Personal & employment</h3>
    <div class="form-row">
        <label class="field">First name *<input name="first_name" value="{{ $v('first_name') }}" required></label>
        <label class="field">Last name *<input name="last_name" value="{{ $v('last_name') }}" required></label>
    </div>
    <div class="form-row">
        <label class="field">Work email *<input type="email" name="email" value="{{ $v('email') }}" required></label>
        <label class="field">Phone<input name="phone" value="{{ $v('phone') }}"></label>
    </div>
    <div class="form-row">
        <label class="field">Department<select name="department_id"><option value="">— None —</option>@foreach ($departments as $d)<option value="{{ $d->id }}" @selected($v('department_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></label>
        <label class="field">Position *<input name="position" value="{{ $v('position') }}" required></label>
        <label class="field">Location<input name="location" value="{{ $v('location') }}"></label>
    </div>
    <div class="form-row">
        <label class="field">Status *<select name="status">@foreach (['active' => 'Active', 'on_leave' => 'On leave', 'inactive' => 'Inactive'] as $k => $t)<option value="{{ $k }}" @selected($v('status', 'active') === $k)>{{ $t }}</option>@endforeach</select><span class="hint">Only active employees are included in payroll runs.</span></label>
        <label class="field">Hire date<input type="date" name="hire_date" value="{{ old('hire_date', optional($employee->hire_date)->format('Y-m-d')) }}"></label>
    </div>

    <h3 style="margin-top:8px">Compensation (monthly)</h3>
    <div class="form-row">
        <label class="field">Base salary *<input type="number" step="0.01" min="0" name="base_salary" value="{{ $v('base_salary', 0) }}" required></label>
        <label class="field">Allowances<input type="number" step="0.01" min="0" name="allowances" value="{{ $v('allowances', 0) }}"></label>
        <label class="field">Overtime rate / hour<input type="number" step="0.01" min="0" name="overtime_rate" value="{{ $v('overtime_rate', 0) }}"></label>
    </div>
    <div class="form-row">
        <label class="field">Bank name<input name="bank_name" value="{{ $v('bank_name') }}"></label>
        <label class="field">Bank account<input name="bank_account" value="{{ $v('bank_account') }}"></label>
    </div>
</div>
