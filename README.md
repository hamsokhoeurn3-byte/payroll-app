# Payroll System (Laravel 11 + MySQL)

Built from the Figma Make design "Payroll System UI Design" (HRPro-style: navy sidebar, blue accent, KPI cards, tables, payslip view). This folder is the **application layer** that sits on top of a fresh Laravel 11 install.

## Install

```bash
# needs PHP 8.2+, Composer and a running MySQL/MariaDB
DB_USER=root DB_PASS=yourpassword ./install.sh ../payroll-app
cd ../payroll-app && php artisan serve
```

`install.sh` creates a fresh Laravel 11 project (if the target is empty), copies these files over it, writes the MySQL settings to `.env`, creates the `payroll` database, and runs `php artisan migrate:fresh --seed`.

Manual equivalent:

```bash
composer create-project laravel/laravel payroll-app "^11.0"
cp -R app bootstrap config database public resources routes payroll-app/
cd payroll-app
# .env: DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=payroll, DB_USERNAME=…, DB_PASSWORD=…
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Demo logins (seeded, change before real use): `admin@payroll.test` / `password` and `olivia@payroll.test` / `password` (employee).

## What's included

| Area | Admin | Employee |
|---|---|---|
| Dashboard (KPIs, payroll chart, department mix, approvals) | yes | own summary + check in/out |
| Employees & departments (CRUD, search/filter, optional portal login) | yes | |
| Payroll runs: generate draft → recalculate → approve → mark paid, CSV export | yes | |
| Payslips (printable / save as PDF) | all | own, once approved |
| Bonuses, deductions, manual overtime per month | yes | |
| Salary adjustments with history | yes | |
| Attendance (auto overtime hours) | manage | check in/out, history |
| Leave requests | approve/reject | request, balance |
| Loans (auto-deducted from payroll, balance reduced when paid) | approve | request |
| Expenses | approve/reimburse | submit |

## How pay is calculated (`app/Services/PayrollService.php`)

gross = base + allowances + bonuses + overtime (attendance overtime hours × hourly rate + manual overtime)
deductions = income tax (progressive brackets) + social security (rate, capped) + flat benefits + loan instalments + other deductions
net = gross − deductions

**The tax brackets, social-security rate and benefits in `config/payroll.php` are sample values.** Replace them with the rules for your country/organisation and have them checked before running real payroll.

## Notes

- Roles: `users.role` is `admin` or `employee`; admin routes use the `admin` middleware and employee data is always scoped to the signed-in user on the server.
- Approved/paid runs are locked. Draft runs can be recalculated after salary or adjustment changes.
- Only the payroll-related modules from the design are built. Recruitment, assets, training, benefits enrolment, exit management, messages, notifications, Arabic/RTL and the marketing landing page from the Figma file are not included.
- Not yet run against a live database or covered by automated tests; run `php artisan migrate:fresh --seed` and click through the main flows first.
