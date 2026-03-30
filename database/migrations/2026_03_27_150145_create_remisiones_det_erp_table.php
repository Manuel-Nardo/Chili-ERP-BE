<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remisiones_det_erp', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('remision_id');
            $table->unsignedBigInteger('pedido_det_erp_id')->nullable();
            $table->unsignedBigInteger('articulo_id');
            $table->double('cantidad');
            $table->double('precio_unitario')->default(0);
            $table->double('importe')->default(0);
            $table->double('iva')->nullable();
            $table->double('impuesto_iva')->nullable();
            $table->double('total')->default(0);
            $table->string('estatus', 50)->default('GENERADA');
            $table->longText('observaciones')->nullable();

            $table->timestamps();
            $table->index('remision_id');
            $table->index('pedido_det_erp_id');
            $table->index('articulo_id');
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remisiones_det_erp');
    }
};