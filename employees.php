<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_admin();

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $emp = one('SELECT * FROM employees WHERE id=?', [(int) ($_POST['id'] ?? 0)]);
    if ($emp) {
        if (val('SELECT COUNT(*) FROM payslips WHERE employee_id=?', [$emp['id']])) {
            flash('error', 'This employee has payslips. Set the status to Inactive instead of deleting.');
        } else {
            q('DELETE FROM employees WHERE id=?', [$emp['id']]);
            if ($emp['user_id']) q("DELETE FROM users WHERE id=? AND role='employee'", [$emp['user_id']]);
            flash('ok', 'Employee deleted.');
        }
    }
    redirect('employees.php');
}

$where = ['1=1']; $p = [];
if (($s = trim((string) ($_GET['q'] ?? ''))) !== '') {
    $where[] = '(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR e.email LIKE ? OR e.position LIKE ?)';
    array_push($p, ...array_fill(0, 5, "%$s%"));
}
if (!empty($_GET['department'])) { $where[] = 'e.department_id=?'; $p[] = (int) $_GET['department']; }
if (!empty($_GET['status'])) { $where[] = 'e.status=?'; $p[] = $_GET['status']; }
$W = implode(' AND ', $where);
$pg = paginate((int) val("SELECT COUNT(*) FROM employees e WHERE $W", $p), 12);
$rows = all("SELECT e.*, d.name dept FROM employees e LEFT JOIN departments d ON d.id=e.department_id WHERE $W ORDER BY e.first_name, e.last_name LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
$depts = all('SELECT id, name FROM departments ORDER BY name');

page_start('Employees', 'employees');
page_title('Employees', $pg['total'] . ' record(s)', btn('Add employee', 'employee_form.php'));
?>
<form class="no-print mb-4 grid gap-3 sm:grid-cols-4" method="get">
  <input name="q" value="<?= e($s) ?>" placeholder="Search name, code, email, position…" class="<?= INPUT ?> sm:col-span-2">
  <select name="department" class="<?= INPUT ?>"><option value="">All departments</option>
    <?php foreach ($depts as $d): ?><option value="<?= $d['id'] ?>" <?= (int) ($_GET['department'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select>
  <div class="flex gap-2"><select name="status" class="<?= INPUT ?>"><option value="">All statuses</option>
    <?php foreach (['active', 'on_leave', 'inactive'] as $st): ?><option value="<?= $st ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $st))) ?></option><?php endforeach; ?></select>
    <button class="<?= BTN ?>">Filter</button></div>
</form>
<div class="<?= CARD ?> overflow-hidden">
  <div class="overflow-x-auto"><table class="data">
    <thead><tr><th>Employee</th><th>Department</th><th>Position</th><th class="num">Base salary</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a class="font-medium text-slate-900 hover:text-blue-600" href="employee.php?id=<?= $r['id'] ?>"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></a>
          <div class="text-xs text-slate-500"><?= e($r['employee_code']) ?> · <?= e($r['email']) ?></div></td>
        <td><?= e($r['dept'] ?? '—') ?></td><td><?= e($r['position']) ?></td>
        <td class="num"><?= money($r['base_salary']) ?></td><td><?= badge($r['status']) ?></td>
        <td class="text-right whitespace-nowrap">
          <a class="text-blue-600 mr-3" href="employee_form.php?id=<?= $r['id'] ?>">Edit</a>
          <form method="post" class="inline" data-confirm="Delete this employee?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button class="text-red-600">Delete</button></form></td>
      </tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-slate-500 py-8">No employees found.</td></tr><?php endif; ?>
    </tbody></table></div>
  <?= pager($pg) ?>
</div>
<?php page_end();
