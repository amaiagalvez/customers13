<?php

namespace Customers13\Tests\Unit;

use Tests\TestCase;

class CustomerTranslationTest extends TestCase
{
    public function test_customer_translation_keys_exist(): void
    {
        $this->assertFileExists(resource_path('lang/en/messages.php'));
        $this->assertFileExists(resource_path('lang/es/messages.php'));
        $this->assertFileExists(resource_path('lang/fr/messages.php'));
        $this->assertFileExists(resource_path('lang/eu/messages.php'));

        $en = file_get_contents(resource_path('lang/en/messages.php'));
        $es = file_get_contents(resource_path('lang/es/messages.php'));
        $fr = file_get_contents(resource_path('lang/fr/messages.php'));
        $eu = file_get_contents(resource_path('lang/eu/messages.php'));

        $this->assertStringContainsString('Create a new customer', $en);
        $this->assertStringContainsString('Create a new customer', $es);
        $this->assertStringContainsString('Create a new customer', $fr);
        $this->assertStringContainsString('Create a new customer', $eu);
    }
}
