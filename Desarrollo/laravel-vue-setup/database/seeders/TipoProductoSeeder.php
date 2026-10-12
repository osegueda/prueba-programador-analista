<?php

namespace Database\Seeders;

use App\Models\TipoProducto;
use App\Models\Producto;
use App\Models\InventarioProducto;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TipoProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Materiales de oficina',
                'descripcion' => 'Artículos y consumibles para oficina, engrapadoras, clips, calculadoras y organizadores.',
            ],
            [
                'nombre' => 'Productos de limpieza',
                'descripcion' => 'Insumos de aseo, desinfectantes, toallas, jabón líquido y aromatizantes.',
            ],
            [
                'nombre' => 'Artículos de papelería',
                'descripcion' => 'Papel bond, cuadernos, bolígrafos, marcadores y carpetas archivadoras.',
            ],
        ];

        foreach ($categorias as $cat) {
            TipoProducto::firstOrCreate(
                ['nombre' => $cat['nombre']],
                ['descripcion' => $cat['descripcion']]
            );
        }

        // Sembrar algunos productos iniciales con inventario para demostración
        $catOficina = TipoProducto::where('nombre', 'Materiales de oficina')->first();
        $catLimpieza = TipoProducto::where('nombre', 'Productos de limpieza')->first();
        $catPapeleria = TipoProducto::where('nombre', 'Artículos de papelería')->first();

        $demoProductos = [
            [
                'id_tipo_producto' => $catOficina->id,
                'codigo' => 'MO-001',
                'descripcion' => 'Engrapadora metálica estándar 26/6',
                'stock_inicial' => 15,
                'stock_minimo' => 5,
            ],
            [
                'id_tipo_producto' => $catOficina->id,
                'codigo' => 'MO-002',
                'descripcion' => 'Caja de clips mariposa 50mm (100 unidades)',
                'stock_inicial' => 40,
                'stock_minimo' => 10,
            ],
            [
                'id_tipo_producto' => $catLimpieza->id,
                'codigo' => 'PL-001',
                'descripcion' => 'Desinfectante multiusos aroma pino 1 Galón',
                'stock_inicial' => 20,
                'stock_minimo' => 4,
            ],
            [
                'id_tipo_producto' => $catLimpieza->id,
                'codigo' => 'PL-002',
                'descripcion' => 'Jabón antibacterial para manos 500ml',
                'stock_inicial' => 8,
                'stock_minimo' => 10, // Generará estado de stock bajo
            ],
            [
                'id_tipo_producto' => $catPapeleria->id,
                'codigo' => 'AP-001',
                'descripcion' => 'Resma de papel bond carta 75g (500 hojas)',
                'stock_inicial' => 50,
                'stock_minimo' => 15,
            ],
            [
                'id_tipo_producto' => $catPapeleria->id,
                'codigo' => 'AP-002',
                'descripcion' => 'Caja de bolígrafos tinta negra punta fina (12 unidades)',
                'stock_inicial' => 25,
                'stock_minimo' => 8,
            ],
        ];

        foreach ($demoProductos as $item) {
            $prod = Producto::firstOrCreate(
                ['codigo' => $item['codigo']],
                [
                    'id_tipo_producto' => $item['id_tipo_producto'],
                    'descripcion' => $item['descripcion'],
                    'stock_actual' => $item['stock_inicial'],
                    'stock_minimo' => $item['stock_minimo'],
                ]
            );

            // Si el producto no tiene movimientos previos, registrar la entrada inicial
            if ($prod->movimientos()->count() === 0 && $item['stock_inicial'] > 0) {
                InventarioProducto::create([
                    'id_producto' => $prod->id,
                    'tipo_movimiento' => 'ENTRADA',
                    'cantidad' => $item['stock_inicial'],
                    'fecha_ingreso' => Carbon::now()->subDays(rand(5, 15)),
                    'motivo' => 'Inventario inicial de apertura',
                    'referencia' => 'INV-INI-' . $prod->codigo,
                ]);
            }
        }

        // Registrar un par de movimientos de salida y entrada para que el historial tenga datos
        $pResma = Producto::where('codigo', 'AP-001')->first();
        if ($pResma && $pResma->movimientos()->count() === 1) {
            InventarioProducto::create([
                'id_producto' => $pResma->id,
                'tipo_movimiento' => 'SALIDA',
                'cantidad' => 10,
                'fecha_ingreso' => Carbon::now()->subDays(3),
                'motivo' => 'Entrega a Departamento de Contabilidad',
                'referencia' => 'VALE-101',
            ]);
            $pResma->recalcularStock();
        }

        $pDesinfectante = Producto::where('codigo', 'PL-001')->first();
        if ($pDesinfectante && $pDesinfectante->movimientos()->count() === 1) {
            InventarioProducto::create([
                'id_producto' => $pDesinfectante->id,
                'tipo_movimiento' => 'ENTRADA',
                'cantidad' => 5,
                'fecha_ingreso' => Carbon::now()->subDays(1),
                'motivo' => 'Compra adicional para reposición de bodega',
                'referencia' => 'FAC-5892',
            ]);
            $pDesinfectante->recalcularStock();
        }
    }
}
