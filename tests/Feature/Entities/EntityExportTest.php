<?php

namespace Entities13\Tests\Feature\Entities;

use App\Models\User;
use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The export downloads the same rows the list shows: the scope, the search and the advanced
 * filters reach it through the list query, so the file never drifts from the page.
 */
class EntityExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ])));
    }

    public function test_the_export_holds_the_active_entities_matching_the_search(): void
    {
        Entity::factory()->create(['name' => 'Northern supplier']);
        Entity::factory()->create(['name' => 'Northern partner']);
        Entity::factory()->create(['name' => 'Southern supplier']);
        Entity::factory()->create(['name' => 'Northern archived', 'active' => false]);

        $this->assertEqualsCanonicalizing(
            ['Northern supplier', 'Northern partner'],
            $this->exportedNames(route('entities.export', ['search' => 'Northern'])),
        );
    }

    public function test_the_export_holds_the_entities_matching_an_advanced_filter(): void
    {
        Entity::factory()->create(['name' => 'Alderi eta bazkide']);
        Entity::factory()->create(['name' => 'Beste bat']);

        $this->assertSame(
            ['Alderi eta bazkide'],
            $this->exportedNames(route('entities.export', [
                'filters' => ['name' => ['operator' => 'contains', 'value' => 'bazkide']],
            ])),
        );
    }

    public function test_the_export_follows_the_archived_and_trash_scopes(): void
    {
        Entity::factory()->create(['name' => 'Active alpha']);
        Entity::factory()->create(['name' => 'Archived alpha', 'active' => false]);
        Entity::factory()->trashed()->create(['name' => 'Trashed alpha']);

        $this->assertSame(['Active alpha'], $this->exportedNames(route('entities.export')));
        $this->assertSame(['Archived alpha'], $this->exportedNames(route('entities.export', ['scope' => 'archived'])));
        $this->assertSame(['Trashed alpha'], $this->exportedNames(route('entities.export', ['scope' => 'trashed'])));
    }

    public function test_the_file_name_uses_the_current_language(): void
    {
        app()->setLocale('eu');
        Entity::factory()->create(['name' => 'Alpha']);

        $response = $this->get(route('entities.export'));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString('bezeroak_', (string) $response->headers->get('content-disposition'));
    }

    public function test_the_file_name_marks_the_archived_and_trash_scopes(): void
    {
        app()->setLocale('eu');
        Entity::factory()->create(['name' => 'Archived alpha', 'active' => false]);
        Entity::factory()->trashed()->create(['name' => 'Trashed alpha']);

        $archived = $this->get(route('entities.export', ['scope' => 'archived']));
        $archived->assertOk();
        $this->assertStringContainsString('bezeroak_artxibatuak_', (string) $archived->headers->get('content-disposition'));

        $trashed = $this->get(route('entities.export', ['scope' => 'trashed']));
        $trashed->assertOk();
        $this->assertStringContainsString('bezeroak_zakarrontzia_', (string) $trashed->headers->get('content-disposition'));
    }

    public function test_the_archived_and_trash_lists_export_their_own_scope(): void
    {
        Entity::factory()->create(['name' => 'Archived alpha', 'active' => false]);
        Entity::factory()->trashed()->create(['name' => 'Trashed alpha']);

        $this->get(route('entities.archived.index'))
            ->assertOk()
            ->assertSee('action="'.route('entities.export').'"', false)
            ->assertSee('name="scope" value="archived"', false);

        $this->get(route('entities.trash.index'))
            ->assertOk()
            ->assertSee('action="'.route('entities.export').'"', false)
            ->assertSee('name="scope" value="trashed"', false);
    }

    /**
     * Download the file and return the entity names, skipping the headings row.
     *
     * @return list<string>
     */
    private function exportedNames(string $url): array
    {
        $response = $this->get($url);

        $response->assertOk();
        $response->assertDownload();

        $file = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $file);

        $sheet = IOFactory::load($file->getFile()->getPathname())->getActiveSheet();
        $names = [];

        foreach (array_slice($sheet->toArray(), 1) as $row) {
            $names[] = (string) $row[1];
        }

        return $names;
    }
}
