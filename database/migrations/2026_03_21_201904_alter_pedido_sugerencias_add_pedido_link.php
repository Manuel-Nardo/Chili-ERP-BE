<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_sugerencias', function (Blueprint $table) {

            // Relación con pedido ERP
            if (!Schema::hasColumn('pedido_sugerencias', 'pedido_erp_id')) {
                $table->unsignedBigInteger('pedido_erp_id')
                    ->nullable()
                    ->after('estatus');
            }

            // Fecha en que se generó el pedido
            if (!Schema::hasColumn('pedido_sugerencias', 'pedido_generado_at')) {
                $table->dateTime('pedido_generado_at')
                    ->nullable()
                    ->after('pedido_erp_id');
            }
        });

        // Índices
        Schema::table('pedido_sugerencias', function (Blueprint $table) {
            try {
                $table->index('pedido_erp_id', 'pedido_sugerencias_pedido_erp_id_idx');
            } catch (\Throwable $e) {}

            try {
                $table->index('pedido_generado_at', 'pedido_sugerencias_pedido_generado_at_idx');
            } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        // Eliminar índices
        Schema::table('pedido_sugerencias', function (Blueprint $table) {
            try {
                $table->dropIndex('pedido_sugerencias_pedido_erp_id_idx');
            } catch (\Throwable $e) {}

            try {
                $table->dropIndex('pedido_sugerencias_pedido_generado_at_idx');
            } catch (\Throwable $e) {}
        });

        // Eliminar columnas
        Schema::table('pedido_sugerencias', function (Blueprint $table) {

            if (Schema::hasColumn('pedido_sugerencias', 'pedido_erp_id')) {
                $table->dropColumn('pedido_erp_id');
            }

            if (Schema::hasColumn('pedido_sugerencias', 'pedido_generado_at')) {
                $table->dropColumn('pedido_generado_at');
            }
        });
    }
};