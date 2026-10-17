<?php

namespace Customers13;

use Customers13\Contracts\CustomerIntegration;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Default implementation of CustomerIntegration with empty implementations.
 * Used when no integration package is installed.
 */
final class DefaultCustomerIntegration extends CustomerIntegration {}

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'customers13');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'customers13');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang');
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');

        $this->publishes([
            __DIR__.'/../config/customers13.php' => config_path('customers13.php'),
        ], 'customers13-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/customers13'),
        ], 'customers13-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/customers13'),
        ], 'customers13-lang');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/customers13.php', 'customers13');

        // Bound only if nobody else has: an integration package registers after this one and
        // replaces it, and doing so does not depend on Composer's provider order being right.
        $this->app->singletonIf(CustomerIntegration::class, DefaultCustomerIntegration::class);
    }
}
