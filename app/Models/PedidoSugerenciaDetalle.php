<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoSugerenciaDetalle extends Model
{
    use HasFactory;

    protected $table = 'pedido_sugerencias_detalle';

    protected $fillable = [
        'pedido_sugerencia_id',
        'producto_id',
        'cantidad_sugerida',
        'cantidad_ajustada',
        'cantidad_final',
        'observaciones',
        'metadata',
    ];

    protected $casts = [
        'cantidad_sugerida' => 'decimal:2',
        'cantidad_ajustada' => 'decimal:2',
        'cantidad_final' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function sugerencia(): BelongsTo
    {
        return $this->belongsTo(PedidoSugerencia::class, 'pedido_sugerencia_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}