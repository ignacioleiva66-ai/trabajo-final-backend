<?php

namespace Database\Seeders;

use App\Models\Camiseta;
use App\Models\Cliente;
use App\Models\Talla;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── TALLAS ──────────────────────────────────────────────────────
        $tallas = ['S', 'M', 'L', 'XL', 'XXL'];
        foreach ($tallas as $nombre) {
            Talla::firstOrCreate(['nombre' => $nombre]);
        }

        // ── CLIENTES INICIALES ──────────────────────────────────────────
        Cliente::firstOrCreate(
            ['rut' => '76.123.456-7'],
            [
                'nombre_comercial'  => '90minutos',
                'direccion'         => 'Providencia, Santiago',
                'categoria'         => 'Preferencial',
                'contacto_nombre'   => 'Pedro Rojas',
                'contacto_email'    => 'compras@90minutos.cl',
                'porcentaje_oferta' => 15.00,
            ]
        );

        Cliente::firstOrCreate(
            ['rut' => '77.654.321-0'],
            [
                'nombre_comercial'  => 'tdeportes',
                'direccion'         => 'Las Condes, Santiago',
                'categoria'         => 'Regular',
                'contacto_nombre'   => 'Ana Soto',
                'contacto_email'    => 'compras@tdeportes.cl',
                'porcentaje_oferta' => 0.00,
            ]
        );

        // ── CAMISETAS DE EJEMPLO ────────────────────────────────────────
        $tallaS  = Talla::where('nombre', 'S')->first();
        $tallaM  = Talla::where('nombre', 'M')->first();
        $tallaL  = Talla::where('nombre', 'L')->first();
        $tallaXL = Talla::where('nombre', 'XL')->first();

        $camiseta1 = Camiseta::firstOrCreate(
            ['codigo_producto' => 'SCL2025L'],
            [
                'titulo'        => 'Camiseta Local 2025 – Selección Chilena',
                'club'          => 'Selección Chilena',
                'pais'          => 'Chile',
                'tipo'          => 'Local',
                'color'         => 'Rojo y Azul',
                'precio'        => 45000,
                'precio_oferta' => 38000,
                'detalles'      => 'Edición aniversario 2025',
            ]
        );

        $camiseta1->tallas()->syncWithoutDetaching([
            $tallaS->id  => ['stock' => 10],
            $tallaM->id  => ['stock' => 20],
            $tallaL->id  => ['stock' => 15],
            $tallaXL->id => ['stock' => 8],
        ]);

        $camiseta2 = Camiseta::firstOrCreate(
            ['codigo_producto' => 'RMA2025V'],
            [
                'titulo'        => 'Camiseta Visita 2025 – Real Madrid',
                'club'          => 'Real Madrid',
                'pais'          => 'España',
                'tipo'          => 'Visita',
                'color'         => 'Negro',
                'precio'        => 62000,
                'precio_oferta' => 55000,
                'detalles'      => 'Versión jugador',
            ]
        );

        $camiseta2->tallas()->syncWithoutDetaching([
            $tallaM->id  => ['stock' => 12],
            $tallaL->id  => ['stock' => 10],
            $tallaXL->id => ['stock' => 5],
        ]);
    }
}
