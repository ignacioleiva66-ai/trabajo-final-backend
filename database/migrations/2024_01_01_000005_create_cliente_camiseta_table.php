<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Relación entre clientes y camisetas (catálogo visible por cliente / pedidos)
        Schema::create('cliente_camiseta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('camiseta_id')->constrained('camisetas')->onDelete('restrict');
            $table->integer('cantidad')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_camiseta');
    }
};
