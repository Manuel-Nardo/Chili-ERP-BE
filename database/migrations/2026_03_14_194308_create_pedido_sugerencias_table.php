<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_sugerencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('tipo_pedido_id')
                ->constrained('tipos_pedido')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('fecha_objetivo');

            $table->enum('estatus', [
                'borrador',
                'confirmado',
                'procesado',
                'cancelado',
            ])->default('borrador');

            $table->enum('origen', [
                'manual',
                'forecast',
            ])->default('manual');

            $table->string('modelo', 100)->nullable();
            $table->text('observaciones')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['cliente_id', 'tipo_pedido_id', 'fecha_objetivo'], 'idx_pedido_sugerencias_busqueda');
            $table->index('estatus', 'idx_pedido_sugerencias_estatus');
            $table->index('origen', 'idx_pedido_sugerencias_origen');
            $table->unique(
                        ['cliente_id', 'tipo_pedido_id', 'fecha_objetivo'],
                        'uq_pedido_sugerencias_cliente_tipo_fecha'
                    );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_sugerencias');
    }
};