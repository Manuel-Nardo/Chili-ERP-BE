<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoPedidoHorario extends Model
{
    protected $table = 'tipos_pedido_horarios';

    protected $fillable = [
        'tipo_pedido_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'activo',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'activo' => 'boolean',
    ];

    public function tipoPedido(): BelongsTo
    {
        return $this->belongsTo(TipoPedido::class, 'tipo_pedido_id');
    }
}