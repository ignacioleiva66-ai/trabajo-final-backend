<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'cliente_id',
        'camiseta_id',
        'talla_id',
        'cantidad',
        'precio_unitario',
        'precio_oferta_aplicada',
        'porcentaje_descuento',
        'precio_final_unitario',
        'total_venta',
        'descuento_tipo',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'cantidad'               => 'integer',
        'precio_unitario'        => 'float',
        'precio_oferta_aplicada' => 'float',
        'porcentaje_descuento'   => 'float',
        'precio_final_unitario'  => 'float',
        'total_venta'            => 'float',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function camiseta(): BelongsTo
    {
        return $this->belongsTo(Camiseta::class);
    }

    public function talla(): BelongsTo
    {
        return $this->belongsTo(Talla::class);
    }
}
