<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CUM_customer_customer_type', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained('CUM_customers')->cascadeOnDelete();
            // A type cannot be deleted while a customer still points at it, not even permanently,
            // which is what this foreign key enforces without any code of ours having to run.
            $table->foreignId('customer_type_id')->constrained('CUM_customer_types')->restrictOnDelete();
            $table->timestamps();

            $table->primary(['customer_id', 'customer_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CUM_customer_customer_type');
    }
};
