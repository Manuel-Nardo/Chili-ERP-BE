<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoDetErp extends Model
{
    protected $table = 'pedidos_det_erp';

    protected $fillable = [
        'folio',
        'articulo_id',
        'cantidad',
        'pu',
        'importe',
        'iva',
        'impuesto_iva',
        'total',
        'estatus',
        'observaciones',
        'motivo_id',
        'c_remisiona',
        'c_existencias',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'pu' => 'float',
        'importe' => 'float',
        'iva' => 'float',
        'impuesto_iva' => 'float',
        'total' => 'float',
        'c_remisiona' => 'float',
        'c_existencias' => 'float',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoErp::class, 'folio');
    }
}