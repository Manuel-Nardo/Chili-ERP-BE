<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposClienteSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['clave' => 'INTERNO', 'nombre' => 'Cliente Interno', 'activo' => 1],
            ['clave' => 'EXTERNO', 'nombre' => 'Cliente Externo', 'activo' => 1],
        ];

        foreach ($rows as $r) {
            DB::table('tipos_cliente')->updateOrInsert(
                ['clave' => $r['clave']],
                $r + ['updated_at' => now(), 'created_at' => now()],
            );
        }
    }
}