<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function linea()
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function tipoPedido()
    {
        return $this->belongsTo(TipoPedido::class, 'tipo_pedido_id');
    }

    public function medida()
    {
        return $this->belongsTo(Unidad::class, 'medida_id');
    }

    public function medidaCompra()
    {
        return $this->belongsTo(Unidad::class, 'medida_compra_id');
    }

    public function impuestos()
    {
        return $this->belongsToMany(Impuesto::class, 'producto_impuesto', 'producto_id', 'impuesto_id')
            ->withTimestamps();
    }

    public function costos()
    {
        return $this->hasMany(ProductoCosto::class, 'producto_id');
    }

    public function precios()
    {
        return $this->hasMany(ProductoPrecio::class, 'producto_id');
    }
}