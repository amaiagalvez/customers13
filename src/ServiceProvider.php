<?php

namespace Customers13;

use Customers13\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Customers13\Http\Controllers\CustomerController;
use Customers13\Http\Controllers\CustomerTrashController;
use Customers13\Http\Controllers\CustomerArchivedController;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

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

        $this->blockDeletionWithChildren();
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

    /**
     * A customer cannot reach the trash while it has projects; trashed projects
     * count too. The row is locked inside the guard's own transaction, so a
     * project that appears between the check and the delete makes the delete wait
     * and then fail.
     *
     * The hook is what makes this safe, not the caller: it holds the guarantee for
     * every path that soft deletes a customer, including a plain `$model->delete()`
     * outside a controller.
     */
    protected function blockDeletionWithChildren(): void
    {
        Customer::deleting(function (Customer $customer): bool {
            $locked = DB::transaction(static fn (): Customer => Customer::withTrashed()
                ->whereKey($customer->getKey())
                ->lockForUpdate()
                ->firstOrFail());

            return ! $locked->projects()->withTrashed()->exists();
        });
    }
}
