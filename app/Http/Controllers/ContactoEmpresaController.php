<?php

namespace App\Http\Controllers;

use App\Helpers\RutValidator;
use App\Models\Cliente;
use App\Models\ContactoEmpresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactoEmpresaController extends Controller
{
    /**
     * GET /api/clientes/{cliente_id}/contactos-empresa
     * Lista todos los contactos de empresa de un cliente.
     */
    public function index(int $clienteId): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);
            $contactos = $cliente->contactosEmpresa()->get();

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
     * GET /api/clientes/{cliente_id}/contactos-empresa/{id}
     * Muestra un contacto de empresa específico.
     */
    public function show(int $clienteId, int $id): JsonResponse
    {
        try {
            // Verificar que el cliente existe
            Cliente::findOrFail($clienteId);

            $contacto = ContactoEmpresa::where('cliente_id', $clienteId)->findOrFail($id);

            return response()->json(['data' => $contacto], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Recurso no encontrado.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener el contacto.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/clientes/{cliente_id}/contactos-empresa
     * Crea un nuevo contacto de empresa para un cliente.
     */
    public function store(Request $request, int $clienteId): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);

            $validated = $request->validate([
                'razon_social'     => 'required|string|max:255',
                'rut_empresa'      => 'required|string|max:20|unique:contactos_empresa,rut_empresa',
                'giro'             => 'required|string|max:255',
                'direccion_fiscal' => 'required|string|max:255',
                'ciudad'           => 'required|string|max:100',
                'region'           => 'required|string|max:100',
                'pais'             => 'sometimes|string|max:100',
                'telefono_empresa' => 'nullable|string|max:20',
                'email_empresa'    => 'required|email|max:255',
                'sitio_web'        => 'nullable|url|max:255',
                'tipo_contacto'    => ['required', Rule::in(['Principal', 'Facturacion', 'Despacho', 'Cobranza'])],
            ]);

            // ── Validar RUT chileno con algoritmo módulo 11 ──────────────
            if (!RutValidator::validar($validated['rut_empresa'])) {
                return response()->json([
                    'message' => 'El RUT de empresa ingresado no es válido.',
                    'rut'     => $validated['rut_empresa'],
                ], 422);
            }

            // Formatear RUT al estándar XX.XXX.XXX-D
            $validated['rut_empresa'] = RutValidator::formatear($validated['rut_empresa']);
            $validated['cliente_id']  = $clienteId;

            $contacto = ContactoEmpresa::create($validated);

            return response()->json([
                'data'    => $contacto,
                'message' => 'Contacto de empresa creado correctamente.',
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
     * PUT /api/clientes/{cliente_id}/contactos-empresa/{id}
     * Actualiza un contacto de empresa completo.
     */
    public function update(Request $request, int $clienteId, int $id): JsonResponse
    {
        try {
            Cliente::findOrFail($clienteId);
            $contacto = ContactoEmpresa::where('cliente_id', $clienteId)->findOrFail($id);

            $validated = $request->validate([
                'razon_social'     => 'sometimes|required|string|max:255',
                'rut_empresa'      => ['sometimes', 'required', 'string', 'max:20',
                                       Rule::unique('contactos_empresa', 'rut_empresa')->ignore($id)],
                'giro'             => 'sometimes|required|string|max:255',
                'direccion_fiscal' => 'sometimes|required|string|max:255',
                'ciudad'           => 'sometimes|required|string|max:100',
                'region'           => 'sometimes|required|string|max:100',
                'pais'             => 'sometimes|string|max:100',
                'telefono_empresa' => 'nullable|string|max:20',
                'email_empresa'    => 'sometimes|required|email|max:255',
                'sitio_web'        => 'nullable|url|max:255',
                'tipo_contacto'    => ['sometimes', Rule::in(['Principal', 'Facturacion', 'Despacho', 'Cobranza'])],
            ]);

            // Validar RUT si viene en el request
            if (isset($validated['rut_empresa'])) {
                if (!RutValidator::validar($validated['rut_empresa'])) {
                    return response()->json([
                        'message' => 'El RUT de empresa ingresado no es válido.',
                        'rut'     => $validated['rut_empresa'],
                    ], 422);
                }
                $validated['rut_empresa'] = RutValidator::formatear($validated['rut_empresa']);
            }

            $contacto->update($validated);

            return response()->json([
                'data'    => $contacto->fresh(),
                'message' => 'Contacto de empresa actualizado correctamente.',
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
     * PATCH /api/clientes/{cliente_id}/contactos-empresa/{id}
     * Actualiza campos específicos de un contacto de empresa.
     * Mismo comportamiento que update() pero semánticamente parcial.
     */
    public function patch(Request $request, int $clienteId, int $id): JsonResponse
    {
        return $this->update($request, $clienteId, $id);
    }

    /**
     * DELETE /api/clientes/{cliente_id}/contactos-empresa/{id}
     * Elimina un contacto de empresa (soft delete).
     */
    public function destroy(int $clienteId, int $id): JsonResponse
    {
        try {
            Cliente::findOrFail($clienteId);
            $contacto = ContactoEmpresa::where('cliente_id', $clienteId)->findOrFail($id);

            $contacto->delete();

            return response()->json(['message' => 'Contacto de empresa eliminado correctamente.'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Recurso no encontrado.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al eliminar el contacto.', 'error' => $e->getMessage()], 500);
        }
    }
}
