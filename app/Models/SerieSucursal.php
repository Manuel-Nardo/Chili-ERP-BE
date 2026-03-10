<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerieSucursal extends Model
{
    protected $table = 'series_sucursal';

    protected $fillable = [
        'cliente_id',
        'tipo_serie_id',
        'serie',
        'folio_actual',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function tipoSerie()
    {
        return $this->belongsTo(TipoSerie::class, 'tipo_serie_id');
    }
}