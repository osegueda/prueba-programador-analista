<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoProducto extends Model
{
    use HasFactory;

    protected $table = 'tbl_tipo_producto';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_tipo_producto');
    }
}
