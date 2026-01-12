<?php

use App\Http\Controllers\Admin\RHCatalogoController;
use App\Http\Controllers\Api\CatalogFilterController;
use Illuminate\Support\Facades\Route;

Route::post('/filters', [CatalogFilterController::class, 'options']);

Route::prefix('admin/rhcatalogo')->group(function () {
    Route::get('stats', [RHCatalogoController::class, 'stats']);
    Route::post('enqueue', [RHCatalogoController::class, 'enqueue']);
    Route::post('retry/{product}', [RHCatalogoController::class, 'retry']);
    Route::get('items', [RHCatalogoController::class, 'items']);
    Route::get('items/{product}', [RHCatalogoController::class, 'show']);
    Route::get('events', [RHCatalogoController::class, 'events']);
});
