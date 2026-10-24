<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login();
$admin = is_admin();
$me = my_employee();

if (is_post()) {
    verify_csrf();
    $act = $_POST['action'] ?? '';
    if ($act === 'request') {
        if (!$me) { http_response_code(403); exit('Only employees can request loans.'); }
        $amt = money_in($_POST['requested_amount'] ?? '');
        if (!$amt || $amt < 1 || $amt > 1000000) flash('error', 'Enter an amount between 1 and 1,000,000.');
        else { insert('loans', ['employee_id' => $me['id'], 'requested_amount' => $amt, 'reason' => mb_substr(trim((string) ($_POST['reason'] ?? '')), 0, 255) ?: null, 'status' => 'pending']); flash('ok', 'Loan request submitted.'); }
    } elseif ($act === 'decide' && $admin) {
        $loan = one('SELECT * FROM loans WHERE id=?', [(int) ($_POST['id'] ?? 0)]);
        $st = $_POST['status'] ?? '';
        if (!$loan || $loan['status'] !== 'pending' || !in_array($st, ['approved', 'rejected'], true)) flash('error', 'This request was already decided.');
        elseif ($st === 'rejected') { update('loans', (int) $loan['id'], ['status' => 'rejected']); flash('ok', 'Loan rejected.'); }
        else {
            $amt = money_in($_POST['approved_amount'] ?? ''); $n = (int) ($_POST['installments'] ?? 0);
            if (!$amt || $amt < 1 || $n < 1 || $n > 60) flash('error', 'Enter an approved amount and 1–60 instalments.');
            else {
                update('loans', (int) $loan['id'], ['status' => 'approved', 'approved_amount' => $amt, 'outstanding' => $amt, 'monthly_installment' => round($amt / $n, 2)]);
                flash('ok', 'Loan approved. It will be deducted automatically from payroll.');
            }
        }
    }
    redirect('loans.php');
}

$rows = $admin ? all('SELECT l.*, e.first_name, e.last_name FROM loans l JOIN employees e ON e.id=l.employee_id ORDER BY l.id DESC')
    : all('SELECT l.*, e.first_name, e.last_name FROM loans l JOIN employees e ON e.id=l.employee_id WHERE l.employee_id=? ORDER BY l.id DESC', [$me['id'] ?? 0]);
page_start('Loans', 'loans');
page_title('Loans', 'Approved loans are deducted from payroll in monthly instalments');
if ($me): ?>
<form method="post" class="<?= CARD ?> p-5 mb-6 grid gap-3 md:grid-cols-4 items-end">
  <?= csrf_field() ?><input type="hidden" name="action" value="request">
  <div><label class="block text-sm font-medium mb-1">Amount</label><input type="number" name="requested_amount" min="1" step="0.01" required class="<?= INPUT ?>"></div>
  <div class="md:col-span-2"><label class="block text-sm font-medium mb-1">Reason</label><input name="reason" maxlength="255" class="<?= INPUT ?>"></div>
  <button class="<?= BTN ?>">Request loan</button>
</form><?php endif; ?>
<div class="<?= CARD ?> overflow-hidden"><div class="overflow-x-auto"><table class="data">
  <thead><tr><?php if ($admin): ?><th>Employee</th><?php endif; ?><th class="num">Requested</th><th class="num">Approved</th><th class="num">Outstanding</th><th class="num">Monthly</th><th>Reason</th><th>Status</th><?php if ($admin): ?><th></th><?php endif; ?></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><?php if ($admin): ?><td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td><?php endif; ?>
    <td class="num"><?= money($r['requested_amount']) ?></td><td class="num"><?= $r['approved_amount'] !== null ? money($r['approved_amount']) : '—' ?></td>
    <td class="num"><?= money($r['outstanding']) ?></td><td class="num"><?= money($r['monthly_installment']) ?></td><td><?= e($r['reason'] ?? '—') ?></td><td><?= badge($r['status']) ?></td>
    <?php if ($admin): ?><td class="text-right"><?php if ($r['status'] === 'pending'): ?>
      <button class="text-blue-600" data-modal-open="#loanModal" data-fill="<?= e(json_encode(['id' => (string) $r['id'], 'approved_amount' => $r['requested_amount'], 'installments' => '6'])) ?>">Review</button><?php endif; ?></td><?php endif; ?></tr><?php endforeach;
  if (!$rows): ?><tr><td colspan="8" class="text-center text-slate-500 py-8">No loans.</td></tr><?php endif; ?>
</tbody></table></div></div>

<?php if ($admin): ?>
<div class="modal-backdrop" id="loanModal"><div class="<?= CARD ?> w-full max-w-md p-6">
  <h2 class="font-semibold text-lg mb-4">Review loan request</h2>
  <form method="post" class="space-y-4" data-loan-form><?= csrf_field() ?><input type="hidden" name="action" value="decide"><input type="hidden" name="id"><input type="hidden" name="status" value="approved">
    <div><label class="block text-sm font-medium mb-1">Approved amount</label><input type="number" name="approved_amount" min="1" step="0.01" required class="<?= INPUT ?>"></div>
    <div><label class="block text-sm font-medium mb-1">Number of monthly instalments</label><input type="number" name="installments" min="1" max="60" required class="<?= INPUT ?>"><div class="text-xs text-slate-500 mt-1" data-per-month></div></div>
    <div class="flex justify-between gap-2">
      <button type="submit" class="<?= BTN ?>">Approve</button>
      <button type="button" data-modal-close class="<?= BTN_GHOST ?>">Cancel</button>
    </div>
  </form>
  <form method="post" class="mt-3 pt-3 border-t border-slate-200" data-confirm="Reject this loan request?"><?= csrf_field() ?><input type="hidden" name="action" value="decide"><input type="hidden" name="id" id="loanRejectId"><input type="hidden" name="status" value="rejected">
    <button class="<?= BTN_DANGER ?>">Reject request</button></form>
</div></div>
<script>
  // keep the reject form's id in sync with the opened request
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-modal-open="#loanModal"]');
    if (b) document.getElementById('loanRejectId').value = JSON.parse(b.dataset.fill).id;
  });
</script>
<?php endif; ?>
<?php page_end();
