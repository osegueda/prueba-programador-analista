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

class MovimientoInventarioController extends Controller
{
    /**
     * Display a listing of inventory movements (CRUD Read).
     */
    public function index(Request $request): Response
    {
        $tipo = $request->input('tipo_movimiento'); // 'ENTRADA', 'SALIDA' o null
        $productoId = $request->input('id_producto');
        $search = $request->input('search');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $query = InventarioProducto::with(['producto.tipo']);

        if (!empty($tipo) && in_array($tipo, ['ENTRADA', 'SALIDA'])) {
            $query->where('tipo_movimiento', $tipo);
        }

        if (!empty($productoId)) {
            $query->where('id_producto', $productoId);
        }

        if (!empty($fechaDesde)) {
            $query->whereDate('fecha_ingreso', '>=', $fechaDesde);
        }

        if (!empty($fechaHasta)) {
            $query->whereDate('fecha_ingreso', '<=', $fechaHasta);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('motivo', 'like', "%{$search}%")
                  ->orWhere('referencia', 'like', "%{$search}%")
                  ->orWhereHas('producto', function ($pq) use ($search) {
                      $pq->where('descripcion', 'like', "%{$search}%")
                         ->orWhere('codigo', 'like', "%{$search}%");
                  });
            });
        }

        $movimientos = $query->orderBy('fecha_ingreso', 'desc')
                             ->orderBy('id', 'desc')
                             ->get();

        $productos = Producto::with('tipo')->orderBy('descripcion')->get();

        $totalEntradas = InventarioProducto::where('tipo_movimiento', 'ENTRADA')->count();
        $totalSalidas = InventarioProducto::where('tipo_movimiento', 'SALIDA')->count();
        $sumaUnidadesEntrada = (int) InventarioProducto::where('tipo_movimiento', 'ENTRADA')->sum('cantidad');
        $sumaUnidadesSalida = (int) InventarioProducto::where('tipo_movimiento', 'SALIDA')->sum('cantidad');
        $tipos = TipoProducto::orderBy('nombre')->get();

        return Inertia::render('Movimientos', [
            'movimientos' => $movimientos,
            'productos' => $productos,
            'tipos' => $tipos,
            'filters' => [
                'tipo_movimiento' => $tipo ?? '',
                'id_producto' => $productoId ?? '',
                'search' => $search ?? '',
                'fecha_desde' => $fechaDesde ?? '',
                'fecha_hasta' => $fechaHasta ?? '',
            ],
            'stats' => [
                'total_entradas' => $totalEntradas,
                'total_salidas' => $totalSalidas,
                'unidades_entradas' => $sumaUnidadesEntrada,
                'unidades_salidas' => $sumaUnidadesSalida,
            ],
        ]);
    }

    /**
     * Store a newly created inventory movement (Entrada o Salida).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_producto' => 'required|exists:tbl_productos,id',
            'tipo_movimiento' => 'required|in:ENTRADA,SALIDA',
            'cantidad' => 'required|integer|min:1',
            'fecha_ingreso' => 'required|date',
            'motivo' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:100',
        ], [
            'id_producto.required' => 'Debes seleccionar un producto.',
            'id_producto.exists' => 'El producto seleccionado no existe.',
            'tipo_movimiento.required' => 'El tipo de movimiento es obligatorio (ENTRADA o SALIDA).',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser ENTRADA o SALIDA.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad debe ser de al menos 1 unidad.',
            'fecha_ingreso.required' => 'La fecha del movimiento es obligatoria.',
            'motivo.required' => 'El motivo u observación del movimiento es obligatorio.',
        ]);

        return DB::transaction(function () use ($validated) {
            $producto = Producto::lockForUpdate()->findOrFail($validated['id_producto']);
            $cantidad = (int) $validated['cantidad'];
            $tipo = $validated['tipo_movimiento'];

            // Si es SALIDA, validar existencias disponibles
            if ($tipo === 'SALIDA') {
                if ($producto->stock_actual < $cantidad) {
                    return redirect()->back()->with('error', "Stock insuficiente para realizar la salida. Existencia actual: {$producto->stock_actual} unidades. Solicitadas: {$cantidad} unidades.");
                }
            }

            $movimiento = InventarioProducto::create([
                'id_producto' => $producto->id,
                'tipo_movimiento' => $tipo,
                'cantidad' => $cantidad,
                'fecha_ingreso' => Carbon::parse($validated['fecha_ingreso']),
                'motivo' => $validated['motivo'],
                'referencia' => $validated['referencia'] ?? null,
            ]);

            // Actualizar y sincronizar el stock del producto
            $nuevoStock = $producto->recalcularStock();

            $accion = $tipo === 'ENTRADA' ? 'Entrada registrada con éxito (+'.$cantidad.')' : 'Salida registrada con éxito (-'.$cantidad.')';
            return redirect()->back()->with('success', "{$accion} para el producto '{$producto->descripcion}'. Stock actual: {$nuevoStock}.");
        });
    }

    /**
     * Update an existing inventory movement.
     */
    public function update(Request $request, InventarioProducto $movimiento)
    {
        $validated = $request->validate([
            'id_producto' => 'required|exists:tbl_productos,id',
            'tipo_movimiento' => 'required|in:ENTRADA,SALIDA',
            'cantidad' => 'required|integer|min:1',
            'fecha_ingreso' => 'required|date',
            'motivo' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:100',
        ], [
            'id_producto.required' => 'Debes seleccionar un producto.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'motivo.required' => 'El motivo es obligatorio.',
        ]);

        return DB::transaction(function () use ($validated, $movimiento) {
            $productoAnterior = Producto::lockForUpdate()->findOrFail($movimiento->id_producto);
            $productoNuevo = ($movimiento->id_producto == $validated['id_producto']) 
                ? $productoAnterior 
                : Producto::lockForUpdate()->findOrFail($validated['id_producto']);

            // Simulación de stock resultante en el producto
            // Aplicar la actualización tentativa y validar que ningún producto quede en negativo
            $movimiento->id_producto = $validated['id_producto'];
            $movimiento->tipo_movimiento = $validated['tipo_movimiento'];
            $movimiento->cantidad = (int) $validated['cantidad'];
            $movimiento->fecha_ingreso = Carbon::parse($validated['fecha_ingreso']);
            $movimiento->motivo = $validated['motivo'];
            $movimiento->referencia = $validated['referencia'] ?? null;
            $movimiento->save();

            // Recalcular stocks
            $stockNuevo = $productoNuevo->recalcularStock();
            if ($productoAnterior->id !== $productoNuevo->id) {
                $productoAnterior->recalcularStock();
            }

            // Validar que no haya stock inconsistente
            $entradas = (int) $productoNuevo->movimientos()->where('tipo_movimiento', 'ENTRADA')->sum('cantidad');
            $salidas = (int) $productoNuevo->movimientos()->where('tipo_movimiento', 'SALIDA')->sum('cantidad');

            if ($entradas < $salidas) {
                // Revertir
                DB::rollBack();
                return redirect()->back()->with('error', "No se puede aplicar la modificación porque causaría un stock negativo en el producto.");
            }

            return redirect()->back()->with('success', 'Movimiento de inventario actualizado exitosamente.');
        });
    }

    /**
     * Delete an existing inventory movement.
     */
    public function destroy(InventarioProducto $movimiento)
    {
        return DB::transaction(function () use ($movimiento) {
            $producto = Producto::lockForUpdate()->findOrFail($movimiento->id_producto);

            // Si es una entrada y se elimina, verificar que las salidas no superen las entradas restantes
            if ($movimiento->tipo_movimiento === 'ENTRADA') {
                $entradasRestantes = (int) $producto->movimientos()
                    ->where('tipo_movimiento', 'ENTRADA')
                    ->where('id', '!=', $movimiento->id)
                    ->sum('cantidad');
                $salidasActuales = (int) $producto->movimientos()
                    ->where('tipo_movimiento', 'SALIDA')
                    ->sum('cantidad');

                if ($entradasRestantes < $salidasActuales) {
                    return redirect()->back()->with('error', "No se puede eliminar esta entrada porque las unidades ya han sido despachadas en salidas registradas.");
                }
            }

            $movimiento->delete();
            $producto->recalcularStock();

            return redirect()->back()->with('success', "Movimiento de inventario eliminado correctamente.");
        });
    }
}
