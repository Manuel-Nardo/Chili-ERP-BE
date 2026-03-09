<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\ClienteTipoPedidoHorarioStoreRequest;
use App\Http\Requests\Catalogos\ClienteTipoPedidoHorarioUpdateRequest;
use App\Models\ClienteTipoPedido;
use App\Models\ClienteTipoPedidoHorario;
use Illuminate\Http\Request;

class ClienteTipoPedidoHorarioController extends Controller
{
    public function index(Request $request)
    {
        $clienteTipoPedidoId = $request->query('cliente_tipo_pedido_id');
        $clienteId = $request->query('cliente_id');
        $tipoPedidoId = $request->query('tipo_pedido_id');
        $activo = $request->query('activo', null);
        $perPage = (int) $request->query('per_page', 50);

        $query = ClienteTipoPedidoHorario::query()
            ->with([
                'clienteTipoPedido.cliente',
                'clienteTipoPedido.tipoPedido',
            ]);

        if ($clienteTipoPedidoId) {
            $query->where('cliente_tipo_pedido_id', (int) $clienteTipoPedidoId);
        }

        if ($clienteId || $tipoPedidoId) {
            $query->whereHas('clienteTipoPedido', function ($q) use ($clienteId, $tipoPedidoId) {
                if ($clienteId) {
                    $q->where('cliente_id', (int) $clienteId);
                }

                if ($tipoPedidoId) {
                    $q->where('tipo_pedido_id', (int) $tipoPedidoId);
                }
            });
        }

        if ($activo !== null) {
            $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOL));
        }

        return response()->json([
            'success' => true,
            'data' => $query
                ->orderBy('cliente_tipo_pedido_id')
                ->orderBy('dia_semana')
                ->paginate($perPage),
        ]);
    }

    public function store(ClienteTipoPedidoHorarioStoreRequest $request)
    {
        $validated = $request->validated();

        $asignacion = ClienteTipoPedido::findOrFail($validated['cliente_tipo_pedido_id']);

        if ($asignacion->usar_horario_default) {
            return response()->json([
                'success' => false,
                'message' => 'La asignación seleccionada usa horario default. Desactiva esa opción antes de crear horarios personalizados.',
            ], 422);
        }

        $exists = ClienteTipoPedidoHorario::query()
            ->where('cliente_tipo_pedido_id', $validated['cliente_tipo_pedido_id'])
            ->where('dia_semana', $validated['dia_semana'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un horario personalizado para esa asignación en el día seleccionado.',
            ], 422);
        }

        $horario = ClienteTipoPedidoHorario::create([
            'cliente_tipo_pedido_id' => $validated['cliente_tipo_pedido_id'],
            'dia_semana' => $validated['dia_semana'],
            'hora_inicio' => $validated['hora_inicio'],
            'hora_fin' => $validated['hora_fin'],
            'activo' => $validated['activo'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $horario->load([
                'clienteTipoPedido.cliente',
                'clienteTipoPedido.tipoPedido',
            ]),
        ], 201);
    }

    public function show(ClienteTipoPedidoHorario $cliente_tipo_pedido_horario)
    {
        return response()->json([
            'success' => true,
            'data' => $cliente_tipo_pedido_horario->load([
                'clienteTipoPedido.cliente',
                'clienteTipoPedido.tipoPedido',
            ]),
        ]);
    }

    public function update(
        ClienteTipoPedidoHorarioUpdateRequest $request,
        ClienteTipoPedidoHorario $cliente_tipo_pedido_horario
    ) {
        $validated = $request->validated();

        $clienteTipoPedidoId = $validated['cliente_tipo_pedido_id'] ?? $cliente_tipo_pedido_horario->cliente_tipo_pedido_id;
        $diaSemana = $validated['dia_semana'] ?? $cliente_tipo_pedido_horario->dia_semana;

        $asignacion = ClienteTipoPedido::findOrFail($clienteTipoPedidoId);

        if ($asignacion->usar_horario_default) {
            return response()->json([
                'success' => false,
                'message' => 'La asignación seleccionada usa horario default. Desactiva esa opción antes de editar horarios personalizados.',
            ], 422);
        }

        $exists = ClienteTipoPedidoHorario::query()
            ->where('cliente_tipo_pedido_id', $clienteTipoPedidoId)
            ->where('dia_semana', $diaSemana)
            ->where('id', '!=', $cliente_tipo_pedido_horario->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un horario personalizado para esa asignación en el día seleccionado.',
            ], 422);
        }

        $cliente_tipo_pedido_horario->update($validated);

        return response()->json([
            'success' => true,
            'data' => $cliente_tipo_pedido_horario->fresh()->load([
                'clienteTipoPedido.cliente',
                'clienteTipoPedido.tipoPedido',
            ]),
        ]);
    }

    public function destroy(ClienteTipoPedidoHorario $cliente_tipo_pedido_horario)
    {
        $cliente_tipo_pedido_horario->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}