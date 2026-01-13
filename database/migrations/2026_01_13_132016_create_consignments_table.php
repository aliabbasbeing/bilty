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
        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('restrict');
            $table->string('bilty_no', 50)->unique();
            $table->date('date');
            $table->string('vehicle_no', 50);
            $table->string('driver_name', 100);
            $table->string('driver_number', 50)->nullable();
            $table->string('vehicle_type', 50)->nullable();
            $table->enum('vehicle_owner', ['own', 'rental'])->default('own');
            $table->string('sender_name', 100)->nullable();
            $table->string('from_city', 100)->nullable();
            $table->string('to_city', 100)->nullable();
            $table->integer('qty')->default(0);
            $table->text('details')->nullable();
            $table->integer('km')->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->enum('rate_type', ['Fixed', 'PerKM'])->default('PerKM');
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('advance', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('pdf_path')->nullable();
            $table->timestamps();
            
            $table->index('bilty_no');
            $table->index('company_id');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignments');
    }
};
