<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoSerie extends Model
{
    protected $table = 'tipos_serie';

    protected $fillable = [
        'nombre',
        'clave',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function seriesSucursal()
    {
        return $this->hasMany(SerieSucursal::class, 'tipo_serie_id');
    }
}