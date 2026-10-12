<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioProducto extends Model
{
    use HasFactory;

    protected $table = 'tbl_inventario_productos';

    protected $fillable = [
        'id_producto',
        'tipo_movimiento',
        'cantidad',
        'fecha_ingreso',
        'motivo',
        'referencia',
    ];

    protected $casts = [
        'fecha_ingreso' => 'datetime',
        'cantidad' => 'integer',
        'id_producto' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}
