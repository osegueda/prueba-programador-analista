<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\TipoProducto;
use App\Models\InventarioProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class ProductoController extends Controller
{
    /**
     * Display a listing of products with their current stock and metrics.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $tipoId = $request->input('id_tipo_producto');
        $estado = $request->input('estado'); // 'todos', 'disponible', 'bajo', 'agotado'

        $query = Producto::with('tipo')->withCount('movimientos');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('descripcion', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        if (!empty($tipoId)) {
            $query->where('id_tipo_producto', $tipoId);
        }

        if ($estado === 'disponible') {
            $query->whereColumn('stock_actual', '>', 'stock_minimo');
        } elseif ($estado === 'bajo') {
            $query->whereColumn('stock_actual', '<=', 'stock_minimo')
                  ->where('stock_actual', '>', 0);
        } elseif ($estado === 'agotado') {
            $query->where('stock_actual', '<=', 0);
        }

        $productos = $query->orderBy('id', 'desc')->get();

        // Resumen de métricas
        $totalProductos = Producto::count();
        $totalUnidadesStock = (int) Producto::sum('stock_actual');
        $productosStockBajo = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('stock_actual', '>', 0)
            ->count();
        $productosAgotados = Producto::where('stock_actual', '<=', 0)->count();

        $totalEntradas = InventarioProducto::where('tipo_movimiento', 'ENTRADA')->count();
        $totalSalidas = InventarioProducto::where('tipo_movimiento', 'SALIDA')->count();

        $tipos = TipoProducto::orderBy('nombre')->get();

        return Inertia::render('Inventario', [
            'productos' => $productos,
            'tipos' => $tipos,
            'filters' => [
                'search' => $search ?? '',
                'id_tipo_producto' => $tipoId ?? '',
                'estado' => $estado ?? 'todos',
            ],
            'metrics' => [
                'total_productos' => $totalProductos,
                'total_unidades' => $totalUnidadesStock,
                'stock_bajo' => $productosStockBajo,
                'agotados' => $productosAgotados,
                'total_entradas' => $totalEntradas,
                'total_salidas' => $totalSalidas,
            ],
        ]);
    }

    /**
     * Store a newly created product with its initial quantity.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_tipo_producto' => 'required|exists:tbl_tipo_producto,id',
            'descripcion' => 'required|string|max:255',
            'cantidad_inicial' => 'required|integer|min:0',
            'codigo' => 'nullable|string|max:50|unique:tbl_productos,codigo',
            'stock_minimo' => 'nullable|integer|min:0',
        ], [
            'id_tipo_producto.required' => 'Debes seleccionar una categoría para el producto.',
            'id_tipo_producto.exists' => 'La categoría seleccionada no es válida.',
            'descripcion.required' => 'La descripción del producto es obligatoria.',
            'cantidad_inicial.required' => 'La cantidad inicial es obligatoria.',
            'cantidad_inicial.min' => 'La cantidad inicial no puede ser negativa.',
            'codigo.unique' => 'El código de producto ingresado ya está en uso.',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $tipo = TipoProducto::findOrFail($validated['id_tipo_producto']);

            // Si no se proporcionó código, generar uno autoincrementable o representativo
            $codigo = $validated['codigo'] ?? null;
            if (empty($codigo)) {
                $prefijo = match ($tipo->id) {
                    1 => 'MO', // Materiales de oficina
                    2 => 'PL', // Productos de limpieza
                    3 => 'AP', // Artículos de papelería
                    default => 'PR',
                };
                $count = Producto::where('id_tipo_producto', $tipo->id)->count() + 1;
                $codigo = sprintf('%s-%03d', $prefijo, $count);

                // Asegurar unicidad
                while (Producto::where('codigo', $codigo)->exists()) {
                    $count++;
                    $codigo = sprintf('%s-%03d', $prefijo, $count);
                }
            }

            $cantidadInicial = (int) $validated['cantidad_inicial'];
            $stockMinimo = isset($validated['stock_minimo']) ? (int) $validated['stock_minimo'] : 5;

            $producto = Producto::create([
                'id_tipo_producto' => $tipo->id,
                'codigo' => $codigo,
                'descripcion' => $validated['descripcion'],
                'stock_actual' => $cantidadInicial,
                'stock_minimo' => $stockMinimo,
            ]);

            // Si la cantidad inicial es mayor a 0, registrar automáticamente en tbl_inventario_productos
            if ($cantidadInicial > 0) {
                InventarioProducto::create([
                    'id_producto' => $producto->id,
                    'tipo_movimiento' => 'ENTRADA',
                    'cantidad' => $cantidadInicial,
                    'fecha_ingreso' => Carbon::now(),
                    'motivo' => 'Inventario inicial de apertura',
                    'referencia' => 'INV-INI-' . $producto->codigo,
                ]);
            }

            return redirect()->back()->with('success', "Producto '{$producto->descripcion}' registrado exitosamente con {$cantidadInicial} unidades iniciales.");
        });
    }

    /**
     * Update product information.
     */
    public function update(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'id_tipo_producto' => 'required|exists:tbl_tipo_producto,id',
            'descripcion' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:50|unique:tbl_productos,codigo,' . $producto->id,
            'stock_minimo' => 'nullable|integer|min:0',
        ], [
            'id_tipo_producto.required' => 'Debes seleccionar una categoría.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'codigo.unique' => 'El código ya pertenece a otro producto.',
        ]);

        $producto->update([
            'id_tipo_producto' => $validated['id_tipo_producto'],
            'descripcion' => $validated['descripcion'],
            'codigo' => $validated['codigo'] ?? $producto->codigo,
            'stock_minimo' => $validated['stock_minimo'] ?? $producto->stock_minimo,
        ]);

        return redirect()->back()->with('success', "Producto '{$producto->descripcion}' actualizado exitosamente.");
    }

    /**
     * Delete product.
     */
    public function destroy(Producto $producto)
    {
        $descripcion = $producto->descripcion;
        $producto->delete();

        return redirect()->back()->with('success', "Producto '{$descripcion}' eliminado exitosamente.");
    }

    /**
     * Get the full history (kardex) of a single product.
     */
    public function historial(Producto $producto)
    {
        $producto->load('tipo');

        // Movimientos ordenados cronológicamente de más antiguo a más reciente para calcular saldo acumulado
        $movimientosAsc = $producto->movimientos()
            ->orderBy('fecha_ingreso', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $saldoAcumulado = 0;
        $historialConSaldo = [];

        foreach ($movimientosAsc as $mov) {
            if ($mov->tipo_movimiento === 'ENTRADA') {
                $saldoAcumulado += $mov->cantidad;
            } else {
                $saldoAcumulado = max(0, $saldoAcumulado - $mov->cantidad);
            }

            $historialConSaldo[] = [
                'id' => $mov->id,
                'tipo_movimiento' => $mov->tipo_movimiento,
                'cantidad' => $mov->cantidad,
                'fecha_ingreso' => $mov->fecha_ingreso ? $mov->fecha_ingreso->format('Y-m-d H:i') : null,
                'motivo' => $mov->motivo,
                'referencia' => $mov->referencia,
                'saldo_resultante' => $saldoAcumulado,
                'created_at' => $mov->created_at ? $mov->created_at->format('Y-m-d H:i') : null,
            ];
        }

        // Invertir para mostrar primero los más recientes en la tabla visual
        $historialDesc = array_reverse($historialConSaldo);

        $totalEntradas = (int) $producto->movimientos()->where('tipo_movimiento', 'ENTRADA')->sum('cantidad');
        $totalSalidas = (int) $producto->movimientos()->where('tipo_movimiento', 'SALIDA')->sum('cantidad');

        return response()->json([
            'producto' => $producto,
            'historial' => $historialDesc,
            'resumen' => [
                'total_entradas' => $totalEntradas,
                'total_salidas' => $totalSalidas,
                'stock_actual' => $producto->stock_actual,
                'stock_minimo' => $producto->stock_minimo,
                'total_movimientos' => count($historialConSaldo),
            ],
        ]);
    }
}
