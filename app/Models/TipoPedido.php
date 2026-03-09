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
        return $this->hasMany(Producto::class, 'tipo_pedido_id');
    }


}