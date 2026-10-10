<?php

use Illuminate\Support\Facades\Route;
use Customers13\Http\Controllers\CustomerController;
use Customers13\Http\Controllers\CustomerTrashController;
use Customers13\Http\Controllers\CustomerArchivedController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('customers/archived', [CustomerArchivedController::class, 'index'])->name('customers.archived.index');
    Route::patch('customers/archived/{customer}', [CustomerArchivedController::class, 'activate'])->whereNumber('customer')->name('customers.archived.activate');
    Route::patch('customers/{customer}/archive', [CustomerArchivedController::class, 'archive'])->whereNumber('customer')->name('customers.archive');
    Route::get('customers/trash', [CustomerTrashController::class, 'index'])->name('customers.trash.index');
    Route::patch('customers/trash/{customer}', [CustomerTrashController::class, 'restore'])->whereNumber('customer')->name('customers.trash.restore');
    Route::delete('customers/trash/{customer}', [CustomerTrashController::class, 'destroy'])->whereNumber('customer')->name('customers.trash.destroy');
    Route::get('customers/options', [CustomerController::class, 'selectOptions'])->name('customers.options');
    Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);
});
