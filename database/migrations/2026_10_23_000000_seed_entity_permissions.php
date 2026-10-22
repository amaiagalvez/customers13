<?php

use Illuminate\Database\Migrations\Migration;

/**
 * The permissions the entity and entity type policies check. `admin` gets full access to both
 * modules, so the accounts that held it before keep managing entities and types.
 */
return new class extends Migration
{
    public function up(): void
    {
        seedPermissions(['entities', 'entity_types'], ['entities.manage', 'entity_types.manage']);
    }

    public function down(): void
    {
        // The permission tables are dropped by the basics13 migrations that created them.
    }
};
