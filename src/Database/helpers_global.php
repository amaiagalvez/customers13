<?php

if (! function_exists('addCommonColumns')) {
    /**
     * Add the columns shared by the application's soft-deleting tables.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     * @return void
     */
    function addCommonColumns($table): void
    {
        $table->longText('notes')->nullable();
        $table->boolean('active')->default(true);
        $table->timestamps();
        $table->softDeletes();

        $table->index(['deleted_at', 'id']);
    }
}

if (! function_exists('addUniqueActiveNameIndex')) {
    /**
     * Make a table's name unique among the rows that are not soft-deleted.
     *
     * @param  string  $table
     * @param  array  $scope
     * @return void
     */
    function addUniqueActiveNameIndex(string $table, array $scope = []): void
    {
        $index = implode('_', array_merge([$table], $scope, ['active_name', 'unique']));

        if (in_array(DB::connection()->getDriverName(), ['sqlite', 'pgsql'], true)) {
            DB::statement(sprintf(
                'CREATE UNIQUE INDEX %s ON %s (%s) WHERE deleted_at IS NULL',
                $index,
                $table,
                implode(', ', array_merge([...$scope, 'name']))
            ));

            return;
        }

        Schema::table($table, function ($blueprint) use ($index, $scope): void {
            $blueprint->string('active_name')->nullable()->storedAs('IF(deleted_at IS NULL, name, NULL)');
            $blueprint->unique([...$scope, 'active_name'], $index);
        });
    }
}