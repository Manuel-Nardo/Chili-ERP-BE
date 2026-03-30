<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remisiones_erp', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pedido_erp_id');
            $table->unsignedBigInteger('serie_id');
            $table->bigInteger('folio');
            $table->string('estatus', 50)->default('GENERADA');
            $table->date('fecha_remision');
            $table->date('fecha_objetivo')->nullable();
            $table->dateTime('confirmado_at')->nullable();
            $table->unsignedBigInteger('sucursal_origen_id')->nullable();
            $table->unsignedBigInteger('sucursal_destino_id');
            $table->double('subtotal')->default(0);
            $table->double('impuestos')->default(0);
            $table->double('total')->default(0);
            $table->string('creado_por')->nullable();
            $table->unsignedBigInteger('autorizado_por')->nullable();
            $table->dateTime('autorizado_at')->nullable();
            $table->longText('observaciones')->nullable();
            $table->timestamps();
            $table->index('pedido_erp_id');
            $table->index(['serie_id', 'folio']);
            $table->index('sucursal_origen_id');
            $table->index('sucursal_destino_id');
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remisiones_erp');
    }
};