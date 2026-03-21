<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoErp extends Model
{
    protected $table = 'pedidos_erp';

    protected $fillable = [
        'serie',
        'num_folio',
        'tipo',
        'estatus',
        'fecha_pedido',
        'fecha_recepcion',
        'fecha_objetivo',
        'observaciones',
        'subtotal',
        'impuestos',
        'total',
        'usuariorealizo',
        'sucursal',
        'sucursal_origen',
        'sucursal_destino',
        'origen_tipo',
        'origen_id',
        'pedido_sugerencia_id',
        'autoriza_pedido_utileria',
        'autorizado_por',
        'confirmado_at',
        'hora_revisado',
    ];

    protected $casts = [
        'fecha_pedido' => 'date',
        'fecha_recepcion' => 'date',
        'fecha_objetivo' => 'date',
        'confirmado_at' => 'datetime',
        'hora_revisado' => 'datetime',
        'subtotal' => 'float',
        'impuestos' => 'float',
        'total' => 'float',
        'autoriza_pedido_utileria' => 'boolean',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoDetErp::class, 'folio');
    }

    public function sugerencia(): BelongsTo
    {
        return $this->belongsTo(PedidoSugerencia::class, 'pedido_sugerencia_id');
    }
}