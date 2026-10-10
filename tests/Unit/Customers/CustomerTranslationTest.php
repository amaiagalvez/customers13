<?php

namespace Customers13\Tests\Unit\Customers;

use PHPUnit\Framework\TestCase;

class CustomerTranslationTest extends TestCase
{
    public function test_customer_translation_keys_exist(): void
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
        $this->assertStringContainsString('Create a new customer', $en);
        $this->assertStringContainsString('Create a new customer', $es);
        $this->assertStringContainsString('Create a new customer', $fr);
        $this->assertStringContainsString('Create a new customer', $eu);
    }
}
