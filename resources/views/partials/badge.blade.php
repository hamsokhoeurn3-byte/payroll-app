@php
    $s = strtolower((string) $status);
    $tone = match (true) {
        in_array($s, ['paid', 'active', 'approved', 'present', 'completed', 'reimbursed', 'repaid']) => 'success',
        in_array($s, ['pending', 'on_leave', 'late', 'draft', 'processing']) => 'warning',
        in_array($s, ['inactive', 'rejected', 'absent']) => 'danger',
        default => 'info',
    };
@endphp
<span class="badge badge-{{ $tone }}">{{ ucwords(str_replace('_', ' ', $s)) }}</span>
