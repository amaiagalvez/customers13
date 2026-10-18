<?php

namespace Customers13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Customers13\Transformers\CustomerListTransformer;

final class CustomerListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('dashboard', fn () => null)->name('dashboard');
        Route::get('customers', fn () => null)->name('customers.index');
        Route::get('customers/archived', fn () => null)->name('customers.archived.index');
        Route::get('customers/trash', fn () => null)->name('customers.trash.index');
        Route::delete('customers/{record}', fn () => null)->name('customers.destroy');
        Route::patch('customers/{record}/archive', fn () => null)->name('customers.archive');
        Route::patch('customers/archived/{record}', fn () => null)->name('customers.archived.activate');
        Route::patch('customers/trash/{record}', fn () => null)->name('customers.trash.restore');
        Route::delete('customers/trash/{record}', fn () => null)->name('customers.trash.destroy');
    }

    public function test_active_list_without_integration_allows_deleting_customer(): void
    {
        $transformer = new CustomerListTransformer;

        $result = $transformer->active($this->records(), '');
        $actions = $result['rows'][0]['actions'];

        $this->assertSame('confirm-modal', $actions[1]['type']);
        $this->assertSame('customer-delete-1', $actions[1]['test']);
    }

    public function test_trash_list_without_integration_allows_permanent_deletion(): void
    {
        $transformer = new CustomerListTransformer;
        $result = $transformer->trash($this->records(), '');
        $actions = $result['rows'][0]['actions'];

        $this->assertSame(['customer-restore-1', 'customer-force-delete-1'], array_column($actions, 'test'));
    }

    public function test_trash_list_shows_the_hint_when_permanent_deletion_is_blocked(): void
    {
        $transformer = new BlockingCustomerListTransformer;
        $result = $transformer->trash($this->records(), '');
        $blockedAction = $result['rows'][0]['actions'][1];

        $this->assertSame('blocked', $blockedAction['type']);
        $this->assertSame('The record is referenced by other data.', $blockedAction['hint']);
    }

    public function test_trash_list_uses_an_empty_hint_when_no_force_delete_hint_is_defined(): void
    {
        $transformer = new BlockingForceDeleteCustomerListTransformer;
        $result = $transformer->trash($this->records(), '');
        $blockedAction = $result['rows'][0]['actions'][1];

        $this->assertSame('blocked', $blockedAction['type']);
        $this->assertSame('', $blockedAction['hint']);
    }

    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    private function records(): LengthAwarePaginator
    {
        $record = Customer::factory()->make(['id' => 1]);
        $record->setRelation('customerTypes', collect());

        return new LengthAwarePaginator([$record], 1, 15);
    }
}

final class BlockingCustomerListTransformer extends CustomerListTransformer
{
    protected function getDeleteBlockCondition(Model $record): bool
    {
        return true;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return true;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return 'The record is referenced by other data.';
    }
}

final class BlockingForceDeleteCustomerListTransformer extends CustomerListTransformer
{
    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return true;
    }
}
