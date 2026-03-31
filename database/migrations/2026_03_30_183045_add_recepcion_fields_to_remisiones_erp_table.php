<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('remisiones_erp', function (Blueprint $table) {
            $table->date('fecha_recepcion')
                ->nullable()
                ->after('fecha_objetivo');

            $table->unsignedBigInteger('recibido_por')
                ->nullable()
                ->after('fecha_recepcion');

            $table->dateTime('recibido_at')
                ->nullable()
                ->after('recibido_por');

            $table->longText('observaciones_recepcion')
                ->nullable()
                ->after('observaciones');

            $table->index('fecha_recepcion');
            $table->index('recibido_por');
            $table->index('recibido_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remisiones_erp', function (Blueprint $table) {
            $table->dropIndex(['fecha_recepcion']);
            $table->dropIndex(['recibido_por']);
            $table->dropIndex(['recibido_at']);

            $table->dropColumn([
                'fecha_recepcion',
                'recibido_por',
                'recibido_at',
                'observaciones_recepcion',
            ]);
        });
    }
};