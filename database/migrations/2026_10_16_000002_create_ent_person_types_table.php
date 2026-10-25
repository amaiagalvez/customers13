<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The languages every person type is named in. Euskera is the language a type always has, so
     * its column is the only one that is not nullable.
     *
     * @var list<string>
     */
    private const NAME_COLUMNS = ['name_eu', 'name_es', 'name_fr', 'name_en'];

    public function up(): void
    {
        Schema::create('ENT_person_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_eu');
            $table->string('name_es')->nullable();
            $table->string('name_fr')->nullable();
            $table->string('name_en')->nullable();

            addCommonColumns($table);
            addAuditColumns($table);
        });

        // One unique index per language: a name has to be unique inside its own language and mean
        // nothing in the others, so a type may share a Spanish name with another one as long as
        // their euskera names differ.
        foreach (self::NAME_COLUMNS as $column) {
            addUniqueActiveColumnIndex('ENT_person_types', $column);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ENT_person_types');
    }
};
