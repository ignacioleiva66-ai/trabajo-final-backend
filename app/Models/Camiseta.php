<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Camiseta extends Model
{
    use SoftDeletes;

    protected $table = 'camisetas';

    protected $fillable = [
        'titulo',
        'club',
        'pais',
        'tipo',
        'color',
        'precio',
        'precio_oferta',
        'detalles',
        'codigo_producto',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Relación muchos a muchos con Talla (con stock por talla).
     */
    public function tallas(): BelongsToMany
    {
        return $this->belongsToMany(Talla::class, 'camiseta_talla')
                    ->withPivot('stock')
                    ->withTimestamps();
    }

    /**
     * Relación muchos a muchos con Cliente.
     */
    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(Cliente::class, 'cliente_camiseta')
                    ->withPivot('cantidad')
                    ->withTimestamps();
    }

    /**
     * Calcula el precio final según la categoría y porcentaje_oferta del cliente.
     *
     * Reglas de negocio (según enunciado):
     *
     * 1. Si el cliente es Preferencial y la camiseta tiene precio_oferta definido
     *    → candidato A = precio_oferta
     *
     * 2. Si el cliente tiene porcentaje_oferta > 0
     *    → candidato B = precio * (1 - porcentaje_oferta / 100)
     *
     * 3. Si existen ambos candidatos → se aplica el MENOR (mejor precio para el cliente)
     *
     * 4. Si ninguna condición aplica → precio base
     *
     * @param Cliente $cliente
     * @return array{ precio_final: float, descuento_aplicado: string }
     */
    public function precioFinalParaCliente(Cliente $cliente): array
    {
        $precioBase = (float) $this->precio;
        $candidatos = [];
        $descripcion = 'precio_base';

        // Candidato A: precio_oferta para clientes Preferenciales
        if ($cliente->categoria === 'Preferencial' && !is_null($this->precio_oferta)) {
            $candidatos['precio_oferta'] = (float) $this->precio_oferta;
        }

        // Candidato B: descuento porcentual del cliente
        if ($cliente->porcentaje_oferta > 0) {
            $candidatos['porcentaje_oferta'] = round(
                $precioBase * (1 - $cliente->porcentaje_oferta / 100),
                0
            );
        }

        if (empty($candidatos)) {
            return [
                'precio_final'       => $precioBase,
                'descuento_aplicado' => 'ninguno',
            ];
        }

        // Aplicar el mejor precio (el menor) para el cliente
        $precioFinal = min($candidatos);
        $descripcion = array_search($precioFinal, $candidatos);

        return [
            'precio_final'       => $precioFinal,
            'descuento_aplicado' => $descripcion,
        ];
    }
}
