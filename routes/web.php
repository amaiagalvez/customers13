<?php

use Illuminate\Support\Facades\Route;
use Entities13\Http\Controllers\EntityController;
use Entities13\Http\Controllers\PersonController;
use Entities13\Http\Controllers\EntityTypeController;
use Entities13\Http\Controllers\PersonTypeController;
use Entities13\Http\Controllers\EntityTrashController;
use Entities13\Http\Controllers\PersonTrashController;
use Entities13\Http\Controllers\EntityArchivedController;
use Entities13\Http\Controllers\PersonArchivedController;
use Entities13\Http\Controllers\EntityTypeTrashController;
use Entities13\Http\Controllers\PersonTypeTrashController;
use Entities13\Http\Controllers\EntityTypeArchivedController;
use Entities13\Http\Controllers\PersonTypeArchivedController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('entities/archived', [EntityArchivedController::class, 'index'])->name('entities.archived.index');
    Route::patch('entities/archived/{entity}', [EntityArchivedController::class, 'activate'])->whereNumber('entity')->name('entities.archived.activate');
    Route::patch('entities/{entity}/archive', [EntityArchivedController::class, 'archive'])->whereNumber('entity')->name('entities.archive');
    Route::get('entities/trash', [EntityTrashController::class, 'index'])->name('entities.trash.index');
    Route::patch('entities/trash/{entity}', [EntityTrashController::class, 'restore'])->whereNumber('entity')->name('entities.trash.restore');
    Route::delete('entities/trash/{entity}', [EntityTrashController::class, 'destroy'])->whereNumber('entity')->name('entities.trash.destroy');
    Route::get('entities/options', [EntityController::class, 'selectOptions'])->name('entities.options');
    Route::get('entities/export', [EntityController::class, 'export'])->name('entities.export');
    Route::resource('entities', EntityController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('entity-types/archived', [EntityTypeArchivedController::class, 'index'])->name('entity-types.archived.index');
    Route::patch('entity-types/archived/{entity_type}', [EntityTypeArchivedController::class, 'activate'])->whereNumber('entity_type')->name('entity-types.archived.activate');
    Route::patch('entity-types/{entity_type}/archive', [EntityTypeArchivedController::class, 'archive'])->whereNumber('entity_type')->name('entity-types.archive');
    Route::get('entity-types/trash', [EntityTypeTrashController::class, 'index'])->name('entity-types.trash.index');
    Route::patch('entity-types/trash/{entity_type}', [EntityTypeTrashController::class, 'restore'])->whereNumber('entity_type')->name('entity-types.trash.restore');
    Route::delete('entity-types/trash/{entity_type}', [EntityTypeTrashController::class, 'destroy'])->whereNumber('entity_type')->name('entity-types.trash.destroy');
    Route::get('entity-types/options', [EntityTypeController::class, 'selectOptions'])->name('entity-types.options');
    Route::resource('entity-types', EntityTypeController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('person-types/archived', [PersonTypeArchivedController::class, 'index'])->name('person-types.archived.index');
    Route::patch('person-types/archived/{person_type}', [PersonTypeArchivedController::class, 'activate'])->whereNumber('person_type')->name('person-types.archived.activate');
    Route::patch('person-types/{person_type}/archive', [PersonTypeArchivedController::class, 'archive'])->whereNumber('person_type')->name('person-types.archive');
    Route::get('person-types/trash', [PersonTypeTrashController::class, 'index'])->name('person-types.trash.index');
    Route::patch('person-types/trash/{person_type}', [PersonTypeTrashController::class, 'restore'])->whereNumber('person_type')->name('person-types.trash.restore');
    Route::delete('person-types/trash/{person_type}', [PersonTypeTrashController::class, 'destroy'])->whereNumber('person_type')->name('person-types.trash.destroy');
    Route::get('person-types/options', [PersonTypeController::class, 'selectOptions'])->name('person-types.options');
    Route::resource('person-types', PersonTypeController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('persons/archived', [PersonArchivedController::class, 'index'])->name('persons.archived.index');
    Route::patch('persons/archived/{person}', [PersonArchivedController::class, 'activate'])->whereNumber('person')->name('persons.archived.activate');
    Route::patch('persons/{person}/archive', [PersonArchivedController::class, 'archive'])->whereNumber('person')->name('persons.archive');
    Route::get('persons/trash', [PersonTrashController::class, 'index'])->name('persons.trash.index');
    Route::patch('persons/trash/{person}', [PersonTrashController::class, 'restore'])->whereNumber('person')->name('persons.trash.restore');
    Route::delete('persons/trash/{person}', [PersonTrashController::class, 'destroy'])->whereNumber('person')->name('persons.trash.destroy');
    Route::get('persons/options', [PersonController::class, 'selectOptions'])->name('persons.options');
    Route::get('persons/export', [PersonController::class, 'export'])->name('persons.export');
    Route::resource('persons', PersonController::class)->only(['index', 'store', 'update', 'destroy']);
});
