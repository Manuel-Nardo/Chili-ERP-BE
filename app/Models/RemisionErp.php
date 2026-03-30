<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RemisionErp extends Model
{
    protected $table = 'remisiones_erp';

    protected $fillable = [
        'pedido_erp_id',
        'serie_id',
        'folio',
        'estatus',
        'fecha_remision',
        'fecha_objetivo',
        'confirmado_at',
        'sucursal_origen_id',
        'sucursal_destino_id',
        'subtotal',
        'impuestos',
        'total',
        'creado_por',
        'autorizado_por',
        'autorizado_at',
        'observaciones',
    ];

    protected $casts = [
        'fecha_remision' => 'date:Y-m-d',
        'fecha_objetivo' => 'date:Y-m-d',
        'confirmado_at' => 'datetime',
        'autorizado_at' => 'datetime',
        'subtotal' => 'float',
        'impuestos' => 'float',
        'total' => 'float',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoErp::class, 'pedido_erp_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RemisionDetErp::class, 'remision_id');
    }

    public function serieSucursal(): BelongsTo
    {
        return $this->belongsTo(SerieSucursal::class, 'serie_id');
    }

    public function sucursalOrigen(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'sucursal_origen_id');
    }

    public function sucursalDestino(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'sucursal_destino_id');
    }
}