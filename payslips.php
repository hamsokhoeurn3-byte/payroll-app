<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login();
$admin = is_admin();
$me = my_employee();

$where = ['1=1']; $p = [];
if (!$admin) {   // employees only ever see their own, and only once approved
    $where[] = "p.employee_id=? AND p.status IN ('processing','paid')";
    $p[] = $me['id'] ?? 0;
} else {
    if (($s = trim((string) ($_GET['q'] ?? ''))) !== '') {
        $where[] = '(p.payslip_no LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ?)'; array_push($p, "%$s%", "%$s%", "%$s%");
    }
    if (!empty($_GET['status'])) { $where[] = 'p.status=?'; $p[] = $_GET['status']; }
    if (!empty($_GET['period']) && valid_period((string) $_GET['period'])) { $where[] = 'p.period=?'; $p[] = $_GET['period']; }
}
$W = implode(' AND ', $where);
$pg = paginate((int) val("SELECT COUNT(*) FROM payslips p JOIN employees e ON e.id=p.employee_id WHERE $W", $p), 15);
$rows = all("SELECT p.*, e.first_name, e.last_name, e.employee_code FROM payslips p JOIN employees e ON e.id=p.employee_id WHERE $W
    ORDER BY p.period DESC, e.first_name LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);

page_start('Payslips', 'payslips');
page_title('Payslips', $admin ? 'All employees' : 'Your payslips');
if ($admin): ?>
<form class="no-print mb-4 grid gap-3 sm:grid-cols-4" method="get">
  <input name="q" value="<?= e($s ?? '') ?>" placeholder="Search name or payslip no…" class="<?= INPUT ?>">
  <input type="month" name="period" value="<?= e($_GET['period'] ?? '') ?>" class="<?= INPUT ?>">
  <select name="status" class="<?= INPUT ?>"><option value="">All statuses</option><?php foreach (['pending', 'processing', 'paid'] as $st): ?><option value="<?= $st ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option><?php endforeach; ?></select>
  <button class="<?= BTN ?>">Filter</button>
</form><?php endif; ?>
<div class="<?= CARD ?> overflow-hidden"><div class="overflow-x-auto"><table class="data">
  <thead><tr><th>Payslip</th><?php if ($admin): ?><th>Employee</th><?php endif; ?><th>Period</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr>
    <td><?= e($r['payslip_no']) ?></td><?php if ($admin): ?><td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td><?php endif; ?>
    <td><?= e(period_label($r['period'])) ?></td><td class="num"><?= money($r['gross']) ?></td><td class="num"><?= money($r['total_deductions']) ?></td>
    <td class="num font-medium"><?= money($r['net']) ?></td><td><?= badge($r['status']) ?></td><td class="text-right"><a class="text-blue-600" href="payslip.php?id=<?= $r['id'] ?>">View</a></td></tr><?php endforeach;
  if (!$rows): ?><tr><td colspan="8" class="text-center text-slate-500 py-8">No payslips found.</td></tr><?php endif; ?>
</tbody></table></div><?= pager($pg) ?></div>
<?php page_end();
