<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'clave',
        'clave_sat',
        'nombre',
        'descripcion',
        'activo',
        'facturable',
        'linea_id',
        'tipo_pedido_id',
        'medida_id',
        'medida_compra_id',
        'ruta',
        'precio_actual',
        'costo_actual',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'facturable' => 'boolean',
        'precio_actual' => 'decimal:2',
        'costo_actual' => 'decimal:2',
    ];

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function tipoPedido(): BelongsTo
    {
        return $this->belongsTo(TipoPedido::class, 'tipo_pedido_id');
    }

    public function medida(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'medida_id');
    }

    public function medidaCompra(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'medida_compra_id');
    }

    public function impuestos(): BelongsToMany
    {
        return $this->belongsToMany(Impuesto::class, 'producto_impuesto', 'producto_id', 'impuesto_id')
            ->withTimestamps();
    }

    public function costos(): HasMany
    {
        return $this->hasMany(ProductoCosto::class, 'producto_id');
    }

    public function precios(): HasMany
    {
        return $this->hasMany(ProductoPrecio::class, 'producto_id');
    }

    public function pedidoSugerenciasDetalle(): HasMany
    {
        return $this->hasMany(PedidoSugerenciaDetalle::class, 'producto_id');
    }
}