<?php

namespace Entities13\Tests\Unit;

use Basics13\Tests\PackageIsolationTestCase;

/**
 * entities13 must work without projects13 or the bridge installed.
 */
class PackageIsolationTest extends PackageIsolationTestCase
{
    protected function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    protected function forbiddenInProductionCode(): array
    {
        return [
            'App\\',
            'EntityProjects13',
            'Projects13',
            'PRO_projects',
            'PRO_epics',
            'PRO_epic_comments',
            "route('projects.",
            "to_route('projects.",
            "'projects.index'",
            "'projects.store'",
            "'projects.update'",
            "'projects.destroy'",
            "'projects.archive'",
            "'projects.archived",
            "'projects.trash",
            "'projects.options'",
            'projects_exists',
            'entity_projects',
        ];
    }

    protected function forbiddenInTests(): array
    {
        return [
            'Projects13\\',
            'EntityProjects13\\',
        ];
    }

    protected function isolationAdvice(): string
    {
        return 'entities13 must work without projects13 installed, but its production code still '
            .'names it. Move whatever needs a project into the integration package, and keep the '
            .'contract in Entities13\\Contracts\\EntityIntegration as the only seam.';
    }
}
