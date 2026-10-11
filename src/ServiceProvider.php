<?php

namespace Customers13;

use Customers13\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'customers13');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'customers13');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang');

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
