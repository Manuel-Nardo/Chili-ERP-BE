<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteTipoPedido extends Model
{
    protected $table = 'clientes_tipos_pedido';

    protected $fillable = [
        'cliente_id',
        'tipo_pedido_id',
        'usar_horario_default',
        'activo',
    ];

    protected $casts = [
        'usar_horario_default' => 'boolean',
        'activo' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function tipoPedido(): BelongsTo
    {
        return $this->belongsTo(TipoPedido::class, 'tipo_pedido_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(ClienteTipoPedidoHorario::class, 'cliente_tipo_pedido_id');
    }
}