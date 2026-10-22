<?php

namespace Entities13\Tests\Unit;

use Generator;
use SplFileInfo;
use RecursiveIteratorIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;

/**
 * The package has to be usable without `projects13` installed, and the only thing that proves it
 * is the absence of every reference to it. Composer's lockfile does not prove anything here: the
 * host resolves a single dependency graph for all of them, so a reference that only fails at
 * runtime still installs cleanly.
 */
class PackageIsolationTest extends TestCase
{
    /**
     * Directories whose contents are production code. Tests, fixtures and vendor are excluded on
     * purpose: a test is allowed to know what the integration adds.
     *
     * @var list<string>
     */
    private const PRODUCTION_DIRECTORIES = ['src', 'resources', 'routes', 'config'];

    /**
     * @var array<non-empty-string, string>
     */
    private const FORBIDDEN = [
        'App\\' => 'App\\',
        'EntityProjects13' => 'EntityProjects13',
        'Projects13' => 'Projects13',
        'PRO_projects' => 'PRO_projects',
        'PRO_epics' => 'PRO_epics',
        'PRO_epic_comments' => 'PRO_epic_comments',
        "route('projects." => "route('projects.",
        "to_route('projects." => "to_route('projects.",
        "'projects.index'" => "'projects.index'",
        "'projects.store'" => "'projects.store'",
        "'projects.update'" => "'projects.update'",
        "'projects.destroy'" => "'projects.destroy'",
        "'projects.archive'" => "'projects.archive'",
        "'projects.archived" => "'projects.archived",
        "'projects.trash" => "'projects.trash",
        "'projects.options'" => "'projects.options'",
        // Payload keys and join aliases are coupling too, not only imports and routes.
        'projects_exists' => 'projects_exists',
        'entity_projects' => 'entity_projects',
    ];

    public function test_production_code_never_references_the_projects_package(): void
    {
        $offences = [];

        foreach ($this->productionFiles() as $file) {
            foreach ($this->offencesIn($file) as $offence) {
                $offences[] = $offence;
            }
        }

        $this->assertSame(
            [],
            $offences,
            'entities13 must work without projects13 installed, but its production code still '
                .'names it. Move whatever needs a project into the integration package, and keep the '
                .'contract in Entities13\\Contracts\\EntityIntegration as the only seam.'.PHP_EOL
                .PHP_EOL.implode(PHP_EOL, $offences),
        );
    }

    public function test_the_isolation_test_scans_something(): void
    {
        // A scan that silently stops finding files would make the assertion above pass forever.
        $this->assertGreaterThan(20, iterator_count($this->productionFiles()));
    }

    public function test_the_package_tests_do_not_require_projects_or_the_bridge(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(dirname(__DIR__).'/'),
        );

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php' || $file->getPathname() === __FILE__) {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            $this->assertIsString($source);
            $this->assertStringNotContainsString('Projects13\\', $source, $file->getPathname());
            $this->assertStringNotContainsString('EntityProjects13\\', $source, $file->getPathname());
        }
    }

    /**
     * @return Generator<int, SplFileInfo>
     */
    private function productionFiles(): iterable
    {
        $root = dirname(__DIR__, 2);

        foreach (self::PRODUCTION_DIRECTORIES as $directory) {
            $path = $root.'/'.$directory;

            if (! is_dir($path)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

            foreach ($files as $file) {
                if ($file->isFile() && in_array($file->getExtension(), ['php', 'blade.php', 'js', 'json'], true)) {
                    yield $file;
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function offencesIn(SplFileInfo $file): array
    {
        $contents = (string) file_get_contents($file->getPathname());
        $relative = str_replace(dirname(__DIR__, 2).'/', '', $file->getPathname());
        $offences = [];

        foreach (self::FORBIDDEN as $needle) {
            $line = $this->lineWith($contents, $needle);

            if ($line !== null) {
                $offences[] = sprintf('%s:%d references "%s"', $relative, $line, $needle);
            }
        }

        return $offences;
    }

    private function lineWith(string $contents, string $needle): ?int
    {
        $position = strpos($contents, $needle);

        return $position === false
            ? null
            : substr_count($contents, PHP_EOL, 0, $position) + 1;
    }
}
