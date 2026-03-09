<?php

namespace Database\Seeders;

use App\Models\Impuesto;
use Illuminate\Database\Seeder;

class ImpuestoSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['nombre' => 'IVA 0', 'codigo' => 'IVA0', 'tipo' => 'IVA', 'porcentaje' => 0, 'activo' => true],
            ['nombre' => 'IVA 8', 'codigo' => 'IVA8', 'tipo' => 'IVA', 'porcentaje' => 8, 'activo' => true],
            ['nombre' => 'IVA 16', 'codigo' => 'IVA16', 'tipo' => 'IVA', 'porcentaje' => 16, 'activo' => true],
            ['nombre' => 'IEPS 8', 'codigo' => 'IEPS8', 'tipo' => 'IEPS', 'porcentaje' => 8, 'activo' => true],
        ];

        foreach ($items as $item) {
            Impuesto::updateOrCreate(
                ['codigo' => $item['codigo']],
                $item
            );
        }
    }
}