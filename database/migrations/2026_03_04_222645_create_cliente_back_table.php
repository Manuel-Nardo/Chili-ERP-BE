<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cliente_back', function (Blueprint $table) {
            $table->id();

            // 1 a 1 con clientes
            $table->foreignId('cliente_id')
                ->unique()
                ->constrained('clientes')
                ->cascadeOnDelete();

            $table->string('contacto', 255)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 255)->nullable();

            $table->text('direccion')->nullable();
            $table->string('cp', 10)->nullable();

            // si luego quieres normalizar, se mueve a catálogo
            $table->string('condicion_pago', 50)->nullable();

            $table->timestamps();

            $table->index('email');
            $table->index('telefono');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_back');
    }
};