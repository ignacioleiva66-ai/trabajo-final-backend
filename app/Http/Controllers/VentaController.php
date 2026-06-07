<?php

namespace App\Http\Controllers;

use App\Models\Camiseta;
use App\Models\Cliente;
use App\Models\Talla;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VentaController extends Controller
{
    /**
     * GET /api/ventas
     * Lista todas las ventas con resumen de totales.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Venta::with(['cliente', 'camiseta', 'talla']);

            // Filtros opcionales por query string
            if ($request->has('cliente_id')) {
                $query->where('cliente_id', $request->query('cliente_id'));
            }
            if ($request->has('camiseta_id')) {
                $query->where('camiseta_id', $request->query('camiseta_id'));
            }
            if ($request->has('estado')) {
                $query->where('estado', $request->query('estado'));
            }

            $ventas = $query->get();

            // ── Resumen agregado ──────────────────────────────────────────
            $resumen = [
                'total_ventas'      => $ventas->count(),
                'unidades_vendidas' => $ventas->where('estado', 'confirmada')->sum('cantidad'),
                'ingresos_totales'  => $ventas->where('estado', 'confirmada')->sum('total_venta'),
            ];

            return response()->json(['data' => $ventas, 'resumen' => $resumen], 200);

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener las ventas.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/ventas/{id}
     * Detalle de una venta.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $venta = Venta::with(['cliente', 'camiseta', 'talla'])->findOrFail($id);
            return response()->json(['data' => $venta], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Venta no encontrada.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener la venta.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/ventas
     * Registra una nueva venta.
     * Calcula automáticamente precio_final y total según cliente y camiseta.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'cliente_id'    => 'required|exists:clientes,id',
                'camiseta_id'   => 'required|exists:camisetas,id',
                'talla_id'      => 'nullable|exists:tallas,id',
                'cantidad'      => 'required|integer|min:1',
                'estado'        => ['nullable', Rule::in(['pendiente', 'confirmada', 'anulada'])],
                'observaciones' => 'nullable|string',
            ]);

            $cliente  = Cliente::findOrFail($validated['cliente_id']);
            $camiseta = Camiseta::findOrFail($validated['camiseta_id']);

            // ── Calcular precio con lógica de descuentos ──────────────────
            $resultado = $camiseta->precioFinalParaCliente($cliente);

            $precioUnitario       = (float) $camiseta->precio;
            $precioFinalUnitario  = $resultado['precio_final'];
            $descuentoTipo        = $resultado['descuento_aplicado'];

            // Calcular porcentaje de descuento real aplicado
            $porcentajeDescuento = 0;
            if ($precioUnitario > 0 && $precioFinalUnitario < $precioUnitario) {
                $porcentajeDescuento = round(
                    (($precioUnitario - $precioFinalUnitario) / $precioUnitario) * 100,
                    2
                );
            }

            $totalVenta = $precioFinalUnitario * $validated['cantidad'];

            DB::beginTransaction();

            $venta = Venta::create([
                'cliente_id'             => $validated['cliente_id'],
                'camiseta_id'            => $validated['camiseta_id'],
                'talla_id'               => $validated['talla_id'] ?? null,
                'cantidad'               => $validated['cantidad'],
                'precio_unitario'        => $precioUnitario,
                'precio_oferta_aplicada' => $camiseta->precio_oferta,
                'porcentaje_descuento'   => $porcentajeDescuento,
                'precio_final_unitario'  => $precioFinalUnitario,
                'total_venta'            => $totalVenta,
                'descuento_tipo'         => $descuentoTipo,
                'estado'                 => $validated['estado'] ?? 'confirmada',
                'observaciones'          => $validated['observaciones'] ?? null,
            ]);

            // Descontar stock de la talla si se indicó
            if (!empty($validated['talla_id'])) {
                $pivot = $camiseta->tallas()->where('talla_id', $validated['talla_id'])->first();
                if ($pivot && $pivot->pivot->stock < $validated['cantidad']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Stock insuficiente para la talla seleccionada.',
                        'stock_disponible' => $pivot->pivot->stock,
                    ], 409);
                }
                if ($pivot) {
                    $camiseta->tallas()->updateExistingPivot($validated['talla_id'], [
                        'stock' => $pivot->pivot->stock - $validated['cantidad'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'data'    => $venta->load(['cliente', 'camiseta', 'talla']),
                'message' => 'Venta registrada correctamente.',
                'resumen' => [
                    'precio_base'         => $precioUnitario,
                    'precio_final'        => $precioFinalUnitario,
                    'descuento_aplicado'  => $descuentoTipo,
                    'porcentaje_ahorro'   => $porcentajeDescuento . '%',
                    'total_venta'         => $totalVenta,
                ],
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Cliente o camiseta no encontrados.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al registrar la venta.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/ventas/{id}
     * Actualiza el estado o las observaciones de una venta.
     */
    public function patch(Request $request, int $id): JsonResponse
    {
        try {
            $venta = Venta::findOrFail($id);

            $validated = $request->validate([
                'estado'        => ['sometimes', Rule::in(['pendiente', 'confirmada', 'anulada'])],
                'observaciones' => 'nullable|string',
            ]);

            $venta->update($validated);

            return response()->json([
                'data'    => $venta->fresh()->load(['cliente', 'camiseta']),
                'message' => 'Venta actualizada correctamente.',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Venta no encontrada.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al actualizar la venta.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/ventas/{id}
     * Anula (elimina) una venta. Solo se pueden eliminar ventas pendientes.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $venta = Venta::findOrFail($id);

            if ($venta->estado === 'confirmada') {
                return response()->json([
                    'message' => 'No se puede eliminar una venta confirmada. Use PATCH para cambiar el estado a "anulada".',
                ], 409);
            }

            $venta->delete();

            return response()->json(['message' => 'Venta eliminada correctamente.'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Venta no encontrada.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al eliminar la venta.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/ventas/estadisticas
     * Estadísticas de ventas: conteo por camiseta, por cliente, totales.
     */
    public function estadisticas(): JsonResponse
    {
        try {
            // Ventas por camiseta (cuántas unidades se vendió cada una)
            $porCamiseta = Venta::where('estado', 'confirmada')
                ->select('camiseta_id', DB::raw('SUM(cantidad) as unidades_vendidas'), DB::raw('SUM(total_venta) as ingreso_total'))
                ->with('camiseta:id,titulo,codigo_producto')
                ->groupBy('camiseta_id')
                ->orderByDesc('unidades_vendidas')
                ->get();

            // Ventas por cliente
            $porCliente = Venta::where('estado', 'confirmada')
                ->select('cliente_id', DB::raw('SUM(cantidad) as unidades_compradas'), DB::raw('SUM(total_venta) as total_gastado'))
                ->with('cliente:id,nombre_comercial,categoria')
                ->groupBy('cliente_id')
                ->orderByDesc('total_gastado')
                ->get();

            // Totales generales
            $totales = [
                'ventas_confirmadas' => Venta::where('estado', 'confirmada')->count(),
                'ventas_pendientes'  => Venta::where('estado', 'pendiente')->count(),
                'ventas_anuladas'    => Venta::where('estado', 'anulada')->count(),
                'unidades_totales'   => Venta::where('estado', 'confirmada')->sum('cantidad'),
                'ingresos_totales'   => Venta::where('estado', 'confirmada')->sum('total_venta'),
            ];

            return response()->json([
                'totales'      => $totales,
                'por_camiseta' => $porCamiseta,
                'por_cliente'  => $porCliente,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al calcular estadísticas.', 'error' => $e->getMessage()], 500);
        }
    }
}
