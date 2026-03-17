<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_sugerencias_detalle', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_sugerencia_id')
                ->constrained('pedido_sugerencias')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('cantidad_sugerida', 12, 2)->default(0);
            $table->decimal('cantidad_ajustada', 12, 2)->default(0);
            $table->decimal('cantidad_final', 12, 2)->default(0);

            $table->text('observaciones')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('pedido_sugerencia_id', 'idx_pedido_sug_det_sugerencia');
            $table->index('producto_id', 'idx_pedido_sug_det_producto');
            $table->unique(['pedido_sugerencia_id', 'producto_id'], 'uq_pedido_sug_det_producto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_sugerencias_detalle');
    }
};