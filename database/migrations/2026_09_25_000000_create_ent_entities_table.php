<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ENT_entities', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            addCommonColumns($table);
            addAuditColumns($table);
        });

        addUniqueActiveNameIndex('ENT_entities');
    }

    public function down(): void
    {
        Schema::dropIfExists('ENT_entities');
    }
};
