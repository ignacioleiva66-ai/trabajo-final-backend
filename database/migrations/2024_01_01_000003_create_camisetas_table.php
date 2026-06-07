<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camisetas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('club');
            $table->string('pais');
            $table->enum('tipo', ['Local', 'Visita', '3era Camiseta', 'Femenino Local', 'Niño']);
            $table->string('color');
            $table->decimal('precio', 10, 2);
            $table->decimal('precio_oferta', 10, 2)->nullable();
            $table->text('detalles')->nullable();
            $table->string('codigo_producto')->unique(); // SKU
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camisetas');
    }
};
