<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series_sucursal', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('tipo_serie_id')
                ->constrained('tipos_serie')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('serie', 20)->unique();
            $table->unsignedBigInteger('folio_actual')->default(0);
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(['cliente_id', 'tipo_serie_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series_sucursal');
    }
};