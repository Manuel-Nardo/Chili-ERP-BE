<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);

            $table->foreignId('tipo_cliente_id')
                ->constrained('tipos_cliente')
                ->restrictOnDelete();

            $table->foreignId('zona_id')
                ->nullable()
                ->constrained('zonas')
                ->nullOnDelete();

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index(['tipo_cliente_id', 'activo']);
            $table->index(['zona_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};