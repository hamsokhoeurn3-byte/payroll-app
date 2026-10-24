<?php

use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\SalaryController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Self-service + shared pages (data is scoped to the signed-in employee inside the controllers)
    Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
    Route::get('/payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/toggle', [AttendanceController::class, 'toggle'])->name('attendance.toggle');

    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');

    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

    // Administrator-only
    Route::middleware('admin')->group(function () {
        Route::resource('employees', EmployeeController::class);
        Route::resource('departments', DepartmentController::class)->except(['show', 'create', 'edit']);

        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/runs', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('/payroll/runs/{run}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::get('/payroll/runs/{run}/export', [PayrollController::class, 'export'])->name('payroll.export');
        Route::post('/payroll/runs/{run}/recalculate', [PayrollController::class, 'recalculate'])->name('payroll.recalculate');
        Route::post('/payroll/runs/{run}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
        Route::post('/payroll/runs/{run}/pay', [PayrollController::class, 'pay'])->name('payroll.pay');
        Route::delete('/payroll/runs/{run}', [PayrollController::class, 'destroy'])->name('payroll.destroy');

        Route::get('/adjustments', [AdjustmentController::class, 'index'])->name('adjustments.index');
        Route::post('/adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store');
        Route::delete('/adjustments/{adjustment}', [AdjustmentController::class, 'destroy'])->name('adjustments.destroy');

        Route::get('/salary', [SalaryController::class, 'index'])->name('salary.index');
        Route::put('/salary/{employee}', [SalaryController::class, 'update'])->name('salary.update');

        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::patch('/leave/{leave}', [LeaveController::class, 'decide'])->name('leave.decide');
        Route::patch('/loans/{loan}', [LoanController::class, 'decide'])->name('loans.decide');
        Route::patch('/expenses/{expense}', [ExpenseController::class, 'decide'])->name('expenses.decide');
    });
});
