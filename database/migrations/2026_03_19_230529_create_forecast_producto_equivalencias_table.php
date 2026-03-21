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
        Schema::create('forecast_producto_equivalencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('tipo_pedido_id')
                ->constrained('tipos_pedido')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Identificadores del producto en POS / fuente histórica
            $table->unsignedBigInteger('producto_fuente_id')->nullable();
            $table->string('producto_fuente_clave', 100)->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(
                ['cliente_id', 'tipo_pedido_id', 'producto_id'],
                'uq_forecast_equiv_cliente_tipo_producto'
            );

            $table->index(
                ['cliente_id', 'tipo_pedido_id', 'activo'],
                'idx_forecast_equiv_contexto'
            );

            $table->index(
                ['producto_fuente_id'],
                'idx_forecast_equiv_fuente_id'
            );

            $table->index(
                ['producto_fuente_clave'],
                'idx_forecast_equiv_fuente_clave'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_producto_equivalencias');
    }
};