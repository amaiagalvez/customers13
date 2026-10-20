<?php

namespace Entities13\Tests\Unit\Entities;

use PHPUnit\Framework\TestCase;

class EntityTranslationTest extends TestCase
{
    public function test_entity_translation_keys_exist(): void
    {
        $langPath = dirname(__DIR__, 3).'/resources/lang';
        $this->assertFileExists($langPath.'/en/messages.php');
        $this->assertFileExists($langPath.'/es/messages.php');
        $this->assertFileExists($langPath.'/fr/messages.php');
        $this->assertFileExists($langPath.'/eu/messages.php');

        $en = file_get_contents($langPath.'/en/messages.php');
        $es = file_get_contents($langPath.'/es/messages.php');
        $fr = file_get_contents($langPath.'/fr/messages.php');
        $eu = file_get_contents($langPath.'/eu/messages.php');

        $this->assertIsString($en);
        $this->assertIsString($es);
        $this->assertIsString($fr);
        $this->assertIsString($eu);
        $this->assertStringContainsString('Create a new entity', $en);
        $this->assertStringContainsString('Create a new entity', $es);
        $this->assertStringContainsString('Create a new entity', $fr);
        $this->assertStringContainsString('Create a new entity', $eu);
    }
}
