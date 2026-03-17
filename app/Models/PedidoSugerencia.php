<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedidoSugerencia extends Model
{
    use HasFactory;

    protected $table = 'pedido_sugerencias';

    public const ESTATUS_BORRADOR = 'borrador';
    public const ESTATUS_CONFIRMADO = 'confirmado';
    public const ESTATUS_PROCESADO = 'procesado';
    public const ESTATUS_CANCELADO = 'cancelado';

    public const ORIGEN_MANUAL = 'manual';
    public const ORIGEN_FORECAST = 'forecast';

    protected $fillable = [
        'cliente_id',
        'tipo_pedido_id',
        'fecha_objetivo',
        'estatus',
        'origen',
        'modelo',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_objetivo' => 'date',
    ];

    public static function estatusDisponibles(): array
    {
        return [
            self::ESTATUS_BORRADOR,
            self::ESTATUS_CONFIRMADO,
            self::ESTATUS_PROCESADO,
            self::ESTATUS_CANCELADO,
        ];
    }

    public static function origenesDisponibles(): array
    {
        return [
            self::ORIGEN_MANUAL,
            self::ORIGEN_FORECAST,
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function tipoPedido(): BelongsTo
    {
        return $this->belongsTo(TipoPedido::class, 'tipo_pedido_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoSugerenciaDetalle::class, 'pedido_sugerencia_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeBorrador($query)
    {
        return $query->where('estatus', self::ESTATUS_BORRADOR);
    }

    public function scopeConfirmado($query)
    {
        return $query->where('estatus', self::ESTATUS_CONFIRMADO);
    }

    public function scopeProcesado($query)
    {
        return $query->where('estatus', self::ESTATUS_PROCESADO);
    }

    public function scopeCancelado($query)
    {
        return $query->where('estatus', self::ESTATUS_CANCELADO);
    }

    public function scopeManual($query)
    {
        return $query->where('origen', self::ORIGEN_MANUAL);
    }

    public function scopeForecast($query)
    {
        return $query->where('origen', self::ORIGEN_FORECAST);
    }

    public function esEditable(): bool
    {
        return $this->estatus === self::ESTATUS_BORRADOR;
    }

    public function estaConfirmado(): bool
    {
        return $this->estatus === self::ESTATUS_CONFIRMADO;
    }

    public function estaProcesado(): bool
    {
        return $this->estatus === self::ESTATUS_PROCESADO;
    }

    public function estaCancelado(): bool
    {
        return $this->estatus === self::ESTATUS_CANCELADO;
    }
}