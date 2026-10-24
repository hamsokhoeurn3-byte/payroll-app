<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('head_name')->nullable();
            $table->string('location')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code', 20)->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone', 40)->nullable();
            $table->string('position');
            $table->string('location')->nullable();
            $table->string('status', 20)->default('active'); // active | on_leave | inactive
            $table->date('hire_date')->nullable();
            $table->decimal('base_salary', 12, 2)->default(0);   // monthly
            $table->decimal('allowances', 12, 2)->default(0);    // monthly
            $table->decimal('overtime_rate', 8, 2)->default(0);  // per hour
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_base_salary', 12, 2);
            $table->decimal('new_base_salary', 12, 2);
            $table->decimal('old_allowances', 12, 2)->default(0);
            $table->decimal('new_allowances', 12, 2)->default(0);
            $table->date('effective_date');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_adjustments');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('departments');
    }
};
