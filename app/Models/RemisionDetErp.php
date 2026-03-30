<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemisionDetErp extends Model
{
    protected $table = 'remisiones_det_erp';

    protected $fillable = [
        'remision_id',
        'pedido_det_erp_id',
        'articulo_id',
        'cantidad',
        'precio_unitario',
        'importe',
        'iva',
        'impuesto_iva',
        'total',
        'estatus',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'precio_unitario' => 'float',
        'importe' => 'float',
        'iva' => 'float',
        'impuesto_iva' => 'float',
        'total' => 'float',
    ];

    public function remision(): BelongsTo
    {
        return $this->belongsTo(RemisionErp::class, 'remision_id');
    }

    public function pedidoDetalle(): BelongsTo
    {
        return $this->belongsTo(PedidoDetErp::class, 'pedido_det_erp_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'articulo_id');
    }
}