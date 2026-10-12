<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\MovimientoInventarioController;

// Página principal: Inventario y Existencias
Route::get('/', [ProductoController::class, 'index'])->name('home');
Route::get('/inventario', [ProductoController::class, 'index'])->name('inventario.index');

// Operaciones de Productos
Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
Route::put('/productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
Route::delete('/productos/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');
Route::get('/productos/{producto}/historial', [ProductoController::class, 'historial'])->name('productos.historial');

// CRUD de Entradas y Salidas de Inventario
Route::get('/movimientos', [MovimientoInventarioController::class, 'index'])->name('movimientos.index');
Route::post('/movimientos', [MovimientoInventarioController::class, 'store'])->name('movimientos.store');
Route::put('/movimientos/{movimiento}', [MovimientoInventarioController::class, 'update'])->name('movimientos.update');
Route::delete('/movimientos/{movimiento}', [MovimientoInventarioController::class, 'destroy'])->name('movimientos.destroy');
