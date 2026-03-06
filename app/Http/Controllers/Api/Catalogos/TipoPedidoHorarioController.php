<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\TipoPedidoHorarioStoreRequest;
use App\Http\Requests\Catalogos\TipoPedidoHorarioUpdateRequest;
use App\Models\TipoPedidoHorario;
use Illuminate\Http\Request;

class TipoPedidoHorarioController extends Controller
{
    public function index(Request $request)
    {
        $tipoPedidoId = $request->query('tipo_pedido_id');
        $activo = $request->query('activo', null);
        $perPage = (int) $request->query('per_page', 50);

        $query = TipoPedidoHorario::query()
            ->with(['tipoPedido']);

        if ($tipoPedidoId) {
            $query->where('tipo_pedido_id', (int) $tipoPedidoId);
        }

        if ($activo !== null) {
            $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOL));
        }

        return response()->json([
            'success' => true,
            'data' => $query
                ->orderBy('tipo_pedido_id')
                ->orderBy('dia_semana')
                ->paginate($perPage),
        ]);
    }

    public function store(TipoPedidoHorarioStoreRequest $request)
    {
        $validated = $request->validated();

        $exists = TipoPedidoHorario::query()
            ->where('tipo_pedido_id', $validated['tipo_pedido_id'])
            ->where('dia_semana', $validated['dia_semana'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un horario para ese tipo de pedido en el día seleccionado.',
            ], 422);
        }

        $horario = TipoPedidoHorario::create([
            'tipo_pedido_id' => $validated['tipo_pedido_id'],
            'dia_semana' => $validated['dia_semana'],
            'hora_inicio' => $validated['hora_inicio'],
            'hora_fin' => $validated['hora_fin'],
            'activo' => $validated['activo'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $horario->load('tipoPedido'),
        ], 201);
    }

    public function show(TipoPedidoHorario $tipo_pedido_horario)
    {
        return response()->json([
            'success' => true,
            'data' => $tipo_pedido_horario->load('tipoPedido'),
        ]);
    }

    public function update(TipoPedidoHorarioUpdateRequest $request, TipoPedidoHorario $tipo_pedido_horario)
    {
        $validated = $request->validated();

        $tipoPedidoId = $validated['tipo_pedido_id'] ?? $tipo_pedido_horario->tipo_pedido_id;
        $diaSemana = $validated['dia_semana'] ?? $tipo_pedido_horario->dia_semana;

        $exists = TipoPedidoHorario::query()
            ->where('tipo_pedido_id', $tipoPedidoId)
            ->where('dia_semana', $diaSemana)
            ->where('id', '!=', $tipo_pedido_horario->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un horario para ese tipo de pedido en el día seleccionado.',
            ], 422);
        }

        $tipo_pedido_horario->update($validated);

        return response()->json([
            'success' => true,
            'data' => $tipo_pedido_horario->fresh()->load('tipoPedido'),
        ]);
    }

    public function destroy(TipoPedidoHorario $tipo_pedido_horario)
    {
        $tipo_pedido_horario->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}