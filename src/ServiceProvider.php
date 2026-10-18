<?php

namespace entities13;

use entities13\Contracts\EntityIntegration;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Default implementation of EntityIntegration with empty implementations.
 * Used when no integration package is installed.
 */
final class DefaultEntityIntegration extends EntityIntegration {}

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'entities13');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'entities13');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../resources/lang');

        $this->publishes([
            __DIR__ . '/../config/entities13.php' => config_path('entities13.php'),
        ], 'entities13-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/entities13'),
        ], 'entities13-views');

        $this->publishes([
            __DIR__ . '/../resources/lang' => lang_path('vendor/entities13'),
        ], 'entities13-lang');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/entities13.php', 'entities13');

        // Bound only if nobody else has: an integration package registers after this one and
        // replaces it, and doing so does not depend on Composer's provider order being right.
        $this->app->singletonIf(EntityIntegration::class, DefaultEntityIntegration::class);
    }
}
