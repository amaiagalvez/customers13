<?php

use Illuminate\Database\Migrations\Migration;

/**
 * The data group of the sidebar: each link asks for the permission its list's policy checks.
 */
return new class extends Migration
{
    public function up(): void
    {
        seedMenus('entities13', [
            ['key' => 'data', 'label' => 'Data', 'sort' => 200],
            ['key' => 'entities', 'parent' => 'data', 'label' => 'Entities', 'sort' => 10, 'icon' => 'users', 'route' => 'entities.index', 'active' => 'entities.*', 'permissions' => ['entities.view']],
            ['key' => 'entity-types', 'parent' => 'data', 'label' => 'Entity types', 'sort' => 20, 'icon' => 'tag', 'route' => 'entity-types.index', 'active' => 'entity-types.*', 'permissions' => ['entity_types.view']],
        ]);
    }

    public function down(): void
    {
        removeMenus('entities13');
    }
};
