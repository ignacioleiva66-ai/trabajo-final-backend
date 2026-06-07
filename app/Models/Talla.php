<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Talla extends Model
{
    protected $table = 'tallas';

    protected $fillable = ['nombre'];

    /**
     * Relación muchos a muchos con Camiseta.
     */
    public function camisetas(): BelongsToMany
    {
        return $this->belongsToMany(Camiseta::class, 'camiseta_talla')
                    ->withPivot('stock')
                    ->withTimestamps();
    }
}
