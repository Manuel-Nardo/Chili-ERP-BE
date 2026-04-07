<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoPedido extends Model
{
    protected $table = 'tipos_pedido';

    protected $fillable = [
        'nombre',
        'detalle',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function clientesTiposPedido(): HasMany
    {
        return $this->hasMany(ClienteTipoPedido::class, 'tipo_pedido_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(TipoPedidoHorario::class, 'tipo_pedido_id');
    }

    public function productos()
    {
        return $this->belongsToMany(
            \App\Models\Producto::class,
            'producto_tipo_pedido',
            'tipo_pedido_id',
            'producto_id'
        )->withTimestamps();
    }

    public function pedidoSugerencias(): HasMany
    {
        return $this->hasMany(PedidoSugerencia::class, 'tipo_pedido_id');
    }
}