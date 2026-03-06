<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_pedido', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->longText('detalle')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
            $table->unique('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_pedido');
    }
};