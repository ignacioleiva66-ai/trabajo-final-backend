<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactoPersonal extends Model
{
    use SoftDeletes;

    protected $table = 'contactos_personal';

    protected $fillable = [
        'cliente_id',
        'nombre',
        'apellido',
        'rut_personal',
        'cargo',
        'area',
        'email',
        'telefono',
        'celular',
        'es_contacto_principal',
        'notas',
    ];

    protected $hidden  = ['deleted_at'];
    protected $casts   = ['es_contacto_principal' => 'boolean'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Nombre completo formateado.
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
