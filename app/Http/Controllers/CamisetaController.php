<?php

namespace App\Http\Controllers;

use App\Models\Camiseta;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CamisetaController extends Controller
{
    /**
     * GET /api/camisetas
     */
    public function index(): JsonResponse
    {
        try {
            $camisetas = Camiseta::with('tallas')->get();
            return response()->json(['data' => $camisetas, 'total' => $camisetas->count()], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener camisetas.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/camisetas/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $camiseta = Camiseta::with('tallas')->findOrFail($id);
            return response()->json(['data' => $camiseta], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Camiseta no encontrada.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener la camiseta.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/camisetas/{id}/precio?cliente_id=1
     * Devuelve la camiseta con precio_final calculado según el cliente.
     */
    public function precioFinal(Request $request, int $id): JsonResponse
    {
        try {
            $camiseta = Camiseta::with('tallas')->findOrFail($id);

            $clienteId = $request->query('cliente_id');
            if (!$clienteId) {
                return response()->json(['message' => 'El parámetro cliente_id es requerido.'], 422);
            }

            $cliente   = Cliente::findOrFail($clienteId);
            $resultado = $camiseta->precioFinalParaCliente($cliente);

            return response()->json([
                'data' => array_merge($camiseta->toArray(), [
                    'precio_final'              => $resultado['precio_final'],
                    'descuento_aplicado'        => $resultado['descuento_aplicado'],
                    'cliente_id'                => $cliente->id,
                    'cliente_nombre'            => $cliente->nombre_comercial,
                    'cliente_categoria'         => $cliente->categoria,
                    'cliente_porcentaje_oferta' => $cliente->porcentaje_oferta,
                ]),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Camiseta o cliente no encontrado.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al calcular precio final.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/clientes/{cliente_id}/camisetas
     * Lista camisetas de un cliente con precio_final ya calculado.
     */
    public function porCliente(int $clienteId): JsonResponse
    {
        try {
            $cliente  = Cliente::findOrFail($clienteId);
            $camisetas = $cliente->camisetas()->with('tallas')->get()->map(function ($camiseta) use ($cliente) {
                $resultado = $camiseta->precioFinalParaCliente($cliente);
                return array_merge($camiseta->toArray(), [
                    'precio_final'       => $resultado['precio_final'],
                    'descuento_aplicado' => $resultado['descuento_aplicado'],
                ]);
            });

            return response()->json([
                'data'    => $camisetas,
                'cliente' => $cliente->nombre_comercial,
                'total'   => $camisetas->count(),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener camisetas del cliente.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/camisetas
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'titulo'            => 'required|string|max:255',
                'club'              => 'required|string|max:255',
                'pais'              => 'required|string|max:100',
                'tipo'              => ['required', Rule::in(['Local', 'Visita', '3era Camiseta', 'Femenino Local', 'Niño'])],
                'color'             => 'required|string|max:100',
                'precio'            => 'required|numeric|min:0',
                'precio_oferta'     => 'nullable|numeric|min:0',
                'detalles'          => 'nullable|string',
                'codigo_producto'   => 'required|string|unique:camisetas,codigo_producto|max:50',
                'tallas'            => 'nullable|array',
                'tallas.*.talla_id' => 'required|exists:tallas,id',
                'tallas.*.stock'    => 'required|integer|min:0',
            ]);

            DB::beginTransaction();

            $camiseta = Camiseta::create($validated);

            if (!empty($validated['tallas'])) {
                $syncData = [];
                foreach ($validated['tallas'] as $t) {
                    $syncData[$t['talla_id']] = ['stock' => $t['stock']];
                }
                $camiseta->tallas()->sync($syncData);
            }

            DB::commit();

            return response()->json([
                'data'    => $camiseta->load('tallas'),
                'message' => 'Camiseta creada correctamente.',
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear la camiseta.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT|PATCH /api/camisetas/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $camiseta = Camiseta::findOrFail($id);

            $validated = $request->validate([
                'titulo'            => 'sometimes|required|string|max:255',
                'club'              => 'sometimes|required|string|max:255',
                'pais'              => 'sometimes|required|string|max:100',
                'tipo'              => ['sometimes', 'required', Rule::in(['Local', 'Visita', '3era Camiseta', 'Femenino Local', 'Niño'])],
                'color'             => 'sometimes|required|string|max:100',
                'precio'            => 'sometimes|required|numeric|min:0',
                'precio_oferta'     => 'nullable|numeric|min:0',
                'detalles'          => 'nullable|string',
                'codigo_producto'   => ['sometimes', 'required', 'string', 'max:50',
                                        Rule::unique('camisetas', 'codigo_producto')->ignore($id)],
                'tallas'            => 'nullable|array',
                'tallas.*.talla_id' => 'required|exists:tallas,id',
                'tallas.*.stock'    => 'required|integer|min:0',
            ]);

            DB::beginTransaction();

            $camiseta->update($validated);

            if (isset($validated['tallas'])) {
                $syncData = [];
                foreach ($validated['tallas'] as $t) {
                    $syncData[$t['talla_id']] = ['stock' => $t['stock']];
                }
                $camiseta->tallas()->sync($syncData);
            }

            DB::commit();

            return response()->json([
                'data'    => $camiseta->load('tallas'),
                'message' => 'Camiseta actualizada correctamente.',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Camiseta no encontrada.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar la camiseta.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/camisetas/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $camiseta = Camiseta::findOrFail($id);
            $camiseta->delete();
            return response()->json(['message' => 'Camiseta eliminada correctamente.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Camiseta no encontrada.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al eliminar la camiseta.', 'error' => $e->getMessage()], 500);
        }
    }
}
