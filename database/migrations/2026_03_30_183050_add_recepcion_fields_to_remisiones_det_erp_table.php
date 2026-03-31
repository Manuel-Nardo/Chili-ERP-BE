<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('remisiones_det_erp', function (Blueprint $table) {
            $table->double('cantidad_recibida')
                ->default(0)
                ->after('cantidad');

            $table->double('diferencia')
                ->default(0)
                ->after('cantidad_recibida');

            $table->longText('observaciones_recepcion')
                ->nullable()
                ->after('observaciones');

            $table->index('cantidad_recibida');
            $table->index('diferencia');
        });

        DB::table('remisiones_det_erp')->update([
            'cantidad_recibida' => 0,
            'diferencia' => DB::raw('cantidad'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remisiones_det_erp', function (Blueprint $table) {
            $table->dropIndex(['cantidad_recibida']);
            $table->dropIndex(['diferencia']);

            $table->dropColumn([
                'cantidad_recibida',
                'diferencia',
                'observaciones_recepcion',
            ]);
        });
    }
};