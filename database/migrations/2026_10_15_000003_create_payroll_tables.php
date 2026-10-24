<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->unique(); // YYYY-MM
            $table->string('status', 20)->default('draft'); // draft | approved | paid
            $table->date('payment_date')->nullable();
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->unsignedInteger('employee_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('payslip_no', 30)->unique();
            $table->string('period', 7);
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('overtime', 12, 2)->default(0);
            $table->decimal('gross', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('social_security', 12, 2)->default(0);
            $table->decimal('benefits', 12, 2)->default(0);
            $table->decimal('loan_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->string('status', 20)->default('pending'); // pending | processing | paid
            $table->date('payment_date')->nullable();
            $table->timestamps();
            $table->unique(['payroll_run_id', 'employee_id']);
        });

        // Bonuses, extra deductions and overtime entries picked up by the payroll run.
        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);
            $table->string('type', 20); // bonus | deduction | overtime
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['period', 'type']);
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('outstanding', 12, 2)->default(0);
            $table->decimal('monthly_installment', 12, 2)->default(0);
            $table->string('reason')->nullable();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected | repaid
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('category', 60);
            $table->date('expense_date');
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected | reimbursed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
    }
};
