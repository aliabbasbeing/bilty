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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no', 50)->unique();
            $table->string('financial_year', 20)->nullable();
            $table->date('issue_date');
            $table->foreignId('company_id')->constrained()->onDelete('restrict');
            $table->json('consignment_ids'); // Array of consignment IDs
            $table->decimal('gross_amount', 10, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(4.00);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->json('meta')->nullable(); // For from/to addresses, bank details
            $table->enum('status', ['DRAFT', 'FINAL'])->default('DRAFT');
            $table->enum('payment_status', ['UNPAID', 'PAID'])->default('UNPAID');
            $table->date('payment_date')->nullable();
            $table->text('payment_note')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
            
            $table->index('bill_no');
            $table->index('company_id');
            $table->index('status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
