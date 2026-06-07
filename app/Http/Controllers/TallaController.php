<?php

namespace App\Http\Controllers;

use App\Models\Camiseta;
use App\Models\Talla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TallaController extends Controller
{
    /**
     * GET /api/tallas
     */
    public function index(): JsonResponse
    {
        try {
            $tallas = Talla::all();
            return response()->json(['data' => $tallas, 'total' => $tallas->count()], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener tallas.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/tallas/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $talla = Talla::with('camisetas')->findOrFail($id);
            return response()->json(['data' => $talla], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Talla no encontrada.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener la talla.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/tallas
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|unique:tallas,nombre|max:10',
            ]);

            $talla = Talla::create($validated);

            return response()->json(['data' => $talla, 'message' => 'Talla creada correctamente.'], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al crear la talla.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/tallas/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $talla = Talla::findOrFail($id);

            $validated = $request->validate([
                'nombre' => "required|string|unique:tallas,nombre,{$id}|max:10",
            ]);

            $talla->update($validated);

            return response()->json(['data' => $talla->fresh(), 'message' => 'Talla actualizada correctamente.'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Talla no encontrada.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al actualizar la talla.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/tallas/{id}
     * No se puede eliminar si está en uso por alguna camiseta.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $talla = Talla::withCount('camisetas')->findOrFail($id);

            if ($talla->camisetas_count > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar la talla porque está asociada a camisetas.',
                ], 409);
            }

            $talla->delete();

            return response()->json(['message' => 'Talla eliminada correctamente.'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Talla no encontrada.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al eliminar la talla.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/camisetas/{camiseta_id}/tallas
     * Asocia tallas a una camiseta con stock.
     */
    public function asociarACamiseta(Request $request, int $camisetaId): JsonResponse
    {
        try {
            $camiseta = Camiseta::findOrFail($camisetaId);

            $validated = $request->validate([
                'tallas'            => 'required|array|min:1',
                'tallas.*.talla_id' => 'required|exists:tallas,id',
                'tallas.*.stock'    => 'required|integer|min:0',
            ]);

            $syncData = [];
            foreach ($validated['tallas'] as $t) {
                $syncData[$t['talla_id']] = ['stock' => $t['stock']];
            }

            $camiseta->tallas()->sync($syncData);

            return response()->json([
                'data'    => $camiseta->load('tallas'),
                'message' => 'Tallas actualizadas correctamente.',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Camiseta no encontrada.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al asociar tallas.', 'error' => $e->getMessage()], 500);
        }
    }
}
