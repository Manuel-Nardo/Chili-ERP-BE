<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteBack extends Model
{
    protected $table = 'cliente_back';

    protected $fillable = [
        'cliente_id',
        'contacto',
        'telefono',
        'email',
        'direccion',
        'cp',
        'condicion_pago',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}