@extends('layouts.app')
@section('title', 'My payslips')

@section('content')
<div class="page-intro"><div><span class="eyebrow">Employee self-service</span><h2>My payslips</h2><p>Review and print your personal payroll records.</p></div></div>

<section class="card">
    @if ($payslips->count())
    <div class="table-scroll"><table>
        <thead><tr><th>Period</th><th>Payslip</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net pay</th><th>Payment date</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($payslips as $p)
            <tr>
                <td><strong>{{ $p->periodLabel() }}</strong></td>
                <td><span class="mono">{{ $p->payslip_no }}</span></td>
                <td class="num">@money($p->gross)</td>
                <td class="num negative">−@money($p->total_deductions)</td>
                <td class="num"><strong>@money($p->net)</strong></td>
                <td>{{ $p->payment_date?->format('M j, Y') ?? '—' }}</td>
                <td>@include('partials.badge', ['status' => $p->status])</td>
                <td><a class="btn btn-secondary btn-sm" href="{{ route('payslips.show', $p) }}">View</a></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @else
        <div class="empty"><h3>No payslips yet</h3><p>Payslips appear here once payroll has been approved.</p></div>
    @endif
    <div class="pagination"><span>{{ $payslips->total() }} payslips</span>{{ $payslips->links() }}</div>
</section>
@endsection
