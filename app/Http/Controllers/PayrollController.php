<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payroll)
    {
    }

    public function index(Request $request)
    {
        $runs = PayrollRun::orderByDesc('period')->get();

        $payslips = Payslip::with('employee.department')
            ->when($request->q, fn ($q, $t) => $q->where(function ($w) use ($t) {
                $w->where('payslip_no', 'like', "%$t%")->orWhereHas('employee', fn ($e) => $e
                    ->where('first_name', 'like', "%$t%")->orWhere('last_name', 'like', "%$t%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->period, fn ($q, $p) => $q->where('period', $p))
            ->orderByDesc('period')->orderBy('employee_id')->paginate(15)->withQueryString();

        return view('payroll.index', [
            'runs' => $runs,
            'payslips' => $payslips,
            'latest' => $runs->first(),
            'suggestedPeriod' => now()->format('Y-m'),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate(['period' => ['required', 'date_format:Y-m']]);
        [$run, $created] = $this->payroll->generate($data['period'], $request->user()->id);

        if ($run->status !== 'draft') {
            return redirect()->route('payroll.show', $run)->withErrors(['period' => 'That period is already '.$run->status.' and locked.']);
        }

        return redirect()->route('payroll.show', $run)->with('status', $created
            ? "Draft payroll generated with $created new payslip(s). Review it, then approve."
            : 'Draft already includes every active employee.');
    }

    public function show(PayrollRun $run)
    {
        return view('payroll.show', [
            'run' => $run,
            'payslips' => $run->payslips()->with('employee.department')->get()->sortBy('employee.first_name'),
        ]);
    }

    public function recalculate(PayrollRun $run): RedirectResponse
    {
        $this->payroll->recalculate($run);

        return back()->with('status', 'Payslips recalculated from current salaries and adjustments.');
    }

    public function approve(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->payroll->approve($run, $request->user()->id);

        return back()->with('status', 'Payroll approved. Mark it as paid once the transfers are made.');
    }

    public function pay(PayrollRun $run): RedirectResponse
    {
        $this->payroll->markPaid($run);

        return back()->with('status', 'Payroll marked as paid. Loan balances were updated.');
    }

    public function destroy(PayrollRun $run): RedirectResponse
    {
        abort_unless($run->status === 'draft', 422, 'Only draft runs can be deleted.');
        $run->delete();

        return redirect()->route('payroll.index')->with('status', 'Draft payroll deleted.');
    }

    public function export(PayrollRun $run): StreamedResponse
    {
        $rows = $run->payslips()->with('employee.department')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Payslip', 'Employee code', 'Employee', 'Department', 'Period', 'Base salary', 'Allowances',
                'Bonus', 'Overtime', 'Gross', 'Tax', 'Social security', 'Benefits', 'Loan', 'Other deductions',
                'Total deductions', 'Net', 'Status', 'Bank', 'Account']);
            foreach ($rows as $p) {
                fputcsv($out, [$p->payslip_no, $p->employee->employee_code, $p->employee->full_name,
                    $p->employee->department?->name, $p->period, $p->base_salary, $p->allowances, $p->bonus,
                    $p->overtime, $p->gross, $p->tax, $p->social_security, $p->benefits, $p->loan_deduction,
                    $p->other_deductions, $p->total_deductions, $p->net, $p->status,
                    $p->employee->bank_name, $p->employee->bank_account]);
            }
            fclose($out);
        }, "payroll-{$run->period}.csv", ['Content-Type' => 'text/csv']);
    }
}
