<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ENT_person_person_type', function (Blueprint $table) {
            $table->foreignId('person_id')->constrained('ENT_persons')->cascadeOnDelete();
            // A type cannot be deleted while a person still points at it, not even permanently,
            // which is what this foreign key enforces without any code of ours having to run.
            $table->foreignId('person_type_id')->constrained('ENT_person_types')->restrictOnDelete();
            $table->timestamps();

            $table->primary(['person_id', 'person_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ENT_person_person_type');
    }
};
