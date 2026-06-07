<?php

namespace App\Http\Controllers;

use App\Helpers\RutValidator;
use App\Models\Cliente;
use App\Models\ContactoPersonal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactoPersonalController extends Controller
{
    /**
     * GET /api/clientes/{cliente_id}/contactos-personal
     * Lista todos los contactos personales de un cliente.
     */
    public function index(int $clienteId): JsonResponse
    {
        try {
            $cliente   = Cliente::findOrFail($clienteId);
            $contactos = $cliente->contactosPersonal()->get();

            return response()->json([
                'data'    => $contactos,
                'cliente' => $cliente->nombre_comercial,
                'total'   => $contactos->count(),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener contactos.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/clientes/{cliente_id}/contactos-personal/{id}
     * Muestra un contacto personal específico.
     */
    public function show(int $clienteId, int $id): JsonResponse
    {
        try {
            Cliente::findOrFail($clienteId);
            $contacto = ContactoPersonal::where('cliente_id', $clienteId)->findOrFail($id);

            return response()->json(['data' => $contacto], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Recurso no encontrado.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener el contacto.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/clientes/{cliente_id}/contactos-personal
     * Crea un nuevo contacto personal para un cliente.
     */
    public function store(Request $request, int $clienteId): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);

            $validated = $request->validate([
                'nombre'                => 'required|string|max:100',
                'apellido'              => 'required|string|max:100',
                'rut_personal'          => 'nullable|string|max:20',
                'cargo'                 => 'required|string|max:100',
                'area'                  => ['required', Rule::in(['Compras', 'Finanzas', 'Gerencia', 'Logistica', 'Ventas', 'Otro'])],
                'email'                 => 'required|email|max:255',
                'telefono'              => 'nullable|string|max:20',
                'celular'               => 'nullable|string|max:20',
                'es_contacto_principal' => 'boolean',
                'notas'                 => 'nullable|string',
            ]);

            // ── Validar RUT personal si viene en el request ──────────────
            if (!empty($validated['rut_personal'])) {
                if (!RutValidator::validar($validated['rut_personal'])) {
                    return response()->json([
                        'message' => 'El RUT personal ingresado no es válido.',
                        'rut'     => $validated['rut_personal'],
                    ], 422);
                }
                $validated['rut_personal'] = RutValidator::formatear($validated['rut_personal']);
            }

            // Si es contacto principal, desmarcar el anterior
            if (!empty($validated['es_contacto_principal'])) {
                ContactoPersonal::where('cliente_id', $clienteId)
                    ->where('es_contacto_principal', true)
                    ->update(['es_contacto_principal' => false]);
            }

            $validated['cliente_id'] = $clienteId;
            $contacto = ContactoPersonal::create($validated);

            return response()->json([
                'data'    => $contacto,
                'message' => 'Contacto personal creado correctamente.',
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al crear el contacto.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/clientes/{cliente_id}/contactos-personal/{id}
     * Actualiza un contacto personal completo.
     */
    public function update(Request $request, int $clienteId, int $id): JsonResponse
    {
        try {
            Cliente::findOrFail($clienteId);
            $contacto = ContactoPersonal::where('cliente_id', $clienteId)->findOrFail($id);

            $validated = $request->validate([
                'nombre'                => 'sometimes|required|string|max:100',
                'apellido'              => 'sometimes|required|string|max:100',
                'rut_personal'          => 'nullable|string|max:20',
                'cargo'                 => 'sometimes|required|string|max:100',
                'area'                  => ['sometimes', Rule::in(['Compras', 'Finanzas', 'Gerencia', 'Logistica', 'Ventas', 'Otro'])],
                'email'                 => 'sometimes|required|email|max:255',
                'telefono'              => 'nullable|string|max:20',
                'celular'               => 'nullable|string|max:20',
                'es_contacto_principal' => 'boolean',
                'notas'                 => 'nullable|string',
            ]);

            // Validar RUT personal si viene en el request
            if (!empty($validated['rut_personal'])) {
                if (!RutValidator::validar($validated['rut_personal'])) {
                    return response()->json([
                        'message' => 'El RUT personal ingresado no es válido.',
                        'rut'     => $validated['rut_personal'],
                    ], 422);
                }
                $validated['rut_personal'] = RutValidator::formatear($validated['rut_personal']);
            }

            // Si pasa a ser principal, desmarcar el anterior
            if (!empty($validated['es_contacto_principal'])) {
                ContactoPersonal::where('cliente_id', $clienteId)
                    ->where('id', '!=', $id)
                    ->where('es_contacto_principal', true)
                    ->update(['es_contacto_principal' => false]);
            }

            $contacto->update($validated);

            return response()->json([
                'data'    => $contacto->fresh(),
                'message' => 'Contacto personal actualizado correctamente.',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Recurso no encontrado.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al actualizar el contacto.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/clientes/{cliente_id}/contactos-personal/{id}
     * Actualiza campos específicos de un contacto personal.
     */
    public function patch(Request $request, int $clienteId, int $id): JsonResponse
    {
        return $this->update($request, $clienteId, $id);
    }

    /**
     * DELETE /api/clientes/{cliente_id}/contactos-personal/{id}
     * Elimina un contacto personal (soft delete).
     */
    public function destroy(int $clienteId, int $id): JsonResponse
    {
        try {
            Cliente::findOrFail($clienteId);
            $contacto = ContactoPersonal::where('cliente_id', $clienteId)->findOrFail($id);

            $contacto->delete();

            return response()->json(['message' => 'Contacto personal eliminado correctamente.'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Recurso no encontrado.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al eliminar el contacto.', 'error' => $e->getMessage()], 500);
        }
    }
}
