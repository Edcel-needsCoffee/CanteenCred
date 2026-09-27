<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('faculty_id')->constrained('faculty')->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('transaction_date');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['active', 'voided', 'corrected'])->default('active');
            $table->boolean('credit_limit_override')->default(false);
            $table->string('override_reason')->nullable();
            $table->foreignId('override_authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('payroll_deduction_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};