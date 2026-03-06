<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes_tipos_pedido', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnDelete();

            $table->foreignId('tipo_pedido_id')
                ->constrained('tipos_pedido')
                ->cascadeOnDelete();

            $table->boolean('usar_horario_default')->default(true);
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(['cliente_id', 'tipo_pedido_id'], 'uq_cliente_tipo_pedido');
            $table->index(['cliente_id', 'activo']);
            $table->index(['tipo_pedido_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_tipos_pedido');
    }
};