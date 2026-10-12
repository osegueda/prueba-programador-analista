<?php

use App\Models\Producto;
use App\Models\TipoProducto;
use App\Models\InventarioProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('puede consultar la existencia de productos en el inventario', function () {
    $response = $this->get(route('inventario.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Inventario')
        ->has('productos')
        ->has('tipos')
        ->has('metrics')
    );
});

test('puede registrar un nuevo producto con su tipo descripcion y cantidad inicial', function () {
    $tipo = TipoProducto::first();

    $response = $this->post(route('productos.store'), [
        'id_tipo_producto' => $tipo->id,
        'descripcion' => 'Marcadores permanentes punta biselada',
        'cantidad_inicial' => 25,
        'stock_minimo' => 5,
    ]);

    $response->assertSessionHas('success');

    $producto = Producto::where('descripcion', 'Marcadores permanentes punta biselada')->first();
    expect($producto)->not->toBeNull();
    expect($producto->stock_actual)->toBe(25);

    // Verificar que se haya creado la entrada inicial en tbl_inventario_productos
    $movimientoInicial = InventarioProducto::where('id_producto', $producto->id)->first();
    expect($movimientoInicial)->not->toBeNull();
    expect($movimientoInicial->tipo_movimiento)->toBe('ENTRADA');
    expect($movimientoInicial->cantidad)->toBe(25);
    expect($movimientoInicial->motivo)->toBe('Inventario inicial de apertura');
});

test('puede registrar una entrada a stock incrementando existencias', function () {
    $producto = Producto::first();
    $stockPrevio = $producto->stock_actual;

    $response = $this->post(route('movimientos.store'), [
        'id_producto' => $producto->id,
        'tipo_movimiento' => 'ENTRADA',
        'cantidad' => 10,
        'fecha_ingreso' => now()->toDateTimeString(),
        'motivo' => 'Compra reposicion de almacen',
        'referencia' => 'FAC-TEST-001',
    ]);

    $response->assertSessionHas('success');

    $producto->refresh();
    expect($producto->stock_actual)->toBe($stockPrevio + 10);
});

test('puede registrar una salida de stock disminuyendo existencias', function () {
    $producto = Producto::where('stock_actual', '>', 5)->first();
    $stockPrevio = $producto->stock_actual;

    $response = $this->post(route('movimientos.store'), [
        'id_producto' => $producto->id,
        'tipo_movimiento' => 'SALIDA',
        'cantidad' => 3,
        'fecha_ingreso' => now()->toDateTimeString(),
        'motivo' => 'Entrega para uso en recepcion',
        'referencia' => 'VALE-TEST-001',
    ]);

    $response->assertSessionHas('success');

    $producto->refresh();
    expect($producto->stock_actual)->toBe($stockPrevio - 3);
});

test('rechaza una salida si la cantidad supera el stock disponible', function () {
    $producto = Producto::first();
    $stockPrevio = $producto->stock_actual;

    $response = $this->post(route('movimientos.store'), [
        'id_producto' => $producto->id,
        'tipo_movimiento' => 'SALIDA',
        'cantidad' => $stockPrevio + 9999, // Supera stock
        'fecha_ingreso' => now()->toDateTimeString(),
        'motivo' => 'Intento de salida sin stock',
    ]);

    $response->assertSessionHas('error');

    $producto->refresh();
    expect($producto->stock_actual)->toBe($stockPrevio); // Stock intacto
});

test('puede consultar el historial de movimientos de un producto con saldo', function () {
    $producto = Producto::first();

    $response = $this->get(route('productos.historial', $producto));

    $response->assertOk();
    $response->assertJsonStructure([
        'producto',
        'historial' => [
            '*' => [
                'id',
                'tipo_movimiento',
                'cantidad',
                'fecha_ingreso',
                'saldo_resultante',
            ],
        ],
        'resumen' => [
            'total_entradas',
            'total_salidas',
            'stock_actual',
            'stock_minimo',
        ],
    ]);
});

test('puede consultar el listado de movimientos en el crud', function () {
    $response = $this->get(route('movimientos.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movimientos')
        ->has('movimientos')
        ->has('productos')
        ->has('stats')
    );
});

test('puede eliminar un movimiento recalculando el stock', function () {
    $producto = Producto::first();

    // Crear un movimiento de salida
    $mov = InventarioProducto::create([
        'id_producto' => $producto->id,
        'tipo_movimiento' => 'SALIDA',
        'cantidad' => 2,
        'fecha_ingreso' => now(),
        'motivo' => 'Salida temporal de prueba',
    ]);
    $producto->recalcularStock();
    $stockConSalida = $producto->stock_actual;

    $response = $this->delete(route('movimientos.destroy', $mov));
    $response->assertSessionHas('success');

    $producto->refresh();
    expect($producto->stock_actual)->toBe($stockConSalida + 2);
});
