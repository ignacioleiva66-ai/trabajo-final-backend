<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactoEmpresa extends Model
{
    use SoftDeletes;

    protected $table = 'contactos_empresa';

    protected $fillable = [
        'cliente_id',
        'razon_social',
        'rut_empresa',
        'giro',
        'direccion_fiscal',
        'ciudad',
        'region',
        'pais',
        'telefono_empresa',
        'email_empresa',
        'sitio_web',
        'tipo_contacto',
    ];

    protected $hidden = ['deleted_at'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
