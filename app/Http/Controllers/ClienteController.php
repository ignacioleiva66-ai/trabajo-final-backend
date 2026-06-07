<?php

namespace App\Http\Controllers;

use App\Helpers\RutValidator;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /**
     * GET /api/clientes
     */
    public function index(): JsonResponse
    {
        try {
            $clientes = Cliente::with(['contactosEmpresa', 'contactosPersonal'])->get();
            return response()->json(['data' => $clientes, 'total' => $clientes->count()], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener clientes.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/clientes/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $cliente = Cliente::with(['camisetas', 'contactosEmpresa', 'contactosPersonal'])->findOrFail($id);
            return response()->json(['data' => $cliente], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener el cliente.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/clientes
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre_comercial'  => 'required|string|max:255',
                'rut'               => 'required|string|unique:clientes,rut|max:20',
                'direccion'         => 'required|string|max:255',
                'categoria'         => ['required', Rule::in(['Regular', 'Preferencial'])],
                'contacto_nombre'   => 'required|string|max:255',
                'contacto_email'    => 'required|email|max:255',
                'porcentaje_oferta' => 'nullable|numeric|min:0|max:100',
            ]);

            // Validar RUT chileno
            if (!RutValidator::validar($validated['rut'])) {
                return response()->json([
                    'message' => 'El RUT ingresado no es válido.',
                    'rut'     => $validated['rut'],
                ], 422);
            }
            $validated['rut'] = RutValidator::formatear($validated['rut']);

            $cliente = Cliente::create($validated);

            return response()->json(['data' => $cliente, 'message' => 'Cliente creado correctamente.'], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al crear el cliente.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/clientes/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($id);

            $validated = $request->validate([
                'nombre_comercial'  => 'sometimes|required|string|max:255',
                'rut'               => ['sometimes', 'required', 'string', 'max:20', Rule::unique('clientes', 'rut')->ignore($id)],
                'direccion'         => 'sometimes|required|string|max:255',
                'categoria'         => ['sometimes', 'required', Rule::in(['Regular', 'Preferencial'])],
                'contacto_nombre'   => 'sometimes|required|string|max:255',
                'contacto_email'    => 'sometimes|required|email|max:255',
                'porcentaje_oferta' => 'nullable|numeric|min:0|max:100',
            ]);

            if (isset($validated['rut'])) {
                if (!RutValidator::validar($validated['rut'])) {
                    return response()->json(['message' => 'El RUT ingresado no es válido.', 'rut' => $validated['rut']], 422);
                }
                $validated['rut'] = RutValidator::formatear($validated['rut']);
            }

            $cliente->update($validated);

            return response()->json(['data' => $cliente->fresh(), 'message' => 'Cliente actualizado correctamente.'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al actualizar el cliente.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/clientes/{id}
     */
    public function patch(Request $request, int $id): JsonResponse
    {
        return $this->update($request, $id);
    }

    /**
     * DELETE /api/clientes/{id}
     * No se puede eliminar si tiene camisetas o ventas.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $cliente = Cliente::withCount(['camisetas', 'ventas'])->findOrFail($id);

            if ($cliente->camisetas_count > 0) {
                return response()->json(['message' => 'No se puede eliminar el cliente porque tiene camisetas asociadas.'], 409);
            }
            if ($cliente->ventas_count > 0) {
                return response()->json(['message' => 'No se puede eliminar el cliente porque tiene ventas registradas.'], 409);
            }

            $cliente->delete();

            return response()->json(['message' => 'Cliente eliminado correctamente.'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al eliminar el cliente.', 'error' => $e->getMessage()], 500);
        }
    }
}
