<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoCosto extends Model
{
    protected $table = 'producto_costos';

    protected $fillable = [
        'producto_id',
        'costo',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}