<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $fillable = [
        'nombre',
        'slug',
        'codigo',
        'activo',
        'meta',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'meta' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}