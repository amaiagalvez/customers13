<?php

namespace Entities13\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The permissions the entity, entity type, person and person type policies check and the data group of the sidebar.
 * `admin` gets full access to all modules. Runs after the basics13 access seeder, which creates
 * `admin`; running it again duplicates nothing and takes no permission away from a role.
 */
class EntitiesAccessSeeder extends Seeder
{
    /**
     * Each link asks for the permission its list's policy checks.
     *
     * @var list<array{key: string, label: string, sort: int, parent?: string, icon?: string, route?: string, active?: string, except?: string, permissions?: list<string>}>
     */
    private const MENUS = [
        ['key' => 'data', 'label' => 'Data', 'sort' => 200],
        ['key' => 'entities', 'parent' => 'data', 'label' => 'Entities', 'sort' => 10, 'icon' => 'users', 'route' => 'entities.index', 'active' => 'entities.*', 'permissions' => ['entities.view']],
        ['key' => 'entity-types', 'parent' => 'data', 'label' => 'Entity types', 'sort' => 20, 'icon' => 'tag', 'route' => 'entity-types.index', 'active' => 'entity-types.*', 'permissions' => ['entity_types.view']],
        ['key' => 'persons', 'parent' => 'data', 'label' => 'Persons', 'sort' => 30, 'icon' => 'user', 'route' => 'persons.index', 'active' => 'persons.*', 'permissions' => ['persons.view']],
        ['key' => 'person-types', 'parent' => 'data', 'label' => 'Person types', 'sort' => 40, 'icon' => 'tag', 'route' => 'person-types.index', 'active' => 'person-types.*', 'permissions' => ['person_types.view']],
    ];

    public function run(): void
    {
        seedPermissions(
            ['entities', 'entity_types', 'persons', 'person_types'],
            ['entities.manage', 'entity_types.manage', 'persons.manage', 'person_types.manage']
        );
        seedMenus('entities13', self::MENUS);
    }
}
