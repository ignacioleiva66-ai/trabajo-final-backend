<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de contactos empresariales de cada cliente B2B.
     * Un cliente puede tener múltiples contactos de empresa
     * (gerente, área de compras, finanzas, etc.)
     */
    public function up(): void
    {
        Schema::create('contactos_empresa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')
                  ->constrained('clientes')
                  ->onDelete('cascade');

            $table->string('razon_social');            // Razón social legal
            $table->string('rut_empresa')->unique();   // RUT empresa (ej: 76.123.456-7)
            $table->string('giro');                    // Giro comercial (ej: "Comercio al por menor")
            $table->string('direccion_fiscal');        // Dirección tributaria
            $table->string('ciudad');
            $table->string('region');
            $table->string('pais')->default('Chile');
            $table->string('telefono_empresa')->nullable();
            $table->string('email_empresa');           // Email corporativo general
            $table->string('sitio_web')->nullable();
            $table->enum('tipo_contacto', [
                'Principal',
                'Facturacion',
                'Despacho',
                'Cobranza'
            ])->default('Principal');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contactos_empresa');
    }
};
