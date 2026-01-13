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
        Schema::create('vehicle_maintenance', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('vehicle_no', 50);
            $table->string('expense_type', 100);
            $table->decimal('amount', 10, 2)->default(0);
            $table->text('narration')->nullable();
            $table->timestamps();
            
            $table->index('entry_date');
            $table->index('vehicle_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance');
    }
};
