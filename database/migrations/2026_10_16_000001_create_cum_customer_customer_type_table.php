<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CUM_entity_entity_type', function (Blueprint $table) {
            $table->foreignId('entity_id')->constrained('CUM_entities')->cascadeOnDelete();
            // A type cannot be deleted while a entity still points at it, not even permanently,
            // which is what this foreign key enforces without any code of ours having to run.
            $table->foreignId('entity_type_id')->constrained('CUM_entity_types')->restrictOnDelete();
            $table->timestamps();

            $table->primary(['entity_id', 'entity_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CUM_entity_entity_type');
    }
};
