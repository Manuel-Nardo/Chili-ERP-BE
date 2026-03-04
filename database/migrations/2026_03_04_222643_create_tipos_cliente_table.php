<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tipos_cliente', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->unique();   // INTERNO, EXTERNO
            $table->string('nombre', 120);
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_cliente');
    }
};