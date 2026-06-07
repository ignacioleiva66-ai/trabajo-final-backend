<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de ventas: registra cada venta de camiseta a un cliente.
     * Permite contar unidades vendidas, calcular totales y aplicar ofertas.
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')
                  ->constrained('clientes')
                  ->onDelete('restrict');
            $table->foreignId('camiseta_id')
                  ->constrained('camisetas')
                  ->onDelete('restrict');
            $table->foreignId('talla_id')
                  ->nullable()
                  ->constrained('tallas')
                  ->onDelete('set null');

            $table->integer('cantidad');                        // Unidades vendidas
            $table->decimal('precio_unitario', 10, 2);         // Precio al momento de la venta
            $table->decimal('precio_oferta_aplicada', 10, 2)->nullable(); // Oferta aplicada (si hubo)
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);    // % descuento aplicado
            $table->decimal('precio_final_unitario', 10, 2);   // Precio final por unidad
            $table->decimal('total_venta', 10, 2);             // cantidad * precio_final_unitario
            $table->string('descuento_tipo')->nullable();       // 'precio_oferta' | 'porcentaje_oferta' | 'ninguno'

            $table->enum('estado', ['pendiente', 'confirmada', 'anulada'])->default('confirmada');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
