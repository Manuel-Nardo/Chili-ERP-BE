<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_erp', function (Blueprint $table) {
            $table->id();

            // Serie / folio
            $table->unsignedBigInteger('serie_id');
            $table->bigInteger('folio');

            // Tipo y estatus
            $table->unsignedBigInteger('tipo_pedido_id');
            $table->string('estatus', 50)->default('GENERADO');

            // Fechas
            $table->date('fecha_pedido');
            $table->date('fecha_objetivo')->nullable();
            $table->dateTime('confirmado_at')->nullable();

            // Sucursales
            $table->unsignedBigInteger('sucursal_origen_id')->nullable();
            $table->unsignedBigInteger('sucursal_destino_id');

            // Trazabilidad (forecast / origen)
            $table->string('origen_tipo', 50)->nullable(); // SUGERENCIA
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->unsignedBigInteger('pedido_sugerencia_id')->nullable();

            // Montos
            $table->double('subtotal')->default(0);
            $table->double('impuestos')->default(0);
            $table->double('total')->default(0);

            // Auditoría
            $table->string('creado_por')->nullable();
            $table->unsignedBigInteger('autorizado_por')->nullable();
            $table->dateTime('autorizado_at')->nullable();

            // Extra
            $table->longText('observaciones')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['serie_id', 'folio']);
            $table->index('pedido_sugerencia_id');
            $table->index(['origen_tipo', 'origen_id']);
            $table->index('sucursal_origen_id');
            $table->index('sucursal_destino_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_erp');
    }
};