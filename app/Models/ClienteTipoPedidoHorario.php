<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteTipoPedidoHorario extends Model
{
    protected $table = 'clientes_tipos_pedido_horarios';

    protected $fillable = [
        'cliente_tipo_pedido_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'activo',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'activo' => 'boolean',
    ];

    public function clienteTipoPedido(): BelongsTo
    {
        return $this->belongsTo(ClienteTipoPedido::class, 'cliente_tipo_pedido_id');
    }
}