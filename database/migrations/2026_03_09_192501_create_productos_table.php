<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {

            $table->id();

            $table->integer('clave')->unique();
            $table->string('clave_sat')->nullable();

            $table->string('nombre');
            $table->text('descripcion')->nullable();

            $table->boolean('activo')->default(true);
            $table->boolean('facturable')->default(false);

            // Clasificación
            $table->foreignId('linea_id')
                ->constrained('lineas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('tipo_pedido_id')
                ->constrained('tipos_pedido')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Unidad
            $table->foreignId('medida_id')
                ->constrained('unidades')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('medida_compra_id')
                ->constrained('unidades')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Ruta de producción
            $table->enum('ruta', ['FRIA', 'CALIENTE', 'PAN'])->nullable();

            // cache rápido (histórico vive en tablas aparte)
            $table->decimal('precio_actual', 10, 2)->nullable();
            $table->decimal('costo_actual', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};