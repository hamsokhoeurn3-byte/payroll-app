<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login();

$s = one('SELECT p.*, e.first_name, e.last_name, e.employee_code, e.position, e.bank_name, e.bank_account, e.user_id, d.name dept
    FROM payslips p JOIN employees e ON e.id=p.employee_id LEFT JOIN departments d ON d.id=e.department_id WHERE p.id=?', [(int) ($_GET['id'] ?? 0)]);
if (!$s) { http_response_code(404); exit('Payslip not found.'); }
if (!is_admin()) {
    $me = my_employee();
    if (!$me || (int) $me['id'] !== (int) $s['employee_id'] || $s['status'] === 'pending') { http_response_code(403); exit('403 - This payslip is not available.'); }
}

page_start('Payslip ' . $s['payslip_no'], 'payslips');
page_title('Payslip', $s['payslip_no'], '<button data-print class="' . BTN . '">Print / Save as PDF</button>' . btn('Back', 'payslips.php', 'ghost'));
$row = fn($l, $v, $b = false) => '<tr><td class="py-2 ' . ($b ? 'font-semibold' : 'text-slate-600') . '">' . e($l) . '</td><td class="py-2 text-right tabular-nums ' . ($b ? 'font-semibold' : '') . '">' . money($v) . '</td></tr>';
?>
<div class="<?= CARD ?> print-card max-w-3xl mx-auto p-8">
  <div class="flex justify-between border-b border-slate-200 pb-5 mb-5">
    <div><div class="text-xl font-semibold"><?= e(cfg('app.company')) ?></div><div class="text-sm text-slate-500">Payslip for <?= e(period_label($s['period'])) ?></div></div>
    <div class="text-right text-sm"><div class="font-medium"><?= e($s['payslip_no']) ?></div><div class="mt-1"><?= badge($s['status']) ?></div><div class="text-slate-500 mt-1">Pay date <?= fdate($s['payment_date']) ?></div></div>
  </div>
  <div class="grid grid-cols-2 gap-4 text-sm mb-6">
    <div><div class="text-slate-500">Employee</div><div class="font-medium"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></div><div><?= e($s['employee_code']) ?> · <?= e($s['position']) ?></div><div><?= e($s['dept'] ?? '') ?></div></div>
    <div><div class="text-slate-500">Bank</div><div class="font-medium"><?= e($s['bank_name'] ?? '—') ?></div><div><?= e($s['bank_account'] ?? '') ?></div></div>
  </div>
  <div class="grid sm:grid-cols-2 gap-8">
    <div><h3 class="font-semibold mb-2">Earnings</h3><table class="w-full text-sm"><?= $row('Base salary', $s['base_salary']) . $row('Allowances', $s['allowances']) . $row('Bonus', $s['bonus']) . $row('Overtime', $s['overtime']) ?>
      <tr class="border-t border-slate-200"><?= substr($row('Gross pay', $s['gross'], true), 4) ?></table></div>
    <div><h3 class="font-semibold mb-2">Deductions</h3><table class="w-full text-sm"><?= $row('Income tax', $s['tax']) . $row('Social security', $s['social_security']) . $row('Benefits', $s['benefits']) . $row('Loan repayment', $s['loan_deduction']) . $row('Other deductions', $s['other_deductions']) ?>
      <tr class="border-t border-slate-200"><?= substr($row('Total deductions', $s['total_deductions'], true), 4) ?></table></div>
  </div>
  <div class="mt-8 rounded-lg bg-blue-600 text-white px-5 py-4 flex justify-between items-center"><span class="font-medium">Net pay</span><span class="text-2xl font-semibold"><?= money($s['net']) ?></span></div>
  <p class="text-xs text-slate-500 mt-6">This is a computer-generated payslip.</p>
</div>
<?php page_end();
