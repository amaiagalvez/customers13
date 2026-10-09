<?php

use Basics13\Support\Database\Helpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CUM_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            Helpers::addCommonColumns($table);
            Helpers::addUniqueActiveNameIndex('CUM_customers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CUM_customers');
    }
};
