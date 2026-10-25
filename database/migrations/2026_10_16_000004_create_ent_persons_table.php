<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ENT_persons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name_1')->nullable();
            $table->string('last_name_2')->nullable();
            $table->string('document_number')->nullable();
            $table->string('lang', 2)->nullable(); // eu|es|fr|en
            $table->foreignId('entity_id')->nullable()->constrained('ENT_entities')->restrictOnDelete();

            addCommonColumns($table);
            addAuditColumns($table);
        });

        // No unique index on name: two persons may share the same name.
    }

    public function down(): void
    {
        Schema::dropIfExists('ENT_persons');
    }
};
