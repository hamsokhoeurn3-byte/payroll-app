<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login();

page_start('Dashboard', 'dashboard');

if (is_admin()):
    $activeCount = (int) val("SELECT COUNT(*) FROM employees WHERE status='active'");
    $totalCount = (int) val('SELECT COUNT(*) FROM employees');
    $runs = all('SELECT * FROM payroll_runs ORDER BY period DESC LIMIT 6');
    $latest = $runs[0] ?? null;
    $pendLeave = (int) val("SELECT COUNT(*) FROM leave_requests WHERE status='pending'");
    $pendLoans = (int) val("SELECT COUNT(*) FROM loans WHERE status='pending'");
    $pendExp = (int) val("SELECT COUNT(*) FROM expenses WHERE status='pending'");
    $mix = all("SELECT COALESCE(d.name,'Unassigned') name, COUNT(*) c FROM employees e LEFT JOIN departments d ON d.id=e.department_id WHERE e.status<>'inactive' GROUP BY d.name ORDER BY c DESC");
    $chart = array_reverse($runs);
    $max = max(1, ...array_map(fn($r) => (float) $r['total_gross'], $chart ?: [['total_gross' => 1]]));
    $leaveList = all("SELECT l.*, e.first_name, e.last_name FROM leave_requests l JOIN employees e ON e.id=l.employee_id WHERE l.status='pending' ORDER BY l.created_at DESC LIMIT 5");
    page_title('Dashboard', 'Payroll overview for ' . cfg('app.company'));
    ?>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
      <?= kpi('Active employees', (string) $activeCount, 'users', $totalCount . ' total records') ?>
      <?= kpi('Latest payroll (net)', $latest ? money($latest['total_net']) : '—', 'wallet', $latest ? period_label($latest['period']) . ' · ' . ucfirst($latest['status']) : 'No payroll run yet') ?>
      <?= kpi('Gross payroll', $latest ? money($latest['total_gross']) : '—', 'trending-up', $latest ? 'Deductions ' . money($latest['total_deductions']) : '') ?>
      <?= kpi('Pending approvals', (string) ($pendLeave + $pendLoans + $pendExp), 'bell', "$pendLeave leave · $pendLoans loans · $pendExp expenses") ?>
    </div>

    <div class="grid gap-6 lg:grid-cols-3 mb-6">
      <div class="<?= CARD ?> p-5 lg:col-span-2">
        <h2 class="font-semibold mb-4">Payroll history <span class="text-xs font-normal text-slate-500">(gross vs net)</span></h2>
        <?php if (!$chart): ?><p class="text-sm text-slate-500">Run payroll to see the chart.</p><?php else: ?>
        <div class="flex items-end gap-4 h-52">
          <?php foreach ($chart as $r): $g = (float) $r['total_gross'] / $max * 100; $n = (float) $r['total_net'] / $max * 100; ?>
            <div class="flex-1 h-full flex flex-col justify-end items-center gap-1" title="<?= e(period_label($r['period'])) ?>: gross <?= e(money($r['total_gross'])) ?>, net <?= e(money($r['total_net'])) ?>">
              <div class="flex items-end gap-1 w-full justify-center h-[calc(100%-1.5rem)]">
                <div class="w-1/2 max-w-[28px] rounded-t bg-blue-200" style="height:<?= round($g, 1) ?>%"></div>
                <div class="w-1/2 max-w-[28px] rounded-t bg-blue-600" style="height:<?= round($n, 1) ?>%"></div>
              </div>
              <span class="text-xs text-slate-500"><?= e(date('M y', strtotime($r['period'] . '-01'))) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="flex gap-4 text-xs text-slate-500 mt-3"><span><span class="inline-block h-2 w-2 rounded-sm bg-blue-200"></span> Gross</span><span><span class="inline-block h-2 w-2 rounded-sm bg-blue-600"></span> Net</span></div>
        <?php endif; ?>
      </div>
      <div class="<?= CARD ?> p-5">
        <h2 class="font-semibold mb-4">Headcount by department</h2>
        <?php foreach ($mix as $m): $pct = $totalCount ? round($m['c'] / max(1, array_sum(array_column($mix, 'c'))) * 100) : 0; ?>
          <div class="mb-3"><div class="flex justify-between text-sm mb-1"><span><?= e($m['name']) ?></span><span class="text-slate-500"><?= (int) $m['c'] ?></span></div>
            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-blue-600" style="width:<?= $pct ?>%"></div></div></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="<?= CARD ?>">
      <div class="flex items-center justify-between p-5 pb-3"><h2 class="font-semibold">Leave awaiting approval</h2><a class="text-sm text-blue-600" href="leave.php?status=pending">View all</a></div>
      <?php if (!$leaveList): ?><p class="px-5 pb-5 text-sm text-slate-500">Nothing pending.</p><?php else: ?>
      <div class="overflow-x-auto"><table class="data"><thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th class="num">Days</th></tr></thead><tbody>
        <?php foreach ($leaveList as $l): ?><tr><td><?= e($l['first_name'] . ' ' . $l['last_name']) ?></td><td><?= e(ucfirst($l['leave_type'])) ?></td>
          <td><?= fdate($l['start_date']) ?> – <?= fdate($l['end_date']) ?></td><td class="num"><?= (int) $l['days'] ?></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </div>

<?php else:
    $emp = my_employee();
    page_title('Welcome, ' . user()['name'], date('l, d F Y'));
    if (!$emp): ?>
      <div class="<?= CARD ?> p-6 text-sm text-slate-600">Your login is not linked to an employee profile yet. Ask an administrator.</div>
    <?php else:
        $slip = one("SELECT * FROM payslips WHERE employee_id=? AND status IN ('processing','paid') ORDER BY period DESC LIMIT 1", [$emp['id']]);
        $recent = all("SELECT * FROM payslips WHERE employee_id=? AND status IN ('processing','paid') ORDER BY period DESC LIMIT 5", [$emp['id']]);
        $used = (int) val("SELECT COALESCE(SUM(days),0) FROM leave_requests WHERE employee_id=? AND status='approved' AND leave_type='annual' AND YEAR(start_date)=YEAR(CURDATE())", [$emp['id']]);
        $allow = (int) cfg('payroll.annual_leave_days');
        $today = one('SELECT * FROM attendances WHERE employee_id=? AND work_date=CURDATE()', [$emp['id']]);
        $loan = (float) val("SELECT COALESCE(SUM(outstanding),0) FROM loans WHERE employee_id=? AND status='approved'", [$emp['id']]);
        ?>
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <?= kpi('Latest net pay', $slip ? money($slip['net']) : '—', 'wallet', $slip ? period_label($slip['period']) : 'No approved payslip yet') ?>
        <?= kpi('Annual leave left', ($allow - $used) . ' days', 'calendar-days', "$used of $allow used this year") ?>
        <?= kpi('Loan outstanding', money($loan), 'landmark') ?>
        <div class="<?= CARD ?> p-5">
          <div class="text-sm text-slate-500 mb-2">Today's attendance</div>
          <div class="text-sm mb-3"><?= $today ? 'In ' . e(substr((string) $today['check_in'], 0, 5)) . ($today['check_out'] ? ' · Out ' . e(substr($today['check_out'], 0, 5)) : '') : 'Not checked in' ?></div>
          <?php if (!$today || !$today['check_out']): ?>
          <form method="post" action="attendance.php"><?= csrf_field() ?><input type="hidden" name="action" value="toggle">
            <button class="<?= BTN_SM ?>"><?= $today ? 'Check out' : 'Check in' ?></button></form>
          <?php else: ?><span class="text-xs text-emerald-600">Done for today</span><?php endif; ?>
        </div>
      </div>
      <div class="<?= CARD ?>"><div class="p-5 pb-3 flex justify-between"><h2 class="font-semibold">Recent payslips</h2><a class="text-sm text-blue-600" href="payslips.php">View all</a></div>
        <?php if (!$recent): ?><p class="px-5 pb-5 text-sm text-slate-500">Payslips appear here once payroll is approved.</p><?php else: ?>
        <div class="overflow-x-auto"><table class="data"><thead><tr><th>Period</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th><th>Status</th><th></th></tr></thead><tbody>
          <?php foreach ($recent as $p): ?><tr><td><?= e(period_label($p['period'])) ?></td><td class="num"><?= money($p['gross']) ?></td><td class="num"><?= money($p['total_deductions']) ?></td>
            <td class="num font-medium"><?= money($p['net']) ?></td><td><?= badge($p['status']) ?></td><td class="text-right"><a class="text-blue-600" href="payslip.php?id=<?= (int) $p['id'] ?>">View</a></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?></div>
    <?php endif;
endif;
page_end();
