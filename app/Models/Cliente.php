<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre_comercial',
        'rut',
        'direccion',
        'categoria',
        'contacto_nombre',
        'contacto_email',
        'porcentaje_oferta',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Relación muchos a muchos con Camiseta (pedidos/catálogo).
     */
    public function camisetas(): BelongsToMany
    {
        return $this->belongsToMany(Camiseta::class, 'cliente_camiseta')
                    ->withPivot('cantidad')
                    ->withTimestamps();
    }

    public function contactosEmpresa(): HasMany
    {
        return $this->hasMany(ContactoEmpresa::class);
    }

    public function contactosPersonal(): HasMany
    {
        return $this->hasMany(ContactoPersonal::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
