<?php

namespace entities13\Tests\Unit\EntityTypes;

use PHPUnit\Framework\TestCase;

class EntityTypeTranslationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const LOCALES = ['en', 'es', 'eu', 'fr'];

    /**
     * The copy the entity type resource and the type column of the entity list show.
     *
     * @var list<string>
     */
    private const REQUIRED_KEYS = [
        'Entity types',
        'New entity type',
        'Edit entity type',
        'Save entity type',
        'Update entity type',
        'No entity types yet.',
        'Name (eu)',
        'Name (es)',
        'Name (fr)',
        'Name (en)',
        'Types',
        'Hold Ctrl or Cmd to pick more than one type.',
    ];

    public function test_the_resource_copy_exists_in_every_locale(): void
    {
        foreach (self::LOCALES as $locale) {
            $lines = $this->lines($locale);

            foreach (self::REQUIRED_KEYS as $key) {
                $this->assertArrayHasKey($key, $lines, "Missing [{$key}] in the {$locale} translations.");
                $this->assertNotSame('', $lines[$key], "Empty [{$key}] in the {$locale} translations.");
            }
        }
    }

    public function test_every_locale_defines_the_same_keys(): void
    {
        $reference = $this->lines('en');

        foreach (self::LOCALES as $locale) {
            $this->assertSame(
                array_keys($reference),
                array_keys($this->lines($locale)),
                "The {$locale} translations do not match the English key set.",
            );
        }
    }

    public function test_the_copy_is_actually_translated(): void
    {
        $this->assertSame('Tipos de cliente', $this->lines('es')['Entity types']);
        $this->assertSame('Bezero motak', $this->lines('eu')['Entity types']);
        $this->assertSame('Types de client', $this->lines('fr')['Entity types']);
    }

    /**
     * @return array<string, string>
     */
    private function lines(string $locale): array
    {
        $file = dirname(__DIR__, 3) . '/resources/lang/' . $locale . '.json';
        $this->assertFileExists($file);

        $decoded = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            $this->fail("The {$locale} translations are not a JSON object.");
        }

        /** @var array<string, string> $decoded */
        return $decoded;
    }
}
