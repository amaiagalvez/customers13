<?php

use Illuminate\Support\Facades\Route;
use Customers13\Http\Controllers\CustomerController;
use Customers13\Http\Controllers\CustomerTypeController;
use Customers13\Http\Controllers\CustomerTrashController;
use Customers13\Http\Controllers\CustomerArchivedController;
use Customers13\Http\Controllers\CustomerTypeTrashController;
use Customers13\Http\Controllers\CustomerTypeArchivedController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('customers/archived', [CustomerArchivedController::class, 'index'])->name('customers.archived.index');
    Route::patch('customers/archived/{customer}', [CustomerArchivedController::class, 'activate'])->whereNumber('customer')->name('customers.archived.activate');
    Route::patch('customers/{customer}/archive', [CustomerArchivedController::class, 'archive'])->whereNumber('customer')->name('customers.archive');
    Route::get('customers/trash', [CustomerTrashController::class, 'index'])->name('customers.trash.index');
    Route::patch('customers/trash/{customer}', [CustomerTrashController::class, 'restore'])->whereNumber('customer')->name('customers.trash.restore');
    Route::delete('customers/trash/{customer}', [CustomerTrashController::class, 'destroy'])->whereNumber('customer')->name('customers.trash.destroy');
    Route::get('customers/options', [CustomerController::class, 'selectOptions'])->name('customers.options');
    Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('customer-types/archived', [CustomerTypeArchivedController::class, 'index'])->name('customer-types.archived.index');
    Route::patch('customer-types/archived/{customer_type}', [CustomerTypeArchivedController::class, 'activate'])->whereNumber('customer_type')->name('customer-types.archived.activate');
    Route::patch('customer-types/{customer_type}/archive', [CustomerTypeArchivedController::class, 'archive'])->whereNumber('customer_type')->name('customer-types.archive');
    Route::get('customer-types/trash', [CustomerTypeTrashController::class, 'index'])->name('customer-types.trash.index');
    Route::patch('customer-types/trash/{customer_type}', [CustomerTypeTrashController::class, 'restore'])->whereNumber('customer_type')->name('customer-types.trash.restore');
    Route::delete('customer-types/trash/{customer_type}', [CustomerTypeTrashController::class, 'destroy'])->whereNumber('customer_type')->name('customer-types.trash.destroy');
    Route::get('customer-types/options', [CustomerTypeController::class, 'selectOptions'])->name('customer-types.options');
    Route::resource('customer-types', CustomerTypeController::class)->only(['index', 'store', 'update', 'destroy']);
});
