-- Payroll System schema (MySQL 5.7+/8, MariaDB 10.4+). Imported automatically by install.php.
-- Importing this file by hand (phpMyAdmin) also works; demo data comes from install.php.

CREATE DATABASE IF NOT EXISTS `payroll` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `payroll`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS expenses, loans, payroll_adjustments, payslips, payroll_runs,
  leave_requests, attendances, salary_adjustments, employees, departments, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','employee') NOT NULL DEFAULT 'employee',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  head_name VARCHAR(120) NULL,
  location VARCHAR(120) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE employees (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  department_id INT UNSIGNED NULL,
  employee_code VARCHAR(20) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(40) NULL,
  position VARCHAR(120) NOT NULL,
  location VARCHAR(120) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',   -- active | on_leave | inactive
  hire_date DATE NULL,
  base_salary DECIMAL(12,2) NOT NULL DEFAULT 0,   -- monthly
  allowances DECIMAL(12,2) NOT NULL DEFAULT 0,    -- monthly
  overtime_rate DECIMAL(8,2) NOT NULL DEFAULT 0,  -- per hour
  bank_name VARCHAR(120) NULL,
  bank_account VARCHAR(60) NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE salary_adjustments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  old_base_salary DECIMAL(12,2) NOT NULL,
  new_base_salary DECIMAL(12,2) NOT NULL,
  old_allowances DECIMAL(12,2) NOT NULL DEFAULT 0,
  new_allowances DECIMAL(12,2) NOT NULL DEFAULT 0,
  effective_date DATE NOT NULL,
  reason VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE attendances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  check_in TIME NULL,
  check_out TIME NULL,
  hours DECIMAL(5,2) NOT NULL DEFAULT 0,
  overtime_hours DECIMAL(5,2) NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'present',  -- present | late | absent | on_leave
  UNIQUE KEY uq_emp_day (employee_id, work_date),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE leave_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  leave_type VARCHAR(40) NOT NULL,                -- annual | sick | personal | unpaid
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  days SMALLINT UNSIGNED NOT NULL,
  reason TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- pending | approved | rejected
  decided_by INT UNSIGNED NULL,
  decided_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE payroll_runs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  period CHAR(7) NOT NULL UNIQUE,                 -- YYYY-MM
  status VARCHAR(20) NOT NULL DEFAULT 'draft',    -- draft | approved | paid
  payment_date DATE NULL,
  total_gross DECIMAL(14,2) NOT NULL DEFAULT 0,
  total_deductions DECIMAL(14,2) NOT NULL DEFAULT 0,
  total_net DECIMAL(14,2) NOT NULL DEFAULT 0,
  employee_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NULL,
  approved_by INT UNSIGNED NULL,
  approved_at DATETIME NULL,
  paid_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE payslips (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payroll_run_id INT UNSIGNED NOT NULL,
  employee_id INT UNSIGNED NOT NULL,
  payslip_no VARCHAR(30) NOT NULL UNIQUE,
  period CHAR(7) NOT NULL,
  base_salary DECIMAL(12,2) NOT NULL DEFAULT 0,
  allowances DECIMAL(12,2) NOT NULL DEFAULT 0,
  bonus DECIMAL(12,2) NOT NULL DEFAULT 0,
  overtime DECIMAL(12,2) NOT NULL DEFAULT 0,
  gross DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax DECIMAL(12,2) NOT NULL DEFAULT 0,
  social_security DECIMAL(12,2) NOT NULL DEFAULT 0,
  benefits DECIMAL(12,2) NOT NULL DEFAULT 0,
  loan_deduction DECIMAL(12,2) NOT NULL DEFAULT 0,
  other_deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
  net DECIMAL(12,2) NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- pending | processing | paid
  payment_date DATE NULL,
  UNIQUE KEY uq_run_emp (payroll_run_id, employee_id),
  FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id) ON DELETE CASCADE,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Bonuses, extra deductions and manual overtime picked up by the payroll run.
CREATE TABLE payroll_adjustments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  period CHAR(7) NOT NULL,
  type VARCHAR(20) NOT NULL,                      -- bonus | deduction | overtime
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_period_type (period, type),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE loans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  requested_amount DECIMAL(12,2) NOT NULL,
  approved_amount DECIMAL(12,2) NULL,
  outstanding DECIMAL(12,2) NOT NULL DEFAULT 0,
  monthly_installment DECIMAL(12,2) NOT NULL DEFAULT 0,
  reason VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- pending | approved | rejected | repaid
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE expenses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  category VARCHAR(60) NOT NULL,
  expense_date DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  description VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- pending | approved | rejected | reimbursed
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;
