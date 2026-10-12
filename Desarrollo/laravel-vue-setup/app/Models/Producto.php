<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'tbl_productos';

    protected $fillable = [
        'id_tipo_producto',
        'codigo',
        'descripcion',
        'stock_actual',
        'stock_minimo',
    ];

    protected $casts = [
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
        'id_tipo_producto' => 'integer',
    ];

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoProducto::class, 'id_tipo_producto');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(InventarioProducto::class, 'id_producto');
    }

    /**
     * Recalcula y sincroniza el stock actual basado en las entradas y salidas registradas.
     */
    public function recalcularStock(): int
    {
        $entradas = (int) $this->movimientos()->where('tipo_movimiento', 'ENTRADA')->sum('cantidad');
        $salidas = (int) $this->movimientos()->where('tipo_movimiento', 'SALIDA')->sum('cantidad');
        $nuevoStock = max(0, $entradas - $salidas);

        $this->stock_actual = $nuevoStock;
        $this->save();

        return $nuevoStock;
    }
}
