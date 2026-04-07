<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_tipo_pedido', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('tipo_pedido_id')
                ->constrained('tipos_pedido')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(['producto_id', 'tipo_pedido_id'], 'producto_tipo_pedido_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_tipo_pedido');
    }
};