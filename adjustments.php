<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/payroll.php';
$admin = require_admin();

$period = valid_period((string) ($_GET['period'] ?? '')) ? $_GET['period'] : date('Y-m');

if (is_post()) {
    verify_csrf();
    $act = $_POST['action'] ?? '';
    $per = (string) ($_POST['period'] ?? '');
    if (!valid_period($per)) { flash('error', 'Invalid period.'); redirect('adjustments.php'); }
    if (run_locked($per)) {
        flash('error', 'Payroll for ' . period_label($per) . ' is already approved/paid and locked.');
    } elseif ($act === 'add') {
        $amt = money_in($_POST['amount'] ?? '');
        $type = $_POST['type'] ?? '';
        $emp = (int) ($_POST['employee_id'] ?? 0);
        $desc = trim((string) ($_POST['description'] ?? ''));
        if (!in_array($type, ['bonus', 'deduction', 'overtime'], true) || !$amt || $amt <= 0 || $desc === '' || !val('SELECT COUNT(*) FROM employees WHERE id=?', [$emp])) {
            flash('error', 'Please complete all fields with a valid amount.');
        } else {
            insert('payroll_adjustments', ['employee_id' => $emp, 'period' => $per, 'type' => $type, 'description' => $desc, 'amount' => $amt, 'created_by' => $admin['id']]);
            flash('ok', 'Entry added. Recalculate the draft payroll to include it.');
        }
    } elseif ($act === 'delete') {
        q('DELETE FROM payroll_adjustments WHERE id=?', [(int) ($_POST['id'] ?? 0)]);
        flash('ok', 'Entry removed. Recalculate the draft payroll to apply.');
    }
    redirect('adjustments.php?period=' . $per);
}

$rows = all('SELECT a.*, e.first_name, e.last_name FROM payroll_adjustments a JOIN employees e ON e.id=a.employee_id WHERE a.period=? ORDER BY a.id DESC', [$period]);
$locked = run_locked($period);
page_start('Bonuses & deductions', 'adjustments');
page_title('Bonuses & deductions', 'Entries here are picked up by the payroll run for the month');
?>
<form method="get" class="no-print mb-4 flex items-end gap-3"><div><label class="block text-sm font-medium mb-1">Period</label><input type="month" name="period" value="<?= e($period) ?>" class="<?= INPUT ?>"></div><button class="<?= BTN_GHOST ?>">Show</button>
  <?php if ($locked): ?><span class="text-sm text-amber-700">This period is locked.</span><?php endif; ?></form>
<?php if (!$locked): ?>
<form method="post" class="<?= CARD ?> p-5 mb-6 grid gap-3 md:grid-cols-6 items-end">
  <?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="period" value="<?= e($period) ?>">
  <div class="md:col-span-2"><label class="block text-sm font-medium mb-1">Employee</label><?= employee_select() ?></div>
  <div><label class="block text-sm font-medium mb-1">Type</label><select name="type" class="<?= INPUT ?>"><option value="bonus">Bonus</option><option value="deduction">Deduction</option><option value="overtime">Overtime (manual)</option></select></div>
  <div><label class="block text-sm font-medium mb-1">Description</label><input name="description" required maxlength="200" class="<?= INPUT ?>"></div>
  <div><label class="block text-sm font-medium mb-1">Amount</label><input name="amount" type="number" step="0.01" min="0.01" required class="<?= INPUT ?>"></div>
  <button class="<?= BTN ?>">Add</button>
</form><?php endif; ?>
<div class="<?= CARD ?> overflow-hidden"><div class="overflow-x-auto"><table class="data">
  <thead><tr><th>Employee</th><th>Type</th><th>Description</th><th class="num">Amount</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td><td><?= e(ucfirst($r['type'])) ?></td><td><?= e($r['description']) ?></td><td class="num"><?= money($r['amount']) ?></td>
    <td class="text-right"><?php if (!$locked): ?><form method="post" class="inline" data-confirm="Remove this entry?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="period" value="<?= e($period) ?>"><button class="text-red-600">Remove</button></form><?php endif; ?></td></tr><?php endforeach;
  if (!$rows): ?><tr><td colspan="5" class="text-center text-slate-500 py-8">No entries for <?= e(period_label($period)) ?>.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php page_end();
