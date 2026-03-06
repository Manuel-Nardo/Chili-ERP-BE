<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes_tipos_pedido_horarios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_tipo_pedido_id')
                ->constrained('clientes_tipos_pedido')
                ->cascadeOnDelete();

            // 1=Lunes ... 7=Domingo
            $table->unsignedTinyInteger('dia_semana');

            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(['cliente_tipo_pedido_id', 'dia_semana'], 'uq_cliente_tipo_pedido_dia');
            $table->index(['cliente_tipo_pedido_id', 'activo'], 'idx_ctp_horarios_activo');
            $table->index('dia_semana');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_tipos_pedido_horarios');
    }
};