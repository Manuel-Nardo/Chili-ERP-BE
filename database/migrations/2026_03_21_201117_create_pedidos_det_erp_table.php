<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_det_erp', function (Blueprint $table) {
            $table->id();

            // Relación
            $table->unsignedBigInteger('pedido_id');

            // Producto
            $table->unsignedBigInteger('articulo_id');

            // Cantidad
            $table->double('cantidad');

            // Precios
            $table->double('precio_unitario')->default(0);
            $table->double('importe')->default(0);
            $table->double('iva')->nullable();
            $table->double('impuesto_iva')->nullable();
            $table->double('total')->default(0);

            // Estado
            $table->string('estatus', 50)->default('GENERADO');

            // Observaciones
            $table->longText('observaciones')->nullable();

            $table->timestamps();

            // Índices
            $table->index('pedido_id');
            $table->index('articulo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_det_erp');
    }
};