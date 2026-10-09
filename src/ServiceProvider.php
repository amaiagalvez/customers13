<?php

namespace Customers13;

use Customers13\Http\Controllers\CustomerArchivedController;
use Customers13\Http\Controllers\CustomerController;
use Customers13\Http\Controllers\CustomerTrashController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'customers13');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'customers13');

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
        //
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['auth', 'verified'])->group(function () {
            Route::get('customers/archived', [CustomerArchivedController::class, 'index'])->name('customers.archived.index');
            Route::patch('customers/archived/{customer}', [CustomerArchivedController::class, 'activate'])->whereNumber('customer')->name('customers.archived.activate');
            Route::patch('customers/{customer}/archive', [CustomerArchivedController::class, 'archive'])->whereNumber('customer')->name('customers.archive');
            Route::get('customers/trash', [CustomerTrashController::class, 'index'])->name('customers.trash.index');
            Route::patch('customers/trash/{customer}', [CustomerTrashController::class, 'restore'])->whereNumber('customer')->name('customers.trash.restore');
            Route::delete('customers/trash/{customer}', [CustomerTrashController::class, 'destroy'])->whereNumber('customer')->name('customers.trash.destroy');
            Route::get('customers/options', [CustomerController::class, 'selectOptions'])->name('customers.options');
            Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);
        });
    }
}
