<?php

use Illuminate\Support\Facades\Route;
use entities13\Http\Controllers\EntityController;
use entities13\Http\Controllers\EntityTypeController;
use entities13\Http\Controllers\EntityTrashController;
use entities13\Http\Controllers\EntityArchivedController;
use entities13\Http\Controllers\EntityTypeTrashController;
use entities13\Http\Controllers\EntityTypeArchivedController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('entities/archived', [EntityArchivedController::class, 'index'])->name('entities.archived.index');
    Route::patch('entities/archived/{entity}', [EntityArchivedController::class, 'activate'])->whereNumber('entity')->name('entities.archived.activate');
    Route::patch('entities/{entity}/archive', [EntityArchivedController::class, 'archive'])->whereNumber('entity')->name('entities.archive');
    Route::get('entities/trash', [EntityTrashController::class, 'index'])->name('entities.trash.index');
    Route::patch('entities/trash/{entity}', [EntityTrashController::class, 'restore'])->whereNumber('entity')->name('entities.trash.restore');
    Route::delete('entities/trash/{entity}', [EntityTrashController::class, 'destroy'])->whereNumber('entity')->name('entities.trash.destroy');
    Route::get('entities/options', [EntityController::class, 'selectOptions'])->name('entities.options');
    Route::resource('entities', EntityController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('entity-types/archived', [EntityTypeArchivedController::class, 'index'])->name('entity-types.archived.index');
    Route::patch('entity-types/archived/{entity_type}', [EntityTypeArchivedController::class, 'activate'])->whereNumber('entity_type')->name('entity-types.archived.activate');
    Route::patch('entity-types/{entity_type}/archive', [EntityTypeArchivedController::class, 'archive'])->whereNumber('entity_type')->name('entity-types.archive');
    Route::get('entity-types/trash', [EntityTypeTrashController::class, 'index'])->name('entity-types.trash.index');
    Route::patch('entity-types/trash/{entity_type}', [EntityTypeTrashController::class, 'restore'])->whereNumber('entity_type')->name('entity-types.trash.restore');
    Route::delete('entity-types/trash/{entity_type}', [EntityTypeTrashController::class, 'destroy'])->whereNumber('entity_type')->name('entity-types.trash.destroy');
    Route::get('entity-types/options', [EntityTypeController::class, 'selectOptions'])->name('entity-types.options');
    Route::resource('entity-types', EntityTypeController::class)->only(['index', 'store', 'update', 'destroy']);
});
