<?php

use App\Http\Controllers\CamisetaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ContactoEmpresaController;
use App\Http\Controllers\ContactoPersonalController;
use App\Http\Controllers\TallaController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes – TodoCamisetas
|--------------------------------------------------------------------------
| Base URL: http://localhost:8000/api
*/

// ── CAMISETAS ─────────────────────────────────────────────────────────────────
Route::prefix('camisetas')->group(function () {
    Route::get('/',                          [CamisetaController::class, 'index']);
    Route::post('/',                         [CamisetaController::class, 'store']);
    Route::get('/{id}',                      [CamisetaController::class, 'show']);
    Route::put('/{id}',                      [CamisetaController::class, 'update']);
    Route::patch('/{id}',                    [CamisetaController::class, 'update']);
    Route::delete('/{id}',                   [CamisetaController::class, 'destroy']);
    Route::get('/{id}/precio',               [CamisetaController::class, 'precioFinal']); // ?cliente_id=
    Route::post('/{camiseta_id}/tallas',     [TallaController::class,    'asociarACamiseta']);
});

// ── CLIENTES ──────────────────────────────────────────────────────────────────
Route::prefix('clientes')->group(function () {
    Route::get('/',        [ClienteController::class, 'index']);
    Route::post('/',       [ClienteController::class, 'store']);
    Route::get('/{id}',    [ClienteController::class, 'show']);
    Route::put('/{id}',    [ClienteController::class, 'update']);
    Route::patch('/{id}',  [ClienteController::class, 'patch']);
    Route::delete('/{id}', [ClienteController::class, 'destroy']);

    // Camisetas del cliente con precio_final
    Route::get('/{cliente_id}/camisetas', [CamisetaController::class, 'porCliente']);

    // Contactos de empresa del cliente
    Route::get   ('/{clienteId}/contactos-empresa',       [ContactoEmpresaController::class, 'index']);
    Route::post  ('/{clienteId}/contactos-empresa',       [ContactoEmpresaController::class, 'store']);
    Route::get   ('/{clienteId}/contactos-empresa/{id}',  [ContactoEmpresaController::class, 'show']);
    Route::put   ('/{clienteId}/contactos-empresa/{id}',  [ContactoEmpresaController::class, 'update']);
    Route::patch ('/{clienteId}/contactos-empresa/{id}',  [ContactoEmpresaController::class, 'patch']);
    Route::delete('/{clienteId}/contactos-empresa/{id}',  [ContactoEmpresaController::class, 'destroy']);

    // Contactos personales del cliente
    Route::get   ('/{clienteId}/contactos-personal',      [ContactoPersonalController::class, 'index']);
    Route::post  ('/{clienteId}/contactos-personal',      [ContactoPersonalController::class, 'store']);
    Route::get   ('/{clienteId}/contactos-personal/{id}', [ContactoPersonalController::class, 'show']);
    Route::put   ('/{clienteId}/contactos-personal/{id}', [ContactoPersonalController::class, 'update']);
    Route::patch ('/{clienteId}/contactos-personal/{id}', [ContactoPersonalController::class, 'patch']);
    Route::delete('/{clienteId}/contactos-personal/{id}', [ContactoPersonalController::class, 'destroy']);
});

// ── TALLAS ────────────────────────────────────────────────────────────────────
Route::prefix('tallas')->group(function () {
    Route::get('/',        [TallaController::class, 'index']);
    Route::post('/',       [TallaController::class, 'store']);
    Route::get('/{id}',    [TallaController::class, 'show']);
    Route::put('/{id}',    [TallaController::class, 'update']);
    Route::patch('/{id}',  [TallaController::class, 'update']);
    Route::delete('/{id}', [TallaController::class, 'destroy']);
});

// ── VENTAS ────────────────────────────────────────────────────────────────────
Route::prefix('ventas')->group(function () {
    Route::get('/estadisticas',  [VentaController::class, 'estadisticas']); // ANTES de /{id}
    Route::get('/',              [VentaController::class, 'index']);
    Route::post('/',             [VentaController::class, 'store']);
    Route::get('/{id}',          [VentaController::class, 'show']);
    Route::patch('/{id}',        [VentaController::class, 'patch']);
    Route::delete('/{id}',       [VentaController::class, 'destroy']);
});
