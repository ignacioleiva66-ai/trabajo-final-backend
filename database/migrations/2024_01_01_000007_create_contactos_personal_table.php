<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de contactos personales de cada cliente B2B.
     * Personas físicas (encargados, vendedores, etc.) asociadas a un cliente.
     */
    public function up(): void
    {
        Schema::create('contactos_personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')
                  ->constrained('clientes')
                  ->onDelete('cascade');

            $table->string('nombre');
            $table->string('apellido');
            $table->string('rut_personal')->nullable();        // RUT personal (ej: 12.345.678-9)
            $table->string('cargo');                           // Cargo (ej: "Encargado de Compras")
            $table->enum('area', [
                'Compras',
                'Finanzas',
                'Gerencia',
                'Logistica',
                'Ventas',
                'Otro'
            ])->default('Compras');
            $table->string('email');
            $table->string('telefono')->nullable();
            $table->string('celular')->nullable();
            $table->boolean('es_contacto_principal')->default(false);
            $table->text('notas')->nullable();                 // Observaciones adicionales

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contactos_personal');
    }
};
