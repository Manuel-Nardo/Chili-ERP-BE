<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cliente_sucursal_pos_mapeo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // ID de sucursal en POS (externo, no FK local)
            $table->unsignedBigInteger('pos_sucursal_id');

            $table->boolean('activo')->default(true);
            $table->boolean('es_principal')->default(true);

            $table->timestamps();

            $table->unique(
                ['cliente_id', 'pos_sucursal_id'],
                'uq_cliente_pos_sucursal'
            );

            $table->index(
                ['cliente_id', 'activo'],
                'idx_cliente_pos_activo'
            );

            $table->index(
                ['pos_sucursal_id', 'activo'],
                'idx_pos_sucursal_activo'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_sucursal_pos_mapeo');
    }
};