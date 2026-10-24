<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$emp = $id ? one('SELECT * FROM employees WHERE id=?', [$id]) : null;
if ($id && !$emp) { http_response_code(404); exit('Employee not found.'); }
$errors = [];

if (is_post()) {
    verify_csrf();
    $d = [
        'first_name' => trim((string) $_POST['first_name']), 'last_name' => trim((string) $_POST['last_name']),
        'email' => strtolower(trim((string) $_POST['email'])), 'phone' => trim((string) $_POST['phone']) ?: null,
        'position' => trim((string) $_POST['position']), 'location' => trim((string) $_POST['location']) ?: null,
        'department_id' => (int) $_POST['department_id'] ?: null,
        'status' => in_array($_POST['status'], ['active', 'on_leave', 'inactive'], true) ? $_POST['status'] : 'active',
        'hire_date' => $_POST['hire_date'] !== '' ? $_POST['hire_date'] : null,
        'bank_name' => trim((string) $_POST['bank_name']) ?: null, 'bank_account' => trim((string) $_POST['bank_account']) ?: null,
    ];
    foreach (['base_salary', 'allowances', 'overtime_rate'] as $f) {
        $d[$f] = money_in($_POST[$f] ?? '0');
        if ($d[$f] === null) $errors[$f] = 'Enter a valid non-negative amount.';
    }
    if ($d['first_name'] === '' || $d['last_name'] === '') $errors['first_name'] = 'First and last name are required.';
    if ($d['position'] === '') $errors['position'] = 'Position is required.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
    elseif (val('SELECT COUNT(*) FROM employees WHERE email=? AND id<>?', [$d['email'], $id])) $errors['email'] = 'This email is already used by another employee.';
    if ($d['hire_date'] !== null && !valid_date($d['hire_date'])) $errors['hire_date'] = 'Invalid date.';
    $portal = !empty($_POST['portal_login']);
    $pw = (string) ($_POST['password'] ?? '');
    $hasLogin = $emp && $emp['user_id'];
    if ($portal && !$hasLogin && strlen($pw) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    if (!isset($errors['email']) && ($portal || $hasLogin)
        && val('SELECT COUNT(*) FROM users WHERE email=? AND id<>?', [$d['email'], (int) ($emp['user_id'] ?? 0)])) {
        $errors['email'] = 'A user account with this email already exists.';
    }

    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        try {
            if ($emp) {
                if ($d['base_salary'] != (float) $emp['base_salary'] || $d['allowances'] != (float) $emp['allowances']) {
                    insert('salary_adjustments', ['employee_id' => $id, 'old_base_salary' => $emp['base_salary'], 'new_base_salary' => $d['base_salary'],
                        'old_allowances' => $emp['allowances'], 'new_allowances' => $d['allowances'], 'effective_date' => date('Y-m-d'),
                        'reason' => 'Edited on employee profile', 'created_by' => user()['id']]);
                }
                update('employees', $id, $d);
                if ($emp['user_id']) {
                    update('users', (int) $emp['user_id'], ['name' => $d['first_name'] . ' ' . $d['last_name'], 'email' => $d['email'], 'is_active' => $d['status'] === 'inactive' ? 0 : 1]);
                }
                $uid = $emp['user_id'];
            } else {
                $d['employee_code'] = 'EMP-' . str_pad((string) ((int) val("SELECT COALESCE(MAX(CAST(SUBSTRING(employee_code,5) AS UNSIGNED)),1000) FROM employees") + 1), 4, '0', STR_PAD_LEFT);
                $id = insert('employees', $d);
                $uid = null;
            }
            if ($portal && !$uid) {
                $uid = insert('users', ['name' => $d['first_name'] . ' ' . $d['last_name'], 'email' => $d['email'], 'password' => password_hash($pw, PASSWORD_DEFAULT), 'role' => 'employee']);
                update('employees', $id, ['user_id' => $uid]);
            }
            $pdo->commit();
            flash('ok', $emp ? 'Employee updated.' : 'Employee created.');
            redirect('employee.php?id=' . $id);
        } catch (Throwable $t) { $pdo->rollBack(); $errors['_'] = 'Could not save: ' . $t->getMessage(); }
    }
}

$v = fn(string $k, $def = '') => $_POST[$k] ?? ($emp[$k] ?? $def);
$depts = all('SELECT id, name FROM departments ORDER BY name');
$err = fn(string $k) => isset($errors[$k]) ? '<p class="text-xs text-red-600 mt-1">' . e($errors[$k]) . '</p>' : '';
page_start($emp ? 'Edit employee' : 'Add employee', 'employees');
page_title($emp ? 'Edit ' . $emp['first_name'] . ' ' . $emp['last_name'] : 'Add employee', '', btn('Back', 'employees.php', 'ghost'));
if (isset($errors['_'])) echo '<div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 text-sm px-4 py-3">' . e($errors['_']) . '</div>';
$field = function (string $label, string $name, string $type = 'text', array $attr = []) use ($v, $err) {
    $a = ''; foreach ($attr as $k => $x) $a .= " $k=\"" . e($x) . '"';
    return '<div><label class="block text-sm font-medium mb-1">' . e($label) . '</label><input type="' . $type . '" name="' . $name . '" value="' . e($v($name)) . '" class="' . INPUT . '"' . $a . '>' . $err($name) . '</div>';
};
?>
<form method="post" class="<?= CARD ?> p-6 space-y-6">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <section><h2 class="font-semibold mb-3">Personal &amp; job</h2>
    <div class="grid gap-4 sm:grid-cols-2">
      <?= $field('First name', 'first_name', 'text', ['required' => 1]) ?><?= $field('Last name', 'last_name', 'text', ['required' => 1]) ?>
      <?= $field('Email', 'email', 'email', ['required' => 1]) ?><?= $field('Phone', 'phone') ?>
      <?= $field('Position', 'position', 'text', ['required' => 1]) ?><?= $field('Location', 'location') ?>
      <div><label class="block text-sm font-medium mb-1">Department</label><select name="department_id" class="<?= INPUT ?>"><option value="">None</option>
        <?php foreach ($depts as $d): ?><option value="<?= $d['id'] ?>" <?= (int) $v('department_id') === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
      <div><label class="block text-sm font-medium mb-1">Status</label><select name="status" class="<?= INPUT ?>">
        <?php foreach (['active', 'on_leave', 'inactive'] as $st): ?><option value="<?= $st ?>" <?= $v('status', 'active') === $st ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $st))) ?></option><?php endforeach; ?></select></div>
      <?= $field('Hire date', 'hire_date', 'date') ?>
    </div></section>
  <section><h2 class="font-semibold mb-3">Compensation (monthly)</h2>
    <div class="grid gap-4 sm:grid-cols-3">
      <?= $field('Base salary', 'base_salary', 'number', ['step' => '0.01', 'min' => 0, 'required' => 1]) ?>
      <?= $field('Allowances', 'allowances', 'number', ['step' => '0.01', 'min' => 0]) ?>
      <?= $field('Overtime rate / hour', 'overtime_rate', 'number', ['step' => '0.01', 'min' => 0]) ?>
      <?= $field('Bank name', 'bank_name') ?><?= $field('Bank account', 'bank_account') ?>
    </div></section>
  <section><h2 class="font-semibold mb-3">Employee portal login</h2>
    <?php if ($emp && $emp['user_id']): ?><p class="text-sm text-slate-600">This employee already has a portal login (<?= e($emp['email']) ?>). Setting status to Inactive disables it.</p>
    <?php else: ?>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="portal_login" name="portal_login" value="1" <?= !empty($_POST['portal_login']) ? 'checked' : '' ?>> Create a login so this employee can view payslips, request leave, etc.</label>
      <div id="portal_fields" class="mt-3 max-w-sm hidden"><label class="block text-sm font-medium mb-1">Initial password (min 8 chars)</label><input type="password" name="password" class="<?= INPUT ?>" autocomplete="new-password"><?= $err('password') ?></div>
    <?php endif; ?></section>
  <div class="flex gap-2"><button class="<?= BTN ?>">Save employee</button><a href="employees.php" class="<?= BTN_GHOST ?>">Cancel</a></div>
</form>
<?php page_end();
